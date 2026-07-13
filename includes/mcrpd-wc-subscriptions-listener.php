<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle new subscription activation (generates license)
 */
add_action( 'woocommerce_subscription_status_updated', 'mcrpd_wc_subscription_status_changed_listener', 10, 3 );
function mcrpd_wc_subscription_status_changed_listener( $subscription, $new_status, $old_status ) {
	global $wpdb;
	$slm_options = get_option( 'slm_plugin_options' );
	
	$sub_id = $subscription->get_id();
	
	if ( 'active' === $new_status ) {
		// Check if we need to generate licenses
		$items = $subscription->get_items();
		
		foreach ( $items as $item_id => $item ) {
			$product_id   = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			$target_id    = $variation_id ? $variation_id : $product_id;
			
			$enable_license = get_post_meta( $target_id, '_mcrpd_enable_license', true );
			
			if ( 'yes' === $enable_license ) {
				// Prevent duplicate generation for this item
				if ( wc_get_order_item_meta( $item_id, '_mcrpd_license_generated', true ) ) {
					continue;
				}
				
				$project_id = get_post_meta( $target_id, '_mcrpd_license_project', true );
				$domains    = get_post_meta( $target_id, '_mcrpd_license_domains', true );
				$quantity   = $item->get_quantity();
				
				if ( empty( $domains ) ) {
					$domains = isset( $slm_options['default_max_domains'] ) ? $slm_options['default_max_domains'] : 1;
				}
				
				$lic_key_prefix = isset( $slm_options['lic_prefix'] ) ? $slm_options['lic_prefix'] : '';
				
				// Expiration date from subscription
				$next_payment = $subscription->get_date( 'next_payment' );
				if ( empty( $next_payment ) ) {
					$next_payment = $subscription->get_date( 'end' );
				}
				$date_expiry = $next_payment ? date( 'Y-m-d', strtotime( $next_payment ) ) : '';
				
				$generated_keys = array();
				
				for ( $i = 0; $i < $quantity; $i++ ) {
					$license_key = uniqid( $lic_key_prefix );
					$license_key = apply_filters( 'slm_generate_license_key', $license_key );
					
					$fields = array(
						'license_key'         => $license_key,
						'lic_status'          => 'pending',
						'first_name'          => $subscription->get_billing_first_name(),
						'last_name'           => $subscription->get_billing_last_name(),
						'email'               => $subscription->get_billing_email(),
						'company_name'        => $subscription->get_billing_company(),
						'txn_id'              => $sub_id,
						'max_allowed_domains' => $domains,
						'date_created'        => date( 'Y-m-d' ),
						'date_renewed'        => $date_expiry,
						'date_expiry'         => $date_expiry,
						'product_ref'         => $project_id,
						'subscr_id'           => $sub_id,
						'user_ref'            => $subscription->get_user_id()
					);
					
					$tbl_name = SLM_TBL_LICENSE_KEYS;
					$result = $wpdb->insert( $tbl_name, $fields );
					
					if ( $result ) {
						$license_id = $wpdb->insert_id;
						$generated_keys[] = $license_key;
						// Store individual license reference in order item meta just in case
						wc_add_order_item_meta( $item_id, '_mcrpd_license_key_' . $i, $license_key );
						wc_add_order_item_meta( $item_id, '_mcrpd_license_id_' . $i, $license_id );
						
						do_action( 'mcrpd_wc_license_created', $license_id, $fields, $subscription );
					}
				}
				
				if ( ! empty( $generated_keys ) ) {
					wc_add_order_item_meta( $item_id, '_mcrpd_license_generated', 'yes' );
					
					// Send email if configured
					if ( ! empty( $slm_options['mcrpd_wc_enable_email_creation'] ) ) {
						mcrpd_wc_send_email_notification( 'creation', $subscription, $generated_keys, $item, $date_expiry );
					}
					
					// Add note to subscription
					$keys_str = implode( ', ', $generated_keys );
					$subscription->add_order_note( sprintf( __( 'Licenses generated for this cycle: %s', 'slm' ), $keys_str ) );
				}
			}
		}
	}
	
	// Handle state changes
	$lk_table = SLM_TBL_LICENSE_KEYS;
	$new_lic_status = '';
	
	if ( 'cancelled' === $new_status ) {
		$new_lic_status = 'pending';
	} elseif ( 'expired' === $new_status ) {
		$new_lic_status = 'expired';
		
		// Send expired email if enabled
		if ( ! empty( $slm_options['mcrpd_wc_enable_email_expired'] ) && 'expired' !== $old_status ) {
			$keys = $wpdb->get_col( $wpdb->prepare( "SELECT license_key FROM $lk_table WHERE subscr_id = %d", $sub_id ) );
			if ( ! empty( $keys ) ) {
				$items = $subscription->get_items();
				$item = reset( $items ); // Get first item for naming
				$date_expiry = $wpdb->get_var( $wpdb->prepare( "SELECT date_expiry FROM $lk_table WHERE subscr_id = %d LIMIT 1", $sub_id ) );
				mcrpd_wc_send_email_notification( 'expired', $subscription, $keys, $item, $date_expiry );
			}
		}
	} elseif ( 'on-hold' === $new_status ) {
		$new_lic_status = 'blocked';
	} elseif ( 'active' === $new_status && 'pending' !== $old_status ) { // Reactivation
		$new_lic_status = 'active';
	}
	
	if ( ! empty( $new_lic_status ) ) {
		$wpdb->update( 
			$lk_table, 
			array( 'lic_status' => $new_lic_status ), 
			array( 'subscr_id' => $sub_id ) 
		);
		$subscription->add_order_note( sprintf( __( 'Associated licenses status changed to: %s', 'slm' ), $new_lic_status ) );
	}
}

/**
 * Handle subscription renewal (extend expiration)
 */
add_action( 'woocommerce_subscription_renewal_payment_complete', 'mcrpd_wc_subscription_renewal', 10, 2 );
function mcrpd_wc_subscription_renewal( $subscription, $last_order ) {
	global $wpdb;
	
	$sub_id = $subscription->get_id();
	$next_payment = $subscription->get_date( 'next_payment' );
	
	if ( ! empty( $next_payment ) ) {
		$date_expiry = date( 'Y-m-d', strtotime( $next_payment ) );
		$lk_table = SLM_TBL_LICENSE_KEYS;
		
		$wpdb->update( 
			$lk_table, 
			array( 
				'date_expiry'  => $date_expiry,
				'date_renewed' => $date_expiry,
				'lic_status'   => 'active'
			), 
			array( 'subscr_id' => $sub_id ) 
		);
		
		$subscription->add_order_note( sprintf( __( 'Licenses renewed. New expiration date: %s', 'slm' ), $date_expiry ) );
		
		// Send renewal email if enabled
		$slm_options = get_option( 'slm_plugin_options' );
		if ( ! empty( $slm_options['mcrpd_wc_enable_email_renewal'] ) ) {
			$keys = $wpdb->get_col( $wpdb->prepare( "SELECT license_key FROM $lk_table WHERE subscr_id = %d", $sub_id ) );
			if ( ! empty( $keys ) ) {
				$items = $subscription->get_items();
				$item = reset( $items );
				mcrpd_wc_send_email_notification( 'renewal', $subscription, $keys, $item, $date_expiry );
			}
		}
	}
}

/**
 * Helper to send email
 */
function mcrpd_wc_send_email_notification( $type, $subscription, $keys, $item, $date_expiry ) {
	$slm_options = get_option( 'slm_plugin_options' );
	
	$subject_key = 'mcrpd_wc_email_subject_' . $type;
	$body_key    = 'mcrpd_wc_email_body_' . $type;
	
	$subject = isset( $slm_options[ $subject_key ] ) && ! empty( $slm_options[ $subject_key ] ) ? $slm_options[ $subject_key ] : '';
	$body    = isset( $slm_options[ $body_key ] ) && ! empty( $slm_options[ $body_key ] ) ? $slm_options[ $body_key ] : '';
	
	if ( empty( $subject ) || empty( $body ) ) {
		return;
	}
	
	$product = $item ? $item->get_product() : false;
	$project_name = '';
	if ( $product ) {
		$project_id = get_post_meta( $product->get_id(), '_mcrpd_license_project', true );
		$project_name = $project_id ? get_the_title( $project_id ) : '';
	}
	
	$keys_str = implode( '<br>', $keys );
	$customer_name = $subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name();
	
	$subs_url = '';
	if ( function_exists( 'wc_get_endpoint_url' ) ) {
		$subs_url = wc_get_endpoint_url( 'subscriptions', '', wc_get_page_permalink( 'myaccount' ) );
	}
	
	$replacements = array(
		'{license_key}'       => $keys_str,
		'{client_name}'       => $customer_name,
		'{client_email}'      => $subscription->get_billing_email(),
		'{order_id}'          => $subscription->get_parent_id() ? $subscription->get_parent_id() : $subscription->get_id(),
		'{project_name}'      => $project_name,
		'{product_name}'      => $item ? $item->get_name() : '',
		'{expiry_date}'       => $date_expiry,
		'{site_name}'         => get_bloginfo( 'name' ),
		'{subscriptions_url}' => $subs_url
	);
	
	$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
	$body    = str_replace( array_keys( $replacements ), array_values( $replacements ), $body );
	
	require_once WP_LICENSE_MANAGER_PATH . '/includes/mcrpd-email-template.php';
	
	$html_body = mcrpd_get_email_html( $body, $subject );
	$headers = array('Content-Type: text/html; charset=UTF-8');
	
	wp_mail( $subscription->get_billing_email(), wp_unslash( $subject ), $html_body, $headers );
}

/**
 * Handle WooCommerce refunds: blocks license keys and cancels subscriptions if fully refunded.
 */
add_action( 'woocommerce_order_refunded', 'mcrpd_wc_handle_refund', 10, 2 );
function mcrpd_wc_handle_refund( $order_id, $refund_id ) {
	global $wpdb;

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Only act if the order is fully refunded (remaining refund amount is 0 or less)
	if ( $order->get_remaining_refund_amount() > 0 ) {
		return;
	}

	$lk_table = SLM_TBL_LICENSE_KEYS;

	// Find subscriptions associated with this order
	$sub_ids = array();
	if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
		$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
		foreach ( $subscriptions as $subscription ) {
			$sub_ids[] = $subscription->get_id();
		}
	}

	// Build query to find licenses
	if ( ! empty( $sub_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $sub_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT id, license_key, subscr_id FROM $lk_table WHERE txn_id = %d OR subscr_id IN ($placeholders)",
			array_merge( array( $order_id ), $sub_ids ) // Correct format for prepare
		);
	} else {
		$sql = $wpdb->prepare( "SELECT id, license_key, subscr_id FROM $lk_table WHERE txn_id = %d", $order_id );
	}

	$licenses = $wpdb->get_results( $sql, OBJECT );

	if ( empty( $licenses ) ) {
		return;
	}

	$blocked_keys = array();

	foreach ( $licenses as $license ) {
		// Update license status to blocked
		$wpdb->update(
			$lk_table,
			array( 'lic_status' => 'blocked' ),
			array( 'id' => $license->id )
		);

		$blocked_keys[] = $license->license_key;

		// Trigger custom action hook so webhooks can fire
		do_action( 'mcrpd_wc_license_refunded', $license->id, $order_id );

		// If the license is linked to a subscription, cancel it
		if ( ! empty( $license->subscr_id ) && function_exists( 'wcs_get_subscription' ) ) {
			$subscription = wcs_get_subscription( $license->subscr_id );
			if ( $subscription && $subscription->has_status( array( 'active', 'on-hold', 'pending' ) ) ) {
				$subscription->update_status( 'cancelled', __( 'Subscription cancelled because the parent order was fully refunded.', 'slm' ) );
			}
		}
	}

	// Add order note
	if ( ! empty( $blocked_keys ) ) {
		$keys_str = implode( ', ', $blocked_keys );
		$order->add_order_note( sprintf( __( 'Associated license keys blocked due to full refund: %s', 'slm' ), $keys_str ) );
	}
}

