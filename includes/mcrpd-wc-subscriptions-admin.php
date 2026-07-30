<?php
/**
 * WooCommerce Subscriptions — Admin Product Data Integration.
 *
 * Adds a "Licence" tab/panel to Simple Subscription products and licence
 * fields to Variable Subscription variations, allowing the store owner to
 * configure per-product licence generation settings.
 *
 * @package SoftwareLicenseManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ──────────────────────────────────────────────────────────────
// 1. Add "Licence" tab for Simple Subscriptions
// ──────────────────────────────────────────────────────────────
add_filter( 'woocommerce_product_data_tabs', 'mcrpd_wc_add_subscription_license_tab' );
function mcrpd_wc_add_subscription_license_tab( $tabs ) {
	$tabs['mcrpd_license'] = array(
		'label'    => __( 'Licence', 'slm' ),
		'target'   => 'mcrpd_license_product_data',
		'class'    => array( 'show_if_subscription' ), // Only show for Simple Subscription.
		'priority' => 65,
	);
	return $tabs;
}

// ──────────────────────────────────────────────────────────────
// 2. Panel content for Simple Subscriptions
// ──────────────────────────────────────────────────────────────
add_action( 'woocommerce_product_data_panels', 'mcrpd_wc_subscription_license_panel' );
function mcrpd_wc_subscription_license_panel() {
	global $post;
	$post_id = $post->ID;

	// Read saved values (with sensible defaults).
	$enable_license  = get_post_meta( $post_id, '_mcrpd_enable_license', true );
	$license_project = get_post_meta( $post_id, '_mcrpd_license_project', true );
	$license_domains = get_post_meta( $post_id, '_mcrpd_license_domains', true );

	// Default to 1 domain when the value has never been set.
	if ( '' === $license_domains || false === $license_domains ) {
		$license_domains = '1';
	}

	echo '<div id="mcrpd_license_product_data" class="panel woocommerce_options_panel">';

	echo '<div class="options_group">';
	woocommerce_wp_checkbox( array(
		'id'          => '_mcrpd_enable_license',
		'label'       => __( 'Enable license', 'slm' ),
		'description' => __( 'Generates a license key when the subscription is activated.', 'slm' ),
		'desc_tip'    => true,
		'value'       => $enable_license,
	) );

	$projects = get_posts( array(
		'post_type'      => 'mcrpd_project',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	) );

	$project_options = array( '' => __( '-- Select Project --', 'slm' ) );
	foreach ( $projects as $project ) {
		$project_options[ $project->ID ] = $project->post_title;
	}

	woocommerce_wp_select( array(
		'id'          => '_mcrpd_license_project',
		'label'       => __( 'License Project', 'slm' ),
		'options'     => $project_options,
		'description' => __( 'Select which project this license belongs to.', 'slm' ),
		'desc_tip'    => true,
		'value'       => $license_project,
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_mcrpd_license_domains',
		'label'       => __( 'Amount of Domains', 'slm' ),
		'description' => __( 'Number of domains allowed for this license.', 'slm' ),
		'type'        => 'number',
		'value'       => $license_domains,
		'desc_tip'    => true,
	) );
	echo '</div>';

	echo '</div>';
}

// ──────────────────────────────────────────────────────────────
// 3. Save panel content for Simple Subscriptions
// ──────────────────────────────────────────────────────────────
add_action( 'woocommerce_process_product_meta', 'mcrpd_wc_save_subscription_license_meta' );
function mcrpd_wc_save_subscription_license_meta( $post_id ) {
	// Enable license checkbox.
	$enable_license = isset( $_POST['_mcrpd_enable_license'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_mcrpd_enable_license', $enable_license );

	// License project.
	if ( isset( $_POST['_mcrpd_license_project'] ) ) {
		$project = sanitize_text_field( wp_unslash( $_POST['_mcrpd_license_project'] ) );
		update_post_meta( $post_id, '_mcrpd_license_project', $project );
	}

	// License domains — default to 1 when empty.
	$domains = isset( $_POST['_mcrpd_license_domains'] )
		? absint( wp_unslash( $_POST['_mcrpd_license_domains'] ) )
		: 1;
	if ( $domains < 1 ) {
		$domains = 1;
	}
	update_post_meta( $post_id, '_mcrpd_license_domains', $domains );
}

// ──────────────────────────────────────────────────────────────
// 4. Add fields to Variable Subscriptions (Variations)
// ──────────────────────────────────────────────────────────────
add_action( 'woocommerce_product_after_variable_attributes', 'mcrpd_wc_variation_license_fields', 10, 3 );
function mcrpd_wc_variation_license_fields( $loop, $variation_data, $variation ) {
	$variation_id = $variation->ID;

	$enable_license  = get_post_meta( $variation_id, '_mcrpd_enable_license', true );
	$license_project = get_post_meta( $variation_id, '_mcrpd_license_project', true );
	$license_domains = get_post_meta( $variation_id, '_mcrpd_license_domains', true );

	// Default to 1 domain when the value has never been set.
	if ( '' === $license_domains || false === $license_domains ) {
		$license_domains = '1';
	}

	$projects = get_posts( array(
		'post_type'      => 'mcrpd_project',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	) );

	$project_options = array( '' => __( '-- Select Project --', 'slm' ) );
	foreach ( $projects as $project ) {
		$project_options[ $project->ID ] = $project->post_title;
	}

	echo '<div class="options_group form-row form-row-full mcrpd-variation-license" style="border-top: 1px solid #eee; padding-top: 10px;">';
	echo '<h4>' . esc_html__( 'License Settings', 'slm' ) . '</h4>';

	woocommerce_wp_checkbox( array(
		'id'            => "_mcrpd_enable_license[{$loop}]",
		'wrapper_class' => 'form-row form-row-full',
		'label'         => __( 'Enable license', 'slm' ),
		'description'   => __( 'Generates a license key when the subscription is activated.', 'slm' ),
		'value'         => $enable_license,
	) );

	woocommerce_wp_select( array(
		'id'            => "_mcrpd_license_project[{$loop}]",
		'wrapper_class' => 'form-row form-row-full',
		'label'         => __( 'License Project', 'slm' ),
		'options'       => $project_options,
		'value'         => $license_project,
	) );

	woocommerce_wp_text_input( array(
		'id'            => "_mcrpd_license_domains[{$loop}]",
		'wrapper_class' => 'form-row form-row-full',
		'label'         => __( 'Amount of Domains', 'slm' ),
		'type'          => 'number',
		'value'         => $license_domains,
	) );

	echo '</div>';
}

// ──────────────────────────────────────────────────────────────
// 5. Save fields for Variable Subscriptions (Variations)
// ──────────────────────────────────────────────────────────────
add_action( 'woocommerce_save_product_variation', 'mcrpd_wc_save_variation_license_meta', 10, 2 );
function mcrpd_wc_save_variation_license_meta( $variation_id, $i ) {
	// Enable license checkbox.
	$enable_license = isset( $_POST['_mcrpd_enable_license'][ $i ] ) ? 'yes' : 'no';
	update_post_meta( $variation_id, '_mcrpd_enable_license', $enable_license );

	// License project.
	if ( isset( $_POST['_mcrpd_license_project'][ $i ] ) ) {
		$project = sanitize_text_field( wp_unslash( $_POST['_mcrpd_license_project'][ $i ] ) );
		update_post_meta( $variation_id, '_mcrpd_license_project', $project );
	}

	// License domains — default to 1 when empty.
	$domains = isset( $_POST['_mcrpd_license_domains'][ $i ] )
		? absint( wp_unslash( $_POST['_mcrpd_license_domains'][ $i ] ) )
		: 1;
	if ( $domains < 1 ) {
		$domains = 1;
	}
	update_post_meta( $variation_id, '_mcrpd_license_domains', $domains );
}
