<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode to display the logged-in user's license keys.
 * Usage: [mcrpd_my_licenses]
 */
add_shortcode( 'mcrpd_my_licenses', 'mcrpd_handle_my_licenses_shortcode' );

function mcrpd_handle_my_licenses_shortcode( $args ) {
	if ( ! is_user_logged_in() ) {
		return '<div class="mcrpd-my-licenses-error">' . esc_html__( 'You must be logged in to view your licenses.', 'slm' ) . '</div>';
	}

	global $wpdb;
	
	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;
	$user_email   = $current_user->user_email;
	
	$lk_table = SLM_TBL_LICENSE_KEYS;
	$sql = $wpdb->prepare(
		"SELECT * FROM $lk_table WHERE user_ref = %s OR email = %s ORDER BY id DESC",
		$user_id,
		$user_email
	);
	
	$records = $wpdb->get_results( $sql, OBJECT );
	
	if ( empty( $records ) ) {
		return '<div class="mcrpd-my-licenses-empty">' . esc_html__( 'Could not find any licenses for your account.', 'slm' ) . '</div>';
	}
	
	$output = '<div class="mcrpd-my-licenses-container">';
	$output .= '<table class="mcrpd-my-licenses-table" style="width: 100%; border-collapse: collapse;">';
	$output .= '<thead><tr>';
	$output .= '<th style="border: 1px solid #ccc; padding: 8px;">' . esc_html__( 'Project', 'slm' ) . '</th>';
	$output .= '<th style="border: 1px solid #ccc; padding: 8px;">' . esc_html__( 'License Key', 'slm' ) . '</th>';
	$output .= '<th style="border: 1px solid #ccc; padding: 8px;">' . esc_html__( 'Status', 'slm' ) . '</th>';
	$output .= '<th style="border: 1px solid #ccc; padding: 8px;">' . esc_html__( 'Expires', 'slm' ) . '</th>';
	$output .= '</tr></thead><tbody>';
	
	foreach ( $records as $record ) {
		$project_name = '';
		if ( ! empty( $record->product_ref ) ) {
			$project_name = get_the_title( $record->product_ref );
		}
		
		$expiry = $record->date_expiry;
		if ( '0000-00-00' === $expiry || empty( $expiry ) ) {
			$expiry = __( 'Never', 'slm' );
		} else {
			$expiry = date_i18n( get_option( 'date_format' ), strtotime( $expiry ) );
		}
		
		$output .= '<tr>';
		$output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html( $project_name ) . '</td>';
		$output .= '<td style="border: 1px solid #ccc; padding: 8px;"><code>' . esc_html( $record->license_key ) . '</code></td>';
		$output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html( ucfirst( $record->lic_status ) ) . '</td>';
		$output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html( $expiry ) . '</td>';
		$output .= '</tr>';
	}
	
	$output .= '</tbody></table></div>';
	
	return $output;
}
