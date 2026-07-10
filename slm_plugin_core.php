<?php

//Defines
global $wpdb;
define( 'SLM_TBL_LICENSE_KEYS', $wpdb->prefix . 'lic_key_tbl' );
define( 'SLM_TBL_LIC_DOMAIN', $wpdb->prefix . 'lic_reg_domain_tbl' );
define( 'SLM_MANAGEMENT_PERMISSION', apply_filters( 'slm_management_permission_role', 'manage_options' ) );
define( 'SLM_MAIN_MENU_SLUG', 'slm-main' );
define( 'SLM_MENU_ICON', 'dashicons-lock' );

//Includes
require_once 'includes/slm-debug-logger.php';
require_once 'includes/slm-error-codes.php';
require_once 'includes/slm-utility.php';
require_once 'includes/slm-init-time-tasks.php';
require_once 'includes/slm-api-utility.php';
require_once 'includes/slm-api-listener.php';
require_once 'includes/slm-third-party-integration.php';
require_once 'includes/mcrpd-project-cpt.php';
//Include admin side only files
if ( is_admin() ) {
	include_once 'menu/slm-admin-init.php';
}

//Action hooks
add_action( 'init', 'slm_init_handler' );
add_action( 'plugins_loaded', 'slm_plugins_loaded_handler' );
add_action( 'admin_enqueue_scripts', 'mcrpd_enqueue_admin_assets' );

// WooCommerce Subscriptions Integration
add_action( 'plugins_loaded', 'mcrpd_maybe_load_wc_integration' );
function mcrpd_maybe_load_wc_integration() {
    if ( class_exists( 'WC_Subscriptions' ) ) {
        $options = get_option( 'slm_plugin_options' );
        if ( ! empty( $options['mcrpd_wc_enable'] ) ) {
            require_once WP_LICENSE_MANAGER_PATH . 'includes/mcrpd-wc-subscriptions-admin.php';
        }
    }
}

add_action( 'wp_enqueue_scripts', 'mcrpd_enqueue_frontend_assets' );
function mcrpd_enqueue_frontend_assets() {
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'mcrpd-licenses' ) ) {
		wp_enqueue_style( 'mcrpd-frontend-css', WP_LICENSE_MANAGER_URL . '/css/mcrpd-frontend.css', array(), WP_LICENSE_MANAGER_VERSION );
		wp_enqueue_script( 'mcrpd-frontend-js', WP_LICENSE_MANAGER_URL . '/js/mcrpd-frontend.js', array(), WP_LICENSE_MANAGER_VERSION, true );
	}
}

function mcrpd_enqueue_admin_assets( $hook ) {
	// Only load on our plugin pages
	if ( strpos( $hook, 'slm-main' ) === false && strpos( $hook, 'wp_lic_mgr_' ) === false && strpos( $hook, 'mcrpd_emails_menu' ) === false && strpos( $hook, 'lic_mgr_integration_help_page' ) === false ) {
		return;
	}
	
	wp_enqueue_style( 'mcrpd-admin-css', WP_LICENSE_MANAGER_URL . '/css/mcrpd-admin.css', array(), WP_LICENSE_MANAGER_VERSION );
	
	wp_enqueue_script( 'mcrpd-admin-js', WP_LICENSE_MANAGER_URL . '/js/mcrpd-admin.js', array( 'jquery' ), WP_LICENSE_MANAGER_VERSION, true );
	wp_localize_script( 'mcrpd-admin-js', 'slm_admin_data', array(
		'confirm_remove_domain' => __( 'Are you sure you want to remove this domain?', 'slm' ),
		'confirm_bulk_op'       => __( 'Are you sure you want to perform this bulk operation on the selected entries?', 'slm' ),
		'msg_loading'           => __( 'Loading...', 'slm' ),
		'msg_deleted'           => __( 'Deleted', 'slm' ),
		'msg_failed'            => __( 'Failed', 'slm' ),
		'msg_no_domains'        => __( 'No domains activated.', 'slm' )
	));
}

//Initialize debug logger
global $slm_debug_logger;
$slm_debug_logger = new SLM_Debug_Logger();

//Do init time tasks
function slm_init_handler() {
	$init_task    = new SLM_Init_Time_Tasks();
	$api_listener = new SLM_API_Listener();
}

//Do plugins loaded time tasks
function slm_plugins_loaded_handler() {
	//Runs when plugins_loaded action gets fired
	if ( is_admin() ) {
		//Check if db update needed
		if ( get_option( 'wp_lic_mgr_db_version' ) != WP_LICENSE_MANAGER_DB_VERSION ) {
			require_once dirname( __FILE__ ) . '/slm_installer.php';
		}
	}

}

//TODO - need to move this to an ajax handler file
add_action( 'wp_ajax_slm_delete_domain', 'slm_del_reg_dom' );
function slm_del_reg_dom() {
	$out = array( 'status' => 'fail' );

	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json( $out );
	}

	global $wpdb;

	$lic_id    = filter_input( INPUT_POST, 'lic_id', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT );
	$domain_id = filter_input( INPUT_POST, 'domain_id', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT );

	if ( empty( $lic_id ) || empty( $domain_id ) ) {
		wp_send_json( $out );
	}

	$reg_table = SLM_TBL_LIC_DOMAIN;

	if ( ! check_ajax_referer( sprintf( 'slm_delete_domain_lic_%s_id_%s', $lic_id, $domain_id ), false, false ) ) {
		wp_send_json( $out );
	}

        do_action( 'slm_before_registered_domain_delete', $domain_id );
  
	$wpdb->query( $wpdb->prepare( "DELETE FROM $reg_table WHERE id=%d", $domain_id ) ); //phpcs:ignore

	// MCOD Custom Logic: Set status to pending if no domains left
	$remaining_domains = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $reg_table WHERE lic_key_id = %d", $lic_id ) );
	if ( $remaining_domains == 0 ) {
		$lk_table = SLM_TBL_LICENSE_KEYS;
		$current_status = $wpdb->get_var( $wpdb->prepare( "SELECT lic_status FROM $lk_table WHERE id = %d", $lic_id ) );
		if ( $current_status !== 'expired' ) {
			$wpdb->update( $lk_table, array( 'lic_status' => 'pending' ), array( 'id' => $lic_id ) );
		}
	}

	$out['status'] = 'success';
        $out = apply_filters( 'slm_registered_domain_delete_response', $out );
  
	wp_send_json( $out );
}
