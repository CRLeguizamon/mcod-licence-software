<?php
/**
 * Update API and Secure File Download Listener
 *
 * Listens for plugin update check requests and serves ZIP file downloads
 * after verifying active licenses using signed HMAC tokens.
 *
 * Security model (server-agnostic):
 * - Update check (mcrpd_check_update): validates secret_key + license_key, returns
 *   a download_url containing a time-limited HMAC-signed token.
 * - File download (mcrpd_download_zip): validates the signed token + expiration.
 *   No secret_key or license_key in the download URL is required; the token IS the auth.
 * - Files on disk are renamed to random hashes (.bin) so URLs are unguessable.
 * - .htaccess provides an extra layer on Apache/LiteSpeed but is NOT required.
 *
 * @package MCOD Software License Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MCRPD_Update_API {

	/**
	 * How long a download token is valid (in seconds).
	 * Default: 12 hours (43200 seconds).
	 */
	const TOKEN_EXPIRY_SECONDS = 43200;

	public function __construct() {
		add_action( 'init', array( $this, 'api_listener' ) );
	}

	/**
	 * Main listener for update and download actions.
	 */
	public function api_listener() {
		if ( ! isset( $_REQUEST['slm_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_REQUEST['slm_action'] ) );

		if ( 'mcrpd_check_update' === $action ) {
			$this->handle_check_update();
		} elseif ( 'mcrpd_download_zip' === $action ) {
			$this->handle_download_zip();
		}
	}

	// ──────────────────────────────────────────────
	//  Token Helpers
	// ──────────────────────────────────────────────

	/**
	 * Generates a signed HMAC token for a download URL.
	 *
	 * @param string $license_key  The license key.
	 * @param string $product_ref  The product reference.
	 * @param int    $expires      Unix timestamp when the token expires.
	 * @return string  The HMAC-SHA256 hex digest.
	 */
	private function generate_download_token( $license_key, $product_ref, $expires ) {
		$data = $license_key . '|' . $product_ref . '|' . $expires;
		return hash_hmac( 'sha256', $data, $this->get_signing_key() );
	}

	/**
	 * Validates a download token.
	 *
	 * @param string $token        The received token.
	 * @param string $license_key  The license key from the URL.
	 * @param string $product_ref  The product reference from the URL.
	 * @param int    $expires      The expiry timestamp from the URL.
	 * @return bool  True if valid, false otherwise.
	 */
	private function validate_download_token( $token, $license_key, $product_ref, $expires ) {
		// Check expiration first
		if ( time() > intval( $expires ) ) {
			return false;
		}

		$expected = $this->generate_download_token( $license_key, $product_ref, $expires );
		return hash_equals( $expected, $token );
	}

	/**
	 * Gets the signing key for HMAC tokens.
	 * Uses wp_salt('auth') which is unique per WordPress installation.
	 *
	 * @return string  The signing key.
	 */
	private function get_signing_key() {
		return wp_salt( 'auth' );
	}

	// ──────────────────────────────────────────────
	//  Credential Verification
	// ──────────────────────────────────────────────

	/**
	 * Verifies the API secret key.
	 */
	private function verify_secret_key() {
		if ( ! isset( $_REQUEST['secret_key'] ) ) {
			$this->send_error_response( 'Secret key is missing', 'SECRET_KEY_MISSING' );
		}

		$options             = get_option( 'slm_plugin_options' );
		$right_secret_key    = isset( $options['lic_verification_secret'] ) ? $options['lic_verification_secret'] : '';
		$received_secret_key = sanitize_text_field( wp_unslash( $_REQUEST['secret_key'] ) );

		if ( empty( $right_secret_key ) || $received_secret_key !== $right_secret_key ) {
			$this->send_error_response( 'Verification API secret key is invalid', 'VERIFY_KEY_INVALID' );
		}
	}

	/**
	 * Validates the license key and checks if it's active.
	 *
	 * @param string $license_key  The license key to validate.
	 * @return object  The license row from the database.
	 */
	private function validate_license( $license_key ) {
		if ( empty( $license_key ) ) {
			$this->send_error_response( 'License key is missing', 'LICENSE_KEY_MISSING' );
		}

		global $wpdb;
		$tbl_name = SLM_TBL_LICENSE_KEYS;
		$sql      = $wpdb->prepare( "SELECT * FROM $tbl_name WHERE license_key = %s", $license_key );
		$license  = $wpdb->get_row( $sql, OBJECT );

		if ( ! $license ) {
			$this->send_error_response( 'Invalid license key', 'LICENSE_INVALID' );
		}

		if ( 'active' !== $license->lic_status ) {
			$this->send_error_response( 'License key status is ' . $license->lic_status, 'LICENSE_NOT_ACTIVE' );
		}

		return $license;
	}

	/**
	 * Finds project post by its Product Reference.
	 *
	 * @param string $product_ref  The product reference to search for.
	 * @return WP_Post  The project post object.
	 */
	private function get_project_by_ref( $product_ref ) {
		if ( empty( $product_ref ) ) {
			$this->send_error_response( 'Product reference is missing', 'PRODUCT_REF_MISSING' );
		}

		$args = array(
			'post_type'      => 'mcrpd_project',
			'meta_key'       => 'mcrpd_product_ref',
			'meta_value'     => $product_ref,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		);

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			$this->send_error_response( 'Project not found for the given product reference', 'PROJECT_NOT_FOUND' );
		}

		return $posts[0];
	}

	// ──────────────────────────────────────────────
	//  API Handlers
	// ──────────────────────────────────────────────

	/**
	 * Handles mcrpd_check_update action.
	 *
	 * Validates secret_key + license_key, then returns update JSON
	 * with a signed download_url that expires after TOKEN_EXPIRY_SECONDS.
	 */
	private function handle_check_update() {
		global $slm_debug_logger;
		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( 'API - Update check (mcrpd_check_update) requested.' );
		}

		// 1. Verify credentials
		$this->verify_secret_key();

		$license_key = isset( $_REQUEST['license_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['license_key'] ) ) : '';
		$product_ref = isset( $_REQUEST['product_ref'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['product_ref'] ) ) : '';

		// 2. Validate license and project
		$this->validate_license( $license_key );
		$project = $this->get_project_by_ref( $product_ref );

		// 3. Gather project metadata
		$stable_version = get_post_meta( $project->ID, 'mcrpd_stable_version', true );
		$tested         = get_post_meta( $project->ID, 'mcrpd_tested_up_to', true );
		$requires       = get_post_meta( $project->ID, 'mcrpd_requires_wp', true );
		$requires_php   = get_post_meta( $project->ID, 'mcrpd_requires_php', true );
		$releases       = get_post_meta( $project->ID, 'mcrpd_releases', true );
		$releases       = is_array( $releases ) ? $releases : array();

		if ( empty( $stable_version ) ) {
			$this->send_error_response( 'No stable version defined for this project', 'NO_STABLE_VERSION' );
		}

		// Find the stable release details
		$stable_release = null;
		foreach ( $releases as $rel ) {
			if ( $rel['version'] === $stable_version ) {
				$stable_release = $rel;
				break;
			}
		}

		$changelog    = $stable_release ? $stable_release['changelog'] : '';
		$last_updated = $stable_release ? date( 'Y-m-d', strtotime( $stable_release['date'] ) ) : date( 'Y-m-d' );

		// Project description from post content
		$description = $project->post_content;
		if ( empty( $description ) ) {
			$description = __( 'No description provided.', 'slm' );
		}

		// 4. Build signed download URL
		$expires  = time() + self::TOKEN_EXPIRY_SECONDS;
		$token    = $this->generate_download_token( $license_key, $product_ref, $expires );

		$download_url = add_query_arg(
			array(
				'slm_action'  => 'mcrpd_download_zip',
				'license_key' => $license_key,
				'product_ref' => $product_ref,
				'expires'     => $expires,
				'token'       => $token,
			),
			home_url( '/' )
		);

		// 5. Format output JSON
		$update_data = array(
			'version'      => $stable_version,
			'download_url' => $download_url,
			'tested'       => $tested ? $tested : '',
			'requires'     => $requires ? $requires : '',
			'requires_php' => $requires_php ? $requires_php : '',
			'last_updated' => $last_updated,
			'sections'     => array(
				'description' => wp_kses_post( $description ),
				'changelog'   => wp_kses_post( $changelog ),
			),
		);

		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( 'API - Update check response: version=' . $stable_version . ', token expires at ' . date( 'Y-m-d H:i:s', $expires ) );
		}

		header( 'Content-Type: application/json' );
		echo json_encode( $update_data );
		exit;
	}

	/**
	 * Handles mcrpd_download_zip action.
	 *
	 * Validates the HMAC-signed token (no secret_key needed in the URL).
	 * If valid and not expired, streams the ZIP file via readfile().
	 */
	private function handle_download_zip() {
		global $slm_debug_logger;
		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( 'API - File download (mcrpd_download_zip) requested.' );
		}

		// 1. Extract and sanitize parameters
		$license_key = isset( $_REQUEST['license_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['license_key'] ) ) : '';
		$product_ref = isset( $_REQUEST['product_ref'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['product_ref'] ) ) : '';
		$expires     = isset( $_REQUEST['expires'] ) ? intval( $_REQUEST['expires'] ) : 0;
		$token       = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : '';

		// 2. Validate the signed token
		if ( empty( $token ) || empty( $expires ) ) {
			$this->send_error_response( 'Download token is missing', 'TOKEN_MISSING' );
		}

		if ( ! $this->validate_download_token( $token, $license_key, $product_ref, $expires ) ) {
			if ( time() > $expires ) {
				$this->send_error_response( 'Download token has expired. Request a new update check.', 'TOKEN_EXPIRED' );
			}
			$this->send_error_response( 'Invalid download token', 'TOKEN_INVALID' );
		}

		// 3. Extra safety: verify the license is still active at download time
		$this->validate_license( $license_key );

		// 4. Find the project and its stable release
		$project = $this->get_project_by_ref( $product_ref );

		$stable_version = get_post_meta( $project->ID, 'mcrpd_stable_version', true );
		$releases       = get_post_meta( $project->ID, 'mcrpd_releases', true );
		$releases       = is_array( $releases ) ? $releases : array();

		if ( empty( $stable_version ) ) {
			wp_die( esc_html__( 'Stable version not configured.', 'slm' ), 404 );
		}

		$stable_release = null;
		foreach ( $releases as $rel ) {
			if ( $rel['version'] === $stable_version ) {
				$stable_release = $rel;
				break;
			}
		}

		if ( ! $stable_release || empty( $stable_release['file_id'] ) ) {
			wp_die( esc_html__( 'Stable version ZIP file not found.', 'slm' ), 404 );
		}

		// 5. Get the file path from disk (not from URL)
		$file_id   = intval( $stable_release['file_id'] );
		$file_path = get_attached_file( $file_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			if ( isset( $slm_debug_logger ) ) {
				$slm_debug_logger->log_debug( 'API - ERROR: File not found on disk. Attachment ID: ' . $file_id );
			}
			wp_die( esc_html__( 'Release file does not exist on the server.', 'slm' ), 404 );
		}

		// 6. Serve the file securely via PHP (never redirect to public URL)
		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( 'API - Serving ZIP securely via readfile(): ' . basename( $file_path ) );
		}

		// Use the original attachment title as the download filename
		$attachment  = get_post( $file_id );
		$original_name = $attachment ? sanitize_file_name( $attachment->post_title ) : basename( $file_path );
		// Ensure the download filename has .zip extension
		if ( pathinfo( $original_name, PATHINFO_EXTENSION ) !== 'zip' ) {
			$original_name .= '.zip';
		}

		// Clean output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $original_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		readfile( $file_path );
		exit;
	}

	// ──────────────────────────────────────────────
	//  Response Helpers
	// ──────────────────────────────────────────────

	/**
	 * Outputs error response in JSON.
	 *
	 * @param string $message     Human-readable error message.
	 * @param string $error_code  Machine-readable error code.
	 */
	private function send_error_response( $message, $error_code ) {
		header( 'Content-Type: application/json', true, 403 );
		echo json_encode(
			array(
				'result'     => 'error',
				'message'    => $message,
				'error_code' => $error_code,
			)
		);
		exit;
	}
}

// Instantiate
new MCRPD_Update_API();
