<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the HTML template for MCOD emails.
 *
 * @param string $content The main body content.
 * @param string $title The title for the header.
 * @return string HTML string.
 */
function mcrpd_get_email_html( $content, $title = '' ) {
	$site_name = get_bloginfo( 'name' );
	$site_url  = site_url();
	$year      = date( 'Y' );
	
	$options = get_option( 'slm_plugin_options' );
	$header_color = isset( $options['mcrpd_wc_email_header_color'] ) && ! empty( $options['mcrpd_wc_email_header_color'] ) ? $options['mcrpd_wc_email_header_color'] : '#2b8c49';
	$logo_url     = isset( $options['mcrpd_wc_email_logo_url'] ) ? $options['mcrpd_wc_email_logo_url'] : '';
	$footer_text  = isset( $options['mcrpd_wc_email_footer_text'] ) && ! empty( $options['mcrpd_wc_email_footer_text'] ) ? $options['mcrpd_wc_email_footer_text'] : '&copy; {year} <a href="{site_url}">{site_name}</a>. All rights reserved.';
	
	$footer_vars = array(
		'{year}'      => $year,
		'{site_name}' => $site_name,
		'{site_url}'  => $site_url,
	);
	$footer_text = str_replace( array_keys( $footer_vars ), array_values( $footer_vars ), $footer_text );
	
	if ( empty( $title ) ) {
		$title = $site_name;
	}
	
	ob_start();
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo esc_html( $title ); ?></title>
		<style>
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
				background-color: #f9fafb;
				color: #374151;
				line-height: 1.6;
				margin: 0;
				padding: 0;
			}
			.email-wrapper {
				width: 100%;
				background-color: #f9fafb;
				padding: 40px 20px;
				box-sizing: border-box;
			}
			.email-container {
				max-width: 600px;
				margin: 0 auto;
				background-color: #ffffff;
				border-radius: 8px;
				box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
				overflow: hidden;
			}
			.email-header {
				background-color: <?php echo esc_attr( $header_color ); ?>;
				padding: 30px 40px;
				text-align: center;
			}
			.email-header h1 {
				color: #ffffff;
				margin: 0;
				font-size: 24px;
				font-weight: 600;
				letter-spacing: 0.5px;
			}
			.email-body {
				padding: 40px;
			}
			.email-body p {
				margin-top: 0;
				margin-bottom: 20px;
				font-size: 15px;
			}
			.email-body p:last-child {
				margin-bottom: 0;
			}
			.email-body a {
				color: <?php echo esc_attr( $header_color ); ?>;
				text-decoration: none;
			}
			.email-body a:hover {
				text-decoration: underline;
			}
			.email-button {
				display: inline-block;
				background-color: <?php echo esc_attr( $header_color ); ?>;
				color: #ffffff !important;
				text-decoration: none !important;
				padding: 12px 24px;
				border-radius: 6px;
				font-weight: 600;
				margin-top: 10px;
				margin-bottom: 10px;
			}
			.email-footer {
				background-color: #f3f4f6;
				padding: 20px 40px;
				text-align: center;
				font-size: 13px;
				color: #6b7280;
			}
			.email-footer a {
				color: #4b5563;
			}
			.license-box {
				background-color: #f3f4f6;
				border: 1px solid #e5e7eb;
				border-radius: 6px;
				padding: 15px;
				margin: 20px 0;
				font-family: monospace;
				font-size: 16px;
				color: #111827;
				text-align: center;
			}
		</style>
	</head>
	<body>
		<table class="email-wrapper" cellpadding="0" cellspacing="0" role="presentation">
			<tr>
				<td align="center">
					<table class="email-container" cellpadding="0" cellspacing="0" role="presentation" width="100%">
						<tr>
							<td class="email-header">
								<?php if ( ! empty( $logo_url ) ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" style="max-height: 80px; width: auto;" />
								<?php else : ?>
									<h1><?php echo esc_html( $title ); ?></h1>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td class="email-body">
								<?php echo wp_kses_post( wpautop( wp_unslash( $content ) ) ); ?>
							</td>
						</tr>
						<tr>
							<td class="email-footer">
								<?php echo wp_kses_post( $footer_text ); ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
	</html>
	<?php
	return ob_get_clean();
}
