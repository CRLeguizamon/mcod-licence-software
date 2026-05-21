<?php

function wp_lic_mgr_add_licenses_menu() {
	global $wpdb;
	// initialise some variables.
	$id                      = '';
	$license_key             = '';
	$max_domains             = 1;
	$license_status          = '';
	$first_name              = '';
	$last_name               = '';
	$email                   = '';
	$company_name            = '';
	$txn_id                  = '';
	$reset_count             = '';
	$created_date            = '';
	$renewed_date            = '';
	$expiry_date             = '';
	$current_date            = ( date( 'Y-m-d' ) );
	$current_date_plus_1year = date( 'Y-m-d', strtotime( '+1 year' ) );
	$product_ref             = '';
	$subscr_id               = '';
        $user_ref                = '';

	$slm_options = get_option( 'slm_plugin_options' );

	echo '<div class="wrap slm-admin-wrap">';
	echo '<h2>Add/Edit Licenses</h2>';
	echo '<div id="poststuff"><div id="post-body">';

	// If product is being edited, grab current product info.
	if ( isset( $_GET['edit_record'] ) ) {
		$errors         = '';
		$id             = intval( wp_unslash( $_GET['edit_record'] ) );
		$lk_table       = SLM_TBL_LICENSE_KEYS;
		$sql_prep       = $wpdb->prepare( "SELECT * FROM $lk_table WHERE id = %s", $id );
		$record         = $wpdb->get_row( $sql_prep, OBJECT );
		$license_key    = $record->license_key;
		$max_domains    = $record->max_allowed_domains;
		$license_status = $record->lic_status;
		$first_name     = $record->first_name;
		$last_name      = $record->last_name;
		$email          = $record->email;
		$company_name   = $record->company_name;
		$txn_id         = $record->txn_id;
		$reset_count    = $record->manual_reset_count;
		$created_date   = $record->date_created;
		$renewed_date   = $record->date_renewed;
		$expiry_date    = $record->date_expiry;
		$product_ref    = $record->product_ref;
		$subscr_id      = $record->subscr_id;
                $user_ref      = $record->user_ref;
	}

	if ( isset( $_POST['save_record'] ) ) {

		// Check nonce.
		check_admin_referer( 'slm_add_edit_nonce_action', 'slm_add_edit_nonce_val' );

		do_action( 'slm_add_edit_interface_save_submission' );

		// TODO - do some validation.
		$license_key    = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
		$max_domains    = intval( wp_unslash( $_POST['max_allowed_domains'] ) );
		$license_status = sanitize_text_field( wp_unslash( $_POST['lic_status'] ) );
		$first_name     = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
		$last_name      = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
		$email          = sanitize_email( wp_unslash( $_POST['email'] ) );
		$company_name   = sanitize_text_field( wp_unslash( $_POST['company_name'] ) );
		$txn_id         = sanitize_text_field( wp_unslash( $_POST['txn_id'] ) );
		$reset_count    = sanitize_text_field( wp_unslash( $_POST['manual_reset_count'] ) );
		$created_date   = sanitize_text_field( wp_unslash( $_POST['date_created'] ) );
		$renewed_date   = sanitize_text_field( wp_unslash( $_POST['date_renewed'] ) );
		$expiry_date    = sanitize_text_field( wp_unslash( $_POST['date_expiry'] ) );
		$product_ref    = sanitize_text_field( wp_unslash( $_POST['product_ref'] ) );
		$subscr_id      = sanitize_text_field( wp_unslash( $_POST['subscr_id'] ) );
                $user_ref       = sanitize_text_field( wp_unslash( $_POST['user_ref'] ) );

		if ( empty( $created_date ) ) {
			$created_date = $current_date;
		}
		if ( empty( $renewed_date ) ) {
			$renewed_date = $current_date;
		}
		if ( empty( $expiry_date ) ) {
			$expiry_date = $current_date_plus_1year;
		}

		// Save the entry to the database.
		$fields                        = array();
		$fields['license_key']         = $license_key;
		$fields['max_allowed_domains'] = $max_domains;
		$fields['lic_status']          = $license_status;
		$fields['first_name']          = $first_name;
		$fields['last_name']           = $last_name;
		$fields['email']               = $email;
		$fields['company_name']        = $company_name;
		$fields['txn_id']              = $txn_id;
		$fields['manual_reset_count']  = $reset_count;
		$fields['date_created']        = $created_date;
		$fields['date_renewed']        = $renewed_date;
		$fields['date_expiry']         = $expiry_date;
		$fields['product_ref']         = $product_ref;
		$fields['subscr_id']           = $subscr_id;
                $fields['user_ref']            = $user_ref;

		$id       = isset( $_POST['edit_record'] ) ? intval( wp_unslash( $_POST['edit_record'] ) ) : '';
		$lk_table = SLM_TBL_LICENSE_KEYS;
		if ( empty( $id ) ) {// Insert into database.
			$result = $wpdb->insert( $lk_table, $fields );
			$id     = $wpdb->insert_id;
			if ( false === $result ) {
				$errors .= __( 'Record could not be inserted into the database!', 'slm' );
			}
		} else { // Update record.
			$where   = array( 'id' => $id );
			$updated = $wpdb->update( $lk_table, $fields, $where );
			if ( false === $updated ) {
				// TODO - log error.
				$errors .= __( 'Update of the license key table failed!', 'slm' );
			}
		}

		if ( empty( $errors ) ) {
			$message = 'Record successfully saved!';
			echo '<div id="message" class="updated fade"><p>';
			echo esc_html( $message );
			echo '</p></div>';
		} else {
			echo '<div id="message" class="error">' . esc_html( $errors ) . '</div>';
		}

		$data = array(
			'row_id' => $id,
			'key'    => $license_key,
		);
		do_action( 'slm_add_edit_interface_save_record_processed', $data );

	}

	?>
	You can add a new license or edit an existing one from this interface.
	<br /><br />

	<div class="postbox">
		<h3 class="hndle"><label for="title">License Details </label></h3>
		<div class="inside">

			<form method="post">
				<?php wp_nonce_field( 'slm_add_edit_nonce_action', 'slm_add_edit_nonce_val' ); ?>
				<table class="form-table">

					<?php
					if ( '' !== $id ) {
						echo '<input name="edit_record" type="hidden" value="' . esc_attr( $id ) . '" />';
					} else {
						if ( ! isset( $editing_record ) ) {// Create an empty object.
							$editing_record = new stdClass();
						}
						// Auto generate unique key.
						$lic_key_prefix = isset($slm_options['lic_prefix']) && ! empty( $slm_options['lic_prefix'] ) ? $slm_options['lic_prefix'] : '';
						if ( !empty($lic_key_prefix) ) {
							$license_key = uniqid( $lic_key_prefix );
						} else {
							$license_key = uniqid();
						}
                        $license_key = apply_filters( 'slm_generate_license_key', $license_key );
					}
					?>

					<tr valign="top">
						<th scope="row">License Key</th>
						<td><input name="license_key" type="text" id="license_key" value="<?php echo esc_attr( $license_key ); ?>" size="30" />
							<br/>The unique license key. When adding a new record it automatically generates a unique key in this field for you. You can change this value to customize the key if you like.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Maximum Allowed Domains</th>
						<td><input name="max_allowed_domains" type="text" id="max_allowed_domains" value="<?php echo esc_attr( $max_domains ); ?>" size="5" /><br/>Number of domains/installs in which this license can be used.</td>
					</tr>

					<tr valign="top">
						<th scope="row">License Status</th>
						<td>
							<select name="lic_status">
								<option value="pending"<?php echo 'pending' === $license_status ? ' selected="selected"' : ''; ?>>Pending</option>
								<option value="active"<?php echo 'active' === $license_status ? ' selected="selected"' : ''; ?>>Active</option>
								<option value="blocked"<?php echo 'blocked' === $license_status ? ' selected="selected"' : ''; ?>>Blocked</option>
								<option value="expired"<?php echo 'expired' === $license_status ? ' selected="selected"' : ''; ?>>Expired</option>
							</select>
						</td></tr>

					<?php
					if ( '' !== $id ) :
						global $wpdb;
						$reg_table   = SLM_TBL_LIC_DOMAIN;
						$sql_prep    = $wpdb->prepare( "SELECT * FROM `$reg_table` WHERE `lic_key_id` = %s", $id );
						$reg_domains = $wpdb->get_results( $sql_prep, OBJECT );
						?>
						<tr valign="top">
							<th scope="row">Registered Domains</th>
							<td>
								<?php if ( count( $reg_domains ) > 0 ) { ?>
									<div id="reg_del_msg" style="display: none;"></div>
									<div class="domain-licenses">
										<table cellpadding="0" cellspacing="0" class="domain-license-table">
											<?php
											$count = 0;
											$tpl   = '<a class="slm-remove-domain-btn del" data-domain-id="%s" data-nonce="%s" data-lic-id="' . $id . '" title="' . __( 'Delete domain', 'slm' ) . '" href="#">&times;</a>';
											foreach ( $reg_domains as $reg_domain ) :
												?>
												<tr <?php echo ( $count % 2 ) ? 'class="alternate"' : ''; ?>>
													<td class="remove-domain">
													<?php
													echo wp_kses(
														sprintf(
															$tpl,
															esc_attr( $reg_domain->id ),
															esc_attr( wp_create_nonce( sprintf( 'slm_delete_domain_lic_%s_id_%s', $id, $reg_domain->id ) ) )
														),
														array(
															'a' => array(
																'class' => array(),
																'href' => array(),
																'title' => array(),
																'data-domain-id' => array(),
																'data-lic-id' => array(),
																'data-nonce' => array(),
															),
														)
													);
													?>
													</td>
													<td><?php echo esc_html( $reg_domain->registered_domain ); ?></td>
												</tr>
												<?php
												$count++;
											endforeach;
											?>
										</table>
									</div>
									<?php
								} else {
									echo esc_html__( 'No domains activated.', 'slm' );
								}
								?>
							</td>
						</tr>
					<?php endif; ?>

					<tr valign="top">
						<th scope="row">First Name</th>
						<td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" size="20" /><br/>License user's first name</td>
					</tr>

					<tr valign="top">
						<th scope="row">Last Name</th>
						<td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" size="20" /><br/>License user's last name</td>
					</tr>

					<tr valign="top">
						<th scope="row">Email Address</th>
						<td><input name="email" type="text" id="email" value="<?php echo esc_attr( $email ); ?>" size="30" /><br/>License user's email address</td>
					</tr>

					<tr valign="top">
						<th scope="row">Company Name</th>
						<td><input name="company_name" type="text" id="company_name" value="<?php echo esc_attr( $company_name ); ?>" size="30" /><br/>License user's company name</td>
					</tr>

					<tr valign="top">
						<th scope="row">Unique Transaction ID</th>
						<td><input name="txn_id" type="text" id="txn_id" value="<?php echo esc_attr( $txn_id ); ?>" size="30" /><br/>The unique transaction ID associated with this license key</td>
					</tr>

					<tr valign="top">
						<th scope="row">Manual Reset Count</th>
						<td><input name="manual_reset_count" type="text" id="manual_reset_count" value="<?php echo esc_attr( $reset_count ); ?>" size="6" />
							<br/>The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Date Created</th>
						<td><input name="date_created" type="text" id="date_created" class="wplm_pick_date" value="<?php echo esc_attr( $created_date ); ?>" size="10" />
							<br/>Creation date of license.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Date Renewed</th>
						<td><input name="date_renewed" type="text" id="date_renewed" class="wplm_pick_date" value="<?php echo esc_attr( $renewed_date ); ?>" size="10" />
							<br/>Renewal date of license.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Date of Expiry</th>
						<td><input name="date_expiry" type="text" id="date_expiry" class="wplm_pick_date" value="<?php echo esc_attr( $expiry_date ); ?>" size="10" />
							<br/>Expiry date of license.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Project</th>
						<td>
							<select name="product_ref" id="product_ref">
								<option value="">-- Select a Project --</option>
								<?php
								$projects = get_posts( array( 'post_type' => 'mcrpd_project', 'posts_per_page' => -1, 'post_status' => 'any' ) );
								foreach ( $projects as $project ) {
									$selected = ( $product_ref == $project->ID ) ? ' selected="selected"' : '';
									echo '<option value="' . esc_attr( $project->ID ) . '"' . $selected . '>' . esc_html( $project->post_title ) . '</option>';
								}
								?>
							</select>
							<br/>The project that this license applies to.</td>
					</tr>

					<tr valign="top">
						<th scope="row">Subscriber ID</th>
						<td><input name="subscr_id" type="text" id="subscr_id" value="<?php echo esc_attr( $subscr_id ); ?>" size="50" />
							<br/>The Subscriber ID (if any). Can be useful if you are using the license key with a recurring payment plan.</td>
					</tr>

					<tr valign="top">
						<th scope="row">User Reference</th>
						<td><input name="user_ref" type="text" id="user_ref" value="<?php echo esc_attr( $user_ref ); ?>" size="30" />
							<br/>The User ID of the user that this license applies to (if any). Can be useful if you want to connect your licenses to some kind of member/user management system.</td>
					</tr>

				</table>

				<?php
				$data = array(
					'row_id' => $id,
					'key'    => $license_key,
				);
				do_action( 'slm_add_edit_interface_above_submit', $data );
				?>

				<div class="submit">
					<input type="submit" class="button-primary" name="save_record" value="Save Record" />
				</div>
			</form>
		</div></div>
	<a href="admin.php?page=<?php echo esc_attr( SLM_MAIN_MENU_SLUG ); ?>" class="button">Manage Licenses</a><br /><br />
	</div></div>
	</div>
	<?php
}
