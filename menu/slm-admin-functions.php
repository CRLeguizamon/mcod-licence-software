<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wp_lic_mgr_admin_fnc_menu() {

	echo '<div class="wrap slm-admin-wrap">';
	echo '<h2>' . esc_html__( 'License Manager Admin Functions', 'slm' ) . '</h2>';
	echo '<div id="poststuff"><div id="post-body">';

	$slm_options = get_option( 'slm_plugin_options' );

	$post_url = '';

	// Rule 3: Never use superglobals directly. wp_unslash first, then sanitize/validate.
	$post_data = wp_unslash( $_POST );
	if ( isset( $post_data['send_deactivation_request'] ) ) {
		check_admin_referer( 'slm_send_deact_req' );
		$post_url                 = isset( $post_data['lic_mgr_deactivation_req_url'] ) ? esc_url_raw( sanitize_text_field( $post_data['lic_mgr_deactivation_req_url'] ) ) : '';
		$secretKeyForVerification = isset( $slm_options['lic_verification_secret'] ) ? $slm_options['lic_verification_secret'] : '';
		$data                     = array();
		$data['secret_key']       = $secretKeyForVerification;

		if ( empty( $post_url ) ) {
			wp_die( esc_html__( 'The URL value is empty. Go back and enter a valid URL value.', 'slm' ) );
		}

		// Send query to the client's deactivation URL
		$response = wp_remote_get( add_query_arg( $data, $post_url ), array( 'timeout' => 20, 'sslverify' => false ) );

		// Check for error in the response
		if ( is_wp_error( $response ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Unexpected Error! The query returned with an error.', 'slm' ) . '</p></div>';
		} else {
			// License data.
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			echo '<div id="message" class="updated fade"><p>';
			echo esc_html__( 'Request sent to the specified URL!', 'slm' );
			echo '</p></div>';
			echo '<h3>' . esc_html__( 'Server Response Dump:', 'slm' ) . '</h3>';
			echo '<pre style="background: #111827; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-family: monospace; font-size: 13px;">';
			echo esc_html( print_r( $license_data, true ) );
			echo '</pre>';
		}
	}
	?>
	<br />
	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e( 'Send Deactivation Message for a License', 'slm' ); ?></label></h3>
		<div class="inside">

			<div class="slm-info-card">
				<h4><?php _e( 'What is the "Admin Functions" section used for?', 'slm' ); ?></h4>
				<p><?php _e( 'This section is a troubleshooting and synchronization utility designed for developers and store administrators. It allows you to manually send a remote deactivation request to a client site\'s license deactivation endpoint (webhook).', 'slm' ); ?></p>
				
				<h4><?php _e( 'How does it work?', 'slm' ); ?></h4>
				<p><?php _e( 'When you trigger a request from this page:', 'slm' ); ?></p>
				<ul>
					<li><?php _e( 'It sends a secure <code>GET</code> request to the remote URL you specify.', 'slm' ); ?></li>
					<li><?php _e( 'It automatically appends your <strong>Verification Secret Key</strong> as a query parameter (<code>secret_key</code>).', 'slm' ); ?></li>
					<li><?php _e( 'The remote client site uses this key to authenticate that the deactivation instruction comes from your authorized license server, allowing it to instantly invalidate its local license cache and database entry.', 'slm' ); ?></li>
				</ul>

				<h4><?php _e( 'When should you use it?', 'slm' ); ?></h4>
				<ul>
					<li><strong><?php _e( 'Testing Connections:', 'slm' ); ?></strong> <?php _e( 'Confirm that your client\'s callback listener URL is active, reachable, and correctly responding to incoming webhook events.', 'slm' ); ?></li>
					<li><strong><?php _e( 'Force Sync/Deactivation:', 'slm' ); ?></strong> <?php _e( 'Immediately deactivate a client\'s license remotely (e.g., in cases of non-payment or chargeback) without waiting for their site to run its regular scheduled cron validation checks.', 'slm' ); ?></li>
				</ul>
			</div>

			<p><strong><?php _e( 'Enter the URL where the license deactivation message will be sent to:', 'slm' ); ?></strong></p>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'slm_send_deact_req' ); ?>
				<input name="lic_mgr_deactivation_req_url" type="text" style="width: 100%; max-width: 600px; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; color: #111827; margin-bottom: 12px;" value="<?php echo esc_attr( $post_url ); ?>" placeholder="https://client-site.com/?slm_action=slm_deactivate&license_key=..." />
				<div class="submit" style="padding: 0; margin-top: 10px;">
					<input type="submit" name="send_deactivation_request" value="<?php esc_attr_e( 'Send Request', 'slm' ); ?>" class="button-primary" />
				</div>
			</form>
		</div>
	</div>
	<?php
	echo '</div></div>';
	echo '</div>';
}

