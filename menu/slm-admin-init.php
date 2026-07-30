<?php

/*
 * This file only gets included if "is_admin()" check is true.
 * Admin menu rendering code goes in this file.
 */

add_action( 'admin_menu', 'wp_lic_mgr_add_admin_menu' );

//Include menu handling files
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-manage-licenses.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-add-licenses.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-lic-settings.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-admin-functions.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-integration-help-page.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/mcrpd-emails-menu.php';

function wp_lic_mgr_add_admin_menu() {
	add_menu_page( 'License Manager', 'License Manager', SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, 'wp_lic_mgr_manage_licenses_menu', SLM_MENU_ICON );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Manage Licenses', 'Manage Licenses', SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, 'wp_lic_mgr_manage_licenses_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Add/Edit Licenses', 'Add/Edit Licenses', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_addedit', 'wp_lic_mgr_add_licenses_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Settings', 'Settings', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_settings', 'wp_lic_mgr_settings_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Admin Functions', 'Admin Functions', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_admin_fnc', 'wp_lic_mgr_admin_fnc_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Integration Help', 'Integration Help', SLM_MANAGEMENT_PERMISSION, 'lic_mgr_integration_help_page', 'lic_mgr_integration_help_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Emails', 'Emails', SLM_MANAGEMENT_PERMISSION, 'mcrpd_emails_menu', 'mcrpd_emails_menu_page' );
}

add_action( 'admin_init', 'mcrpd_export_licenses_to_csv' );
function mcrpd_export_licenses_to_csv() {
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'slm-main' ) {
		return;
	}
	if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'export_licenses' ) {
		return;
	}

	if ( ! current_user_can( SLM_MANAGEMENT_PERMISSION ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'slm' ) );
	}

	check_admin_referer( 'slm_export_licenses' );

	global $wpdb;
	$license_table = SLM_TBL_LICENSE_KEYS;
	$domain_table  = SLM_TBL_LIC_DOMAIN;

	if ( ! empty( $_REQUEST['s'] ) ) {
		$search_term = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
		$placeholder = '%' . $wpdb->esc_like( $search_term ) . '%';

		$query = $wpdb->prepare(
			"SELECT `lk`.* FROM `$license_table` `lk`
			LEFT JOIN `$domain_table` `rd` ON `lk`.`id` = `rd`.`lic_key_id`
			WHERE `lk`.`license_key` LIKE %s
			OR `lk`.`email` LIKE %s
			OR `lk`.`txn_id` LIKE %s
			OR `lk`.`first_name` LIKE %s
			OR `lk`.`last_name` LIKE %s
			OR `rd`.`registered_domain` LIKE %s
			GROUP BY `lk`.`id`
			ORDER BY `lk`.`id` DESC",
			$placeholder, $placeholder, $placeholder, $placeholder, $placeholder, $placeholder
		);
		$licenses = $wpdb->get_results( $query, ARRAY_A );
	} else {
		$licenses = $wpdb->get_results( "SELECT * FROM `$license_table` ORDER BY id DESC", ARRAY_A );
	}

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=licenses-export-' . date( 'Y-m-d' ) . '.csv' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$output = fopen( 'php://output', 'w' );

	// Add BOM for Excel UTF-8 compatibility
	fputs( $output, "\xEF\xBB\xBF" );

	// Output headers
	fputcsv( $output, array(
		'ID',
		'License Key',
		'Max Allowed Domains',
		'Status',
		'First Name',
		'Last Name',
		'Email',
		'Company Name',
		'Transaction ID',
		'Date Created',
		'Date Renewed',
		'Date Expiry',
		'Product Ref'
	) );

	if ( $licenses ) {
		foreach ( $licenses as $license ) {
			fputcsv( $output, array(
				$license['id'],
				$license['license_key'],
				$license['max_allowed_domains'],
				$license['lic_status'],
				$license['first_name'],
				$license['last_name'],
				$license['email'],
				$license['company_name'],
				$license['txn_id'],
				$license['date_created'],
				$license['date_renewed'],
				$license['date_expiry'],
				$license['product_ref']
			) );
		}
	}

	fclose( $output );
	exit;
}
