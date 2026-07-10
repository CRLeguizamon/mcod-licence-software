<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mcrpd_emails_menu_page() {
	if ( isset( $_POST['mcrpd_save_emails'] ) ) {
		check_admin_referer( 'mcrpd_emails_nonce_action', 'mcrpd_emails_nonce_val' );

		$curr_opts = get_option( 'slm_plugin_options' );
		
		$options = array(
			// Creation
			'mcrpd_wc_enable_email_creation'  => isset( $_POST['mcrpd_wc_enable_email_creation'] ) ? '1' : '',
			'mcrpd_wc_email_subject_creation' => isset( $_POST['mcrpd_wc_email_subject_creation'] ) ? sanitize_text_field( wp_unslash( $_POST['mcrpd_wc_email_subject_creation'] ) ) : '',
			'mcrpd_wc_email_body_creation'    => isset( $_POST['mcrpd_wc_email_body_creation'] ) ? wp_unslash( $_POST['mcrpd_wc_email_body_creation'] ) : '',
			
			// Expired
			'mcrpd_wc_enable_email_expired'   => isset( $_POST['mcrpd_wc_enable_email_expired'] ) ? '1' : '',
			'mcrpd_wc_email_subject_expired'  => isset( $_POST['mcrpd_wc_email_subject_expired'] ) ? sanitize_text_field( wp_unslash( $_POST['mcrpd_wc_email_subject_expired'] ) ) : '',
			'mcrpd_wc_email_body_expired'     => isset( $_POST['mcrpd_wc_email_body_expired'] ) ? wp_unslash( $_POST['mcrpd_wc_email_body_expired'] ) : '',
			
			// Renewal
			'mcrpd_wc_enable_email_renewal'   => isset( $_POST['mcrpd_wc_enable_email_renewal'] ) ? '1' : '',
			'mcrpd_wc_email_subject_renewal'  => isset( $_POST['mcrpd_wc_email_subject_renewal'] ) ? sanitize_text_field( wp_unslash( $_POST['mcrpd_wc_email_subject_renewal'] ) ) : '',
			'mcrpd_wc_email_body_renewal'     => isset( $_POST['mcrpd_wc_email_body_renewal'] ) ? wp_unslash( $_POST['mcrpd_wc_email_body_renewal'] ) : '',
			
			// Template Settings
			'mcrpd_wc_email_header_color'     => isset( $_POST['mcrpd_wc_email_header_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['mcrpd_wc_email_header_color'] ) ) : '',
			'mcrpd_wc_email_logo_url'         => isset( $_POST['mcrpd_wc_email_logo_url'] ) ? sanitize_url( wp_unslash( $_POST['mcrpd_wc_email_logo_url'] ) ) : '',
			'mcrpd_wc_email_footer_text'      => isset( $_POST['mcrpd_wc_email_footer_text'] ) ? wp_unslash( $_POST['mcrpd_wc_email_footer_text'] ) : '',
		);

		$options = array_merge( $curr_opts, $options );
		update_option( 'slm_plugin_options', $options );

		echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Email settings saved successfully.', 'slm' ) . '</p></div>';
	}

	$options = get_option( 'slm_plugin_options' );
	
	// Defaults
	$def_creation_subj = 'Your license key for {product_name}';
	$def_creation_body = "Hello {client_name},\n\nThank you for your purchase. Here is your license key:\n\n<div class=\"license-box\">{license_key}</div>\n\nThis license will expire on: <strong>{expiry_date}</strong>.\n\nYou can manage your subscription from <a href=\"{subscriptions_url}\">your account</a>.";
	
	$def_expired_subj  = 'Your license for {product_name} has expired';
	$def_expired_body  = "Hello {client_name},\n\nWe inform you that your license key <strong>{license_key}</strong> has expired on {expiry_date}.\n\nTo continue receiving updates and support, please renew your subscription from <a href=\"{subscriptions_url}\">your account</a>.";
	
	$def_renewal_subj  = 'License renewal confirmation';
	$def_renewal_body  = "Hello {client_name},\n\nThank you for renewing! Your license <strong>{license_key}</strong> has been successfully extended.\n\nThe new expiration date is: <strong>{expiry_date}</strong>.\n\nYou can manage your subscription from <a href=\"{subscriptions_url}\">your account</a>.";

	$def_footer_text   = '&copy; {year} <a href="{site_url}">{site_name}</a>. All rights reserved.';

	// Variables for output
	$en_creation = ! empty( $options['mcrpd_wc_enable_email_creation'] ) ? 'checked="checked"' : '';
	$sub_creation = isset( $options['mcrpd_wc_email_subject_creation'] ) && ! empty( $options['mcrpd_wc_email_subject_creation'] ) ? $options['mcrpd_wc_email_subject_creation'] : $def_creation_subj;
	$body_creation = isset( $options['mcrpd_wc_email_body_creation'] ) && ! empty( $options['mcrpd_wc_email_body_creation'] ) ? $options['mcrpd_wc_email_body_creation'] : $def_creation_body;
	
	$en_expired = ! empty( $options['mcrpd_wc_enable_email_expired'] ) ? 'checked="checked"' : '';
	$sub_expired = isset( $options['mcrpd_wc_email_subject_expired'] ) && ! empty( $options['mcrpd_wc_email_subject_expired'] ) ? $options['mcrpd_wc_email_subject_expired'] : $def_expired_subj;
	$body_expired = isset( $options['mcrpd_wc_email_body_expired'] ) && ! empty( $options['mcrpd_wc_email_body_expired'] ) ? $options['mcrpd_wc_email_body_expired'] : $def_expired_body;
	
	$en_renewal = ! empty( $options['mcrpd_wc_enable_email_renewal'] ) ? 'checked="checked"' : '';
	$sub_renewal = isset( $options['mcrpd_wc_email_subject_renewal'] ) && ! empty( $options['mcrpd_wc_email_subject_renewal'] ) ? $options['mcrpd_wc_email_subject_renewal'] : $def_renewal_subj;
	$body_renewal = isset( $options['mcrpd_wc_email_body_renewal'] ) && ! empty( $options['mcrpd_wc_email_body_renewal'] ) ? $options['mcrpd_wc_email_body_renewal'] : $def_renewal_body;

	$header_color = isset( $options['mcrpd_wc_email_header_color'] ) && ! empty( $options['mcrpd_wc_email_header_color'] ) ? $options['mcrpd_wc_email_header_color'] : '#2b8c49';
	$logo_url     = isset( $options['mcrpd_wc_email_logo_url'] ) ? $options['mcrpd_wc_email_logo_url'] : '';
	$footer_text  = isset( $options['mcrpd_wc_email_footer_text'] ) && ! empty( $options['mcrpd_wc_email_footer_text'] ) ? $options['mcrpd_wc_email_footer_text'] : $def_footer_text;

	require_once WP_LICENSE_MANAGER_PATH . '/includes/mcrpd-email-template.php';
	
	// Helper to generate preview HTML
	function mcrpd_get_preview_html( $body ) {
		$dummy = array(
			'{license_key}'       => 'ABC12-DEF34-GHI56-JKL78',
			'{client_name}'       => 'Juan Pérez',
			'{client_email}'      => 'juan@example.com',
			'{order_id}'          => '#12345',
			'{project_name}'      => 'Mi Super Plugin',
			'{product_name}'      => 'Suscripción Anual - Plugin XYZ',
			'{expiry_date}'       => date_i18n( get_option('date_format'), strtotime('+1 year') ),
			'{site_name}'         => get_bloginfo( 'name' ),
			'{subscriptions_url}' => '#'
		);
		$parsed_body = str_replace( array_keys( $dummy ), array_values( $dummy ), $body );
		return mcrpd_get_email_html( $parsed_body, get_bloginfo('name') );
	}
	
	?>
	<div class="wrap slm-admin-wrap mcrpd-emails-wrap">
		<h2><?php esc_html_e( 'Email Settings (WooCommerce Subscriptions)', 'slm' ); ?></h2>
		
		<style>
			.mcrpd-emails-wrap details {
				margin-top: 15px;
				background: #f9fafb;
				border: 1px solid #e5e7eb;
				border-radius: 6px;
				padding: 10px 15px;
			}
			.mcrpd-emails-wrap details summary {
				font-weight: 600;
				cursor: pointer;
				color: #2b8c49;
				outline: none;
			}
			.mcrpd-emails-wrap iframe.preview-frame {
				width: 100%;
				height: 500px;
				border: none;
				margin-top: 15px;
				background: #fff;
				border-radius: 6px;
				box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
			}
		</style>
		
		<p><?php esc_html_e( 'Configure the email notifications that will be sent to customers regarding their licenses. These emails use a modern HTML template and include dynamic variables.', 'slm' ); ?></p>
		
		<div class="slm-info-card">
			<h4><?php esc_html_e( 'Available Variables', 'slm' ); ?></h4>
			<p>
				<code>{license_key}</code>, <code>{client_name}</code>, <code>{client_email}</code>, <code>{order_id}</code>, <br>
				<code>{project_name}</code>, <code>{product_name}</code>, <code>{expiry_date}</code>, <code>{site_name}</code>, <br>
				<code>{subscriptions_url}</code> (<?php esc_html_e( 'Link to My Account > Subscriptions', 'slm' ); ?>)
			</p>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'mcrpd_emails_nonce_action', 'mcrpd_emails_nonce_val' ); ?>
			
			<!-- Template Customization -->
			<div class="postbox">
				<h3 class="hndle"><label><?php esc_html_e( 'Template Customization', 'slm' ); ?></label></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Header Background Color', 'slm' ); ?></th>
							<td>
								<input type="color" name="mcrpd_wc_email_header_color" value="<?php echo esc_attr( $header_color ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Header Logo URL', 'slm' ); ?></th>
							<td>
								<input type="url" name="mcrpd_wc_email_logo_url" value="<?php echo esc_url( $logo_url ); ?>" style="width: 100%; max-width: 600px;" placeholder="https://..." />
								<p class="description"><?php esc_html_e( 'If provided, this image will be shown instead of the site title in the header.', 'slm' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Footer Text', 'slm' ); ?></th>
							<td>
								<textarea name="mcrpd_wc_email_footer_text" rows="3" style="width: 100%; max-width: 600px;"><?php echo esc_textarea( $footer_text ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Available variables: {year}, {site_name}, {site_url}', 'slm' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- 1. Creación de Licencia -->
			<div class="postbox">
				<h3 class="hndle"><label><?php esc_html_e( 'License Creation Email', 'slm' ); ?></label></h3>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Sent when a subscription is activated for the first time and the license key is generated.', 'slm' ); ?></p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Enable email', 'slm' ); ?></th>
							<td><input name="mcrpd_wc_enable_email_creation" type="checkbox" <?php echo $en_creation; ?> value="1"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email subject', 'slm' ); ?></th>
							<td><input type="text" name="mcrpd_wc_email_subject_creation" value="<?php echo esc_attr( $sub_creation ); ?>" style="width: 100%; max-width: 600px;" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email body (Supports basic HTML)', 'slm' ); ?></th>
							<td>
								<textarea name="mcrpd_wc_email_body_creation" rows="6" style="width: 100%; max-width: 600px;"><?php echo esc_textarea( $body_creation ); ?></textarea>
								<details>
									<summary><?php esc_html_e( 'Preview Template', 'slm' ); ?></summary>
									<iframe class="preview-frame" srcdoc="<?php echo esc_attr( mcrpd_get_preview_html( $body_creation ) ); ?>"></iframe>
								</details>
							</td>
						</tr>
					</table>
				</div>
			</div>
			
			<!-- 2. Licencia Vencida -->
			<div class="postbox">
				<h3 class="hndle"><label><?php esc_html_e( 'Expired License Email', 'slm' ); ?></label></h3>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Sent when a subscription changes to "Expired" status.', 'slm' ); ?></p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Enable email', 'slm' ); ?></th>
							<td><input name="mcrpd_wc_enable_email_expired" type="checkbox" <?php echo $en_expired; ?> value="1"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email subject', 'slm' ); ?></th>
							<td><input type="text" name="mcrpd_wc_email_subject_expired" value="<?php echo esc_attr( $sub_expired ); ?>" style="width: 100%; max-width: 600px;" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email body', 'slm' ); ?></th>
							<td>
								<textarea name="mcrpd_wc_email_body_expired" rows="6" style="width: 100%; max-width: 600px;"><?php echo esc_textarea( $body_expired ); ?></textarea>
								<details>
									<summary><?php esc_html_e( 'Preview Template', 'slm' ); ?></summary>
									<iframe class="preview-frame" srcdoc="<?php echo esc_attr( mcrpd_get_preview_html( $body_expired ) ); ?>"></iframe>
								</details>
							</td>
						</tr>
					</table>
				</div>
			</div>
			
			<!-- 3. Aviso de Renovación -->
			<div class="postbox">
				<h3 class="hndle"><label><?php esc_html_e( 'Successful Renewal Notice', 'slm' ); ?></label></h3>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Sent when the renewal payment is completed and the license expiration date is extended.', 'slm' ); ?></p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Enable email', 'slm' ); ?></th>
							<td><input name="mcrpd_wc_enable_email_renewal" type="checkbox" <?php echo $en_renewal; ?> value="1"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email subject', 'slm' ); ?></th>
							<td><input type="text" name="mcrpd_wc_email_subject_renewal" value="<?php echo esc_attr( $sub_renewal ); ?>" style="width: 100%; max-width: 600px;" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email body', 'slm' ); ?></th>
							<td>
								<textarea name="mcrpd_wc_email_body_renewal" rows="6" style="width: 100%; max-width: 600px;"><?php echo esc_textarea( $body_renewal ); ?></textarea>
								<details>
									<summary><?php esc_html_e( 'Preview Template', 'slm' ); ?></summary>
									<iframe class="preview-frame" srcdoc="<?php echo esc_attr( mcrpd_get_preview_html( $body_renewal ) ); ?>"></iframe>
								</details>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="submit">
				<input type="submit" class="button-primary" name="mcrpd_save_emails" value="<?php esc_attr_e( 'Save Emails', 'slm' ); ?>" />
			</div>
		</form>
	</div>
	<?php
}
