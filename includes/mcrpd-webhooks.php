<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends a webhook push notification to all registered domains of a license key.
 *
 * @param int    $license_id The database ID of the license.
 * @param string $new_status The new status of the license.
 * @param string $context    The context of the status change.
 */
function mcrpd_send_webhook_notifications( $license_id, $new_status, $context = '' ) {
	global $wpdb, $slm_debug_logger;

	$lk_table  = SLM_TBL_LICENSE_KEYS;
	$reg_table = SLM_TBL_LIC_DOMAIN;

	// 1. Get the license details
	$license = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $lk_table WHERE id = %d", $license_id ), OBJECT );
	if ( ! $license ) {
		return;
	}

	// 2. Get all registered domains
	$domains = $wpdb->get_results( $wpdb->prepare( "SELECT registered_domain FROM $reg_table WHERE lic_key_id = %d", $license_id ), OBJECT );
	if ( empty( $domains ) ) {
		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( "Webhook - No domains registered for license ID: $license_id. No webhooks sent." );
		}
		return;
	}

	$payload = array(
		'license_key' => $license->license_key,
		'status'      => $new_status,
		'product_ref' => $license->product_ref,
		'context'     => $context,
		'timestamp'   => time(),
	);

	$payload_json = json_encode( $payload );
	$signature    = hash_hmac( 'sha256', $payload_json, wp_salt( 'auth' ) );

	foreach ( $domains as $domain ) {
		$target_url = esc_url_raw( trailingslashit( $domain->registered_domain ) );
		$webhook_url = add_query_arg( 'mcrpd_webhook', 'license_status_changed', $target_url );

		if ( isset( $slm_debug_logger ) ) {
			$slm_debug_logger->log_debug( "Webhook - Sending push to: $webhook_url with status: $new_status (Context: $context)" );
		}

		// Fire-and-forget push
		wp_remote_post( $webhook_url, array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false, // Non-blocking
			'headers'     => array(
				'Content-Type'      => 'application/json',
				'X-MCRPD-Signature' => $signature,
			),
			'body'        => $payload_json,
			'cookies'     => array(),
			'sslverify'   => false,
		) );
	}
}

/**
 * Hook listeners
 */

// 1. Cron-job expiration
add_action( 'slm_license_key_expired', 'mcrpd_webhook_trigger_on_expiry' );
function mcrpd_webhook_trigger_on_expiry( $license_id ) {
	mcrpd_send_webhook_notifications( $license_id, 'expired', 'cron_expiry' );
}

// 2. WooCommerce subscription changes (renewals/expirations/cancellations)
add_action( 'woocommerce_subscription_status_updated', 'mcrpd_webhook_trigger_on_subscription_change', 20, 3 );
function mcrpd_webhook_trigger_on_subscription_change( $subscription, $new_status, $old_status ) {
	global $wpdb;
	
	$sub_id   = $subscription->get_id();
	$lk_table = SLM_TBL_LICENSE_KEYS;

	$licenses = $wpdb->get_results( $wpdb->prepare( "SELECT id, lic_status FROM $lk_table WHERE subscr_id = %d", $sub_id ), OBJECT );
	if ( ! empty( $licenses ) ) {
		foreach ( $licenses as $lic ) {
			mcrpd_send_webhook_notifications( $lic->id, $lic->lic_status, 'subscription_change' );
		}
	}
}

// 3. WooCommerce refunds
add_action( 'mcrpd_wc_license_refunded', 'mcrpd_webhook_trigger_on_refund', 10, 2 );
function mcrpd_webhook_trigger_on_refund( $license_id, $order_id ) {
	mcrpd_send_webhook_notifications( $license_id, 'blocked', 'refund' );
}

// 4. Manual admin edits
add_action( 'slm_add_edit_interface_save_record_processed', 'mcrpd_webhook_trigger_on_manual_edit' );
function mcrpd_webhook_trigger_on_manual_edit( $data ) {
	global $wpdb;
	$license_id = intval( $data['row_id'] );
	$lk_table   = SLM_TBL_LICENSE_KEYS;
	$status     = $wpdb->get_var( $wpdb->prepare( "SELECT lic_status FROM $lk_table WHERE id = %d", $license_id ) );
	if ( $status ) {
		mcrpd_send_webhook_notifications( $license_id, $status, 'manual_edit' );
	}
}
