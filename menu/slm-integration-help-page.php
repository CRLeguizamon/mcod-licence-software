<?php

function lic_mgr_integration_help_menu() {

	$options                 = get_option( 'slm_plugin_options' );
	$creation_secret_key     = isset($options['lic_creation_secret']) && !empty($options['lic_creation_secret']) ? $options['lic_creation_secret'] : '';
	$secret_verification_key = isset($options['lic_verification_secret']) && !empty($options['lic_verification_secret']) ? $options['lic_verification_secret'] : '';

	echo '<div class="wrap slm-admin-wrap">';
	echo '<h2>' . __( 'API Integration Documentation', 'slm' ) . '</h2>';
	echo '<div id="poststuff"><div id="post-body">';

	echo '<h3>' . __( 'Key Variables For Your Installation', 'slm' ) . '</h3>';

	$api_query_post_url = SLM_SITE_HOME_URL;
	echo '<table class="form-table" style="max-width: 800px; margin-bottom: 30px;">';
	echo '<tr><th style="width:250px;">' . __( 'API POST URL', 'slm' ) . '</th><td><input type="text" readonly value="' . esc_url( $api_query_post_url ) . '" style="width:100%;" /></td></tr>';
	echo '<tr><th>' . __( 'Verification Secret Key', 'slm' ) . '<br/><small>' . __( '(Activate/Deactivate/Check)', 'slm' ) . '</small></th><td><input type="text" readonly value="' . esc_html( $secret_verification_key ) . '" style="width:100%;" /></td></tr>';
	echo '<tr><th>' . __( 'Creation Secret Key', 'slm' ) . '<br/><small>' . __( '(Create New)', 'slm' ) . '</small></th><td><input type="text" readonly value="' . esc_html( $creation_secret_key ) . '" style="width:100%;" /></td></tr>';
	echo '</table>';

	echo '<h3>' . __( 'API Endpoints (cURL Examples)', 'slm' ) . '</h3>';
	?>
	<details class="slm-api-doc">
		<summary>1. <?php _e( 'Create New License', 'slm' ); ?> (slm_create_new)</summary>
		<p><?php echo wp_kses_post( __( 'Generates a new license key in the system. Use the <strong>Creation Secret Key</strong>.', 'slm' ) ); ?></p>
		<pre>curl -X POST <?php echo esc_url( $api_query_post_url ); ?> \
     -d "slm_action=slm_create_new" \
     -d "secret_key=<?php echo esc_html( $creation_secret_key ); ?>" \
     -d "first_name=John" \
     -d "last_name=Doe" \
     -d "email=john@example.com" \
     -d "max_allowed_domains=1"</pre>
	</details>

	<details class="slm-api-doc">
		<summary>2. <?php _e( 'Activate License', 'slm' ); ?> (slm_activate)</summary>
		<p><?php echo wp_kses_post( __( 'Activates a license key for a specific domain. Use the <strong>Verification Secret Key</strong>.', 'slm' ) ); ?></p>
		<pre>curl -X POST <?php echo esc_url( $api_query_post_url ); ?> \
     -d "slm_action=slm_activate" \
     -d "secret_key=<?php echo esc_html( $secret_verification_key ); ?>" \
     -d "license_key=YOUR_LICENSE_KEY" \
     -d "registered_domain=example.com" \
     -d "item_reference=Project_ID"</pre>
	</details>

	<details class="slm-api-doc">
		<summary>3. <?php _e( 'Deactivate License', 'slm' ); ?> (slm_deactivate)</summary>
		<p><?php echo wp_kses_post( __( 'Deactivates a license key for a specific domain. If the license reaches 0 domains, its status will automatically revert to pending. Use the <strong>Verification Secret Key</strong>.', 'slm' ) ); ?></p>
		<pre>curl -X POST <?php echo esc_url( $api_query_post_url ); ?> \
     -d "slm_action=slm_deactivate" \
     -d "secret_key=<?php echo esc_html( $secret_verification_key ); ?>" \
     -d "license_key=YOUR_LICENSE_KEY" \
     -d "registered_domain=example.com"</pre>
	</details>

	<details class="slm-api-doc">
		<summary>4. <?php _e( 'Check License Status', 'slm' ); ?> (slm_check)</summary>
		<p><?php echo wp_kses_post( __( 'Retrieves the current status and details of a license key. Use the <strong>Verification Secret Key</strong>.', 'slm' ) ); ?></p>
		<pre>curl -X POST <?php echo esc_url( $api_query_post_url ); ?> \
     -d "slm_action=slm_check" \
     -d "secret_key=<?php echo esc_html( $secret_verification_key ); ?>" \
     -d "license_key=YOUR_LICENSE_KEY"</pre>
	</details>
	<?php

	echo '</div></div>';
	echo '</div>';
}
