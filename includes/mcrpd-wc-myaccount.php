<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Register Endpoint
add_action( 'init', 'mcrpd_wc_add_licenses_endpoint' );
function mcrpd_wc_add_licenses_endpoint() {
	$options = get_option( 'slm_plugin_options' );
	if ( ! empty( $options['mcrpd_wc_enable_myaccount'] ) ) {
		add_rewrite_endpoint( 'mcrpd-licenses', EP_ROOT | EP_PAGES );
	}
}

// 2. Add to My Account Menu
add_filter( 'woocommerce_account_menu_items', 'mcrpd_wc_licenses_menu_item', 15 );
function mcrpd_wc_licenses_menu_item( $items ) {
	$options = get_option( 'slm_plugin_options' );
	if ( ! empty( $options['mcrpd_wc_enable_myaccount'] ) ) {
		// Insert after 'downloads' if possible, else just add it.
		$new_items = array();
		foreach ( $items as $key => $item ) {
			$new_items[ $key ] = $item;
			if ( 'downloads' === $key ) {
				$new_items['mcrpd-licenses'] = __( 'Licenses', 'slm' );
			}
		}
		if ( ! isset( $new_items['mcrpd-licenses'] ) ) {
			$new_items['mcrpd-licenses'] = __( 'Licenses', 'slm' );
		}
		return $new_items;
	}
	return $items;
}

// 3. Render Endpoint Content
add_action( 'woocommerce_account_mcrpd-licenses_endpoint', 'mcrpd_wc_licenses_endpoint_content' );
function mcrpd_wc_licenses_endpoint_content() {
	global $wpdb;
	
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$current_user = wp_get_current_user();
	$user_email   = $current_user->user_email;
	
	$lk_table = SLM_TBL_LICENSE_KEYS;
	$licenses = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $lk_table WHERE user_ref = %s OR email = %s ORDER BY id DESC",
		$user_id,
		$user_email
	) );
	
	if ( isset( $_GET['view'] ) ) {
		$view_id = intval( $_GET['view'] );
		$license = null;
		foreach ( $licenses as $lic ) {
			if ( $lic->id == $view_id ) {
				$license = $lic;
				break;
			}
		}
		
		if ( $license ) {
			mcrpd_wc_render_license_detail( $license );
		} else {
			echo '<div class="woocommerce-Message woocommerce-Message--error woocommerce-error">';
			echo __( 'License not found or you do not have permission.', 'slm' );
			echo '</div>';
		}
		return;
	}
	
	if ( empty( $licenses ) ) {
		echo '<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">';
		echo __( 'You do not have any licenses yet.', 'slm' );
		echo '</div>';
		return;
	}
	
	echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table mcrpd-licenses-table">';
	echo '<thead><tr>';
	echo '<th class="mcrpd-project-col"><span class="nobr">' . __( 'Project', 'slm' ) . '</span></th>';
	echo '<th class="mcrpd-key-col"><span class="nobr">' . __( 'License', 'slm' ) . '</span></th>';
	echo '<th class="mcrpd-status-col"><span class="nobr">' . __( 'Status', 'slm' ) . '</span></th>';
	echo '<th class="mcrpd-expiry-col"><span class="nobr">' . __( 'Expires', 'slm' ) . '</span></th>';
	echo '<th class="mcrpd-actions-col"><span class="nobr">' . __( 'Actions', 'slm' ) . '</span></th>';
	echo '</tr></thead><tbody>';
	
	foreach ( $licenses as $license ) {
		$project_name = '';
		if ( ! empty( $license->product_ref ) ) {
			$project_name = get_the_title( $license->product_ref );
		}
		
		$masked_key = substr( $license->license_key, 0, 8 ) . '****' . substr( $license->license_key, -4 );
		$status_class = 'mcrpd-status-' . sanitize_html_class( strtolower( $license->lic_status ) );
		
		$expiry = $license->date_expiry;
		if ( '0000-00-00' === $expiry || empty( $expiry ) ) {
			$expiry = __( 'Never', 'slm' );
		} else {
			$expiry = date_i18n( get_option( 'date_format' ), strtotime( $expiry ) );
		}
		
		$view_url = esc_url( add_query_arg( 'view', $license->id, wc_get_endpoint_url( 'mcrpd-licenses' ) ) );
		
		echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">';
		echo '<td class="woocommerce-orders-table__cell mcrpd-project-col" data-title="' . esc_attr__( 'Project', 'slm' ) . '">' . esc_html( $project_name ) . '</td>';
		echo '<td class="woocommerce-orders-table__cell mcrpd-key-col" data-title="' . esc_attr__( 'License', 'slm' ) . '"><code>' . esc_html( $masked_key ) . '</code></td>';
		echo '<td class="woocommerce-orders-table__cell mcrpd-status-col" data-title="' . esc_attr__( 'Status', 'slm' ) . '"><span class="mcrpd-badge ' . $status_class . '">' . esc_html( ucfirst( $license->lic_status ) ) . '</span></td>';
		echo '<td class="woocommerce-orders-table__cell mcrpd-expiry-col" data-title="' . esc_attr__( 'Expires', 'slm' ) . '">' . esc_html( $expiry ) . '</td>';
		echo '<td class="woocommerce-orders-table__cell mcrpd-actions-col" data-title="' . esc_attr__( 'Actions', 'slm' ) . '"><a href="' . $view_url . '" class="woocommerce-button button view">' . __( 'View', 'slm' ) . '</a></td>';
		echo '</tr>';
	}
	
	echo '</tbody></table>';
}

function mcrpd_wc_render_license_detail( $license ) {
	$project_name = '';
	if ( ! empty( $license->product_ref ) ) {
		$project_name = get_the_title( $license->product_ref );
	}
	
	$status_class = 'mcrpd-status-' . sanitize_html_class( strtolower( $license->lic_status ) );
	$expiry = $license->date_expiry;
	if ( '0000-00-00' === $expiry || empty( $expiry ) ) {
		$expiry = __( 'Never', 'slm' );
	} else {
		$expiry = date_i18n( get_option( 'date_format' ), strtotime( $expiry ) );
	}
	$created = date_i18n( get_option( 'date_format' ), strtotime( $license->date_created ) );
	
	global $wpdb;
	$reg_table   = SLM_TBL_LIC_DOMAIN;
	$domains = $wpdb->get_results( $wpdb->prepare( "SELECT id, registered_domain FROM $reg_table WHERE lic_key_id = %d", $license->id ) );
	$domain_count = count( $domains );
	
	$back_url = esc_url( wc_get_endpoint_url( 'mcrpd-licenses' ) );
	
	echo '<div class="mcrpd-license-detail">';
	echo '<p><a href="' . $back_url . '">&larr; ' . __( 'Back to Licenses', 'slm' ) . '</a></p>';
	
	echo '<div class="mcrpd-license-card">';
	echo '<div class="mcrpd-license-header">';
	echo '<h3>' . esc_html( $project_name ) . '</h3>';
	echo '<span class="mcrpd-badge ' . $status_class . '">' . esc_html( ucfirst( $license->lic_status ) ) . '</span>';
	echo '</div>';
	
	echo '<div class="mcrpd-license-key-box">';
	echo '<label>' . __( 'License Key', 'slm' ) . '</label>';
	echo '<div class="mcrpd-key-row">';
	echo '<input type="text" readonly value="' . esc_attr( $license->license_key ) . '" id="mcrpd_copy_target" />';
	echo '<button type="button" class="mcrpd-copy-btn" data-clipboard-target="#mcrpd_copy_target">' . __( 'Copy', 'slm' ) . '</button>';
	echo '</div>';
	echo '<span class="mcrpd-copy-feedback" style="display:none; color: #2b8c49; font-size: 13px; margin-top: 5px;">' . __( 'Copied!', 'slm' ) . '</span>';
	echo '</div>';
	
	echo '<div class="mcrpd-license-info-grid">';
	echo '<div><strong>' . __( 'Activated Domains', 'slm' ) . '</strong><br>' . $domain_count . ' / ' . esc_html( $license->max_allowed_domains ) . '</div>';
	echo '<div><strong>' . __( 'Creation Date', 'slm' ) . '</strong><br>' . esc_html( $created ) . '</div>';
	echo '<div><strong>' . __( 'Expiration Date', 'slm' ) . '</strong><br>' . esc_html( $expiry ) . '</div>';
	if ( ! empty( $license->subscr_id ) ) {
		$sub_url = esc_url( wc_get_endpoint_url( 'view-subscription', $license->subscr_id, wc_get_page_permalink( 'myaccount' ) ) );
		echo '<div><strong>' . __( 'Linked Subscription', 'slm' ) . '</strong><br><a href="' . $sub_url . '">#' . esc_html( $license->subscr_id ) . '</a></div>';
	}
	echo '</div>';
	
	if ( $domain_count > 0 ) {
		echo '<div class="mcrpd-license-domains">';
		echo '<h4>' . __( 'Registered Domains', 'slm' ) . '</h4>';
		echo '<ul>';
		foreach ( $domains as $dom ) {
			$nonce = wp_create_nonce( sprintf( 'mcrpd_deactivate_domain_%s_%s', $license->id, $dom->id ) );
			echo '<li style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #eee;">';
			echo '<span>' . esc_html( $dom->registered_domain ) . '</span>';
			echo '<a href="#" class="mcrpd-deactivate-domain-btn button delete" data-domain-id="' . esc_attr( $dom->id ) . '" data-lic-id="' . esc_attr( $license->id ) . '" data-nonce="' . esc_attr( $nonce ) . '" style="padding: 4px 10px; font-size: 12px; margin-left: 10px; line-height: 1; background: #ef4444; color: #fff; border: none; border-radius: 4px; text-decoration: none;">' . __( 'Deactivate', 'slm' ) . '</a>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	
	echo '</div>'; // close card
	echo '</div>'; // close detail wrapper
}
