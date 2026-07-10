<?php
/**
 * Project Releases and Metadata Management
 *
 * Handles metaboxes for Project CPT, allowing admins to upload .zip releases,
 * set the stable version, tested, requires, requires_php, and product reference.
 *
 * @package MCOD Software License Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register metaboxes for mcrpd_project CPT.
 */
function mcrpd_add_project_metaboxes() {
	add_meta_box(
		'mcrpd_project_settings',
		__( 'Project Update & API Settings', 'slm' ),
		'mcrpd_render_project_settings_metabox',
		'mcrpd_project',
		'normal',
		'high'
	);

	add_meta_box(
		'mcrpd_project_releases',
		__( 'Project Releases (.zip)', 'slm' ),
		'mcrpd_render_project_releases_metabox',
		'mcrpd_project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'mcrpd_add_project_metaboxes' );

/**
 * Enqueue scripts and styles for CPT edit page.
 */
function mcrpd_enqueue_project_edit_assets( $hook ) {
	global $post;
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && 'mcrpd_project' === $post->post_type ) {
		wp_enqueue_media();
		// Admin styles for our custom table and layout
		wp_add_inline_style( 'post', "
			.mcrpd-form-table th { width: 200px; text-align: left; padding: 10px 5px; }
			.mcrpd-form-table td { padding: 10px 5px; }
			.mcrpd-releases-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
			.mcrpd-releases-table th, .mcrpd-releases-table td { border: 1px solid #ccd0d4; padding: 8px 10px; text-align: left; }
			.mcrpd-releases-table tr.is-stable { background: #f0f6fc; font-weight: bold; }
			.mcrpd-badge-stable { background: #007cba; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; text-transform: uppercase; }
			.mcrpd-add-release-box { background: #fafafa; border: 1px dashed #ccd0d4; padding: 15px; margin-top: 20px; border-radius: 4px; }
			.mcrpd-add-release-box h4 { margin-top: 0; border-bottom: 1px solid #ccd0d4; padding-bottom: 8px; }
		" );
	}
}
add_action( 'admin_enqueue_scripts', 'mcrpd_enqueue_project_edit_assets' );

/**
 * Render Project Update Settings Metabox.
 */
function mcrpd_render_project_settings_metabox( $post ) {
	wp_nonce_field( 'mcrpd_save_project_settings', 'mcrpd_project_settings_nonce' );

	$product_ref  = get_post_meta( $post->ID, 'mcrpd_product_ref', true );
	$tested       = get_post_meta( $post->ID, 'mcrpd_tested_up_to', true );
	$requires     = get_post_meta( $post->ID, 'mcrpd_requires_wp', true );
	$requires_php = get_post_meta( $post->ID, 'mcrpd_requires_php', true );
	$stable_ver   = get_post_meta( $post->ID, 'mcrpd_stable_version', true );

	// Fetch releases to show in stable selection
	$releases = get_post_meta( $post->ID, 'mcrpd_releases', true );
	$releases = is_array( $releases ) ? $releases : array();
	?>
	<table class="form-table mcrpd-form-table">
		<tr>
			<th><label for="mcrpd_product_ref"><?php _e( 'Product Reference', 'slm' ); ?></label></th>
			<td>
				<input type="text" id="mcrpd_product_ref" name="mcrpd_product_ref" value="<?php echo esc_attr( $product_ref ); ?>" class="regular-text" required />
				<p class="description"><?php _e( 'Must match the Item Reference/Product Ref of your plugin (e.g. "COD License").', 'slm' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="mcrpd_stable_version"><?php _e( 'Stable Version', 'slm' ); ?></label></th>
			<td>
				<?php if ( empty( $releases ) ) : ?>
					<input type="text" id="mcrpd_stable_version" name="mcrpd_stable_version" value="<?php echo esc_attr( $stable_ver ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'Manually define stable version or add releases below to select from them.', 'slm' ); ?></p>
				<?php else : ?>
					<select id="mcrpd_stable_version" name="mcrpd_stable_version" class="regular-text">
						<option value=""><?php _e( '-- Select Stable Version --', 'slm' ); ?></option>
						<?php foreach ( $releases as $rel ) : ?>
							<option value="<?php echo esc_attr( $rel['version'] ); ?>" <?php selected( $stable_ver, $rel['version'] ); ?>>
								<?php echo esc_html( $rel['version'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><label for="mcrpd_tested_up_to"><?php _e( 'Tested Up To (WP Version)', 'slm' ); ?></label></th>
			<td>
				<input type="text" id="mcrpd_tested_up_to" name="mcrpd_tested_up_to" value="<?php echo esc_attr( $tested ); ?>" placeholder="e.g. 6.9" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="mcrpd_requires_wp"><?php _e( 'Requires WP Version', 'slm' ); ?></label></th>
			<td>
				<input type="text" id="mcrpd_requires_wp" name="mcrpd_requires_wp" value="<?php echo esc_attr( $requires ); ?>" placeholder="e.g. 5.0" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="mcrpd_requires_php"><?php _e( 'Requires PHP Version', 'slm' ); ?></label></th>
			<td>
				<input type="text" id="mcrpd_requires_php" name="mcrpd_requires_php" value="<?php echo esc_attr( $requires_php ); ?>" placeholder="e.g. 8.0" class="regular-text" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Render Project Releases Metabox.
 */
function mcrpd_render_project_releases_metabox( $post ) {
	$releases = get_post_meta( $post->ID, 'mcrpd_releases', true );
	$releases = is_array( $releases ) ? $releases : array();
	$stable_version = get_post_meta( $post->ID, 'mcrpd_stable_version', true );

	// Sort releases by version/date (newest first)
	usort( $releases, function( $a, $b ) {
		return version_compare( $b['version'], $a['version'] );
	} );
	?>
	<div class="mcrpd-releases-wrapper">
		<h3><?php _e( 'Active Releases', 'slm' ); ?></h3>
		<?php if ( empty( $releases ) ) : ?>
			<p class="description"><?php _e( 'No releases uploaded yet for this project.', 'slm' ); ?></p>
		<?php else : ?>
			<table class="mcrpd-releases-table">
				<thead>
					<tr>
						<th><?php _e( 'Version', 'slm' ); ?></th>
						<th><?php _e( 'File / ZIP Link', 'slm' ); ?></th>
						<th><?php _e( 'Upload Date', 'slm' ); ?></th>
						<th><?php _e( 'Changelog', 'slm' ); ?></th>
						<th><?php _e( 'Actions', 'slm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $releases as $index => $rel ) : 
						$is_stable = ( $rel['version'] === $stable_version );
						$file_url = wp_get_attachment_url( $rel['file_id'] );
						if ( ! $file_url ) {
							$file_url = $rel['file_url']; // Fallback
						}
						?>
						<tr class="<?php echo $is_stable ? 'is-stable' : ''; ?>">
							<td>
								<?php echo esc_html( $rel['version'] ); ?>
								<?php if ( $is_stable ) : ?>
									<span class="mcrpd-badge-stable"><?php _e( 'Stable', 'slm' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank">
									<?php echo esc_html( basename( $file_url ) ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $rel['date'] ); ?></td>
							<td><?php echo nl2br( esc_html( $rel['changelog'] ) ); ?></td>
							<td>
								<?php if ( ! $is_stable ) : ?>
									<button type="submit" name="mcrpd_set_stable" value="<?php echo esc_attr( $rel['version'] ); ?>" class="button button-secondary button-small">
										<?php _e( 'Set Stable', 'slm' ); ?>
									</button>
								<?php endif; ?>
								<button type="submit" name="mcrpd_delete_release" value="<?php echo esc_attr( $rel['version'] ); ?>" class="button button-link-delete button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this release?', 'slm' ); ?>');" style="color: #b32d2e; text-decoration: none; margin-left: 8px;">
									<?php _e( 'Delete', 'slm' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<div class="mcrpd-add-release-box">
			<h4><?php _e( 'Add New Release', 'slm' ); ?></h4>
			<table class="form-table mcrpd-form-table" style="margin: 0;">
				<tr>
					<th style="width: 150px;"><label for="mcrpd_new_version"><?php _e( 'Version', 'slm' ); ?></label></th>
					<td>
						<input type="text" id="mcrpd_new_version" name="mcrpd_new_version" placeholder="e.g. 4.1.0.13" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th><label for="mcrpd_new_zip_url"><?php _e( 'ZIP File', 'slm' ); ?></label></th>
					<td>
						<input type="hidden" id="mcrpd_new_zip_id" name="mcrpd_new_zip_id" value="" />
						<input type="text" id="mcrpd_new_zip_url" name="mcrpd_new_zip_url" value="" class="regular-text" placeholder="<?php _e( 'Select or upload ZIP file', 'slm' ); ?>" readonly />
						<button type="button" id="mcrpd_upload_zip_btn" class="button button-secondary"><?php _e( 'Upload/Select ZIP', 'slm' ); ?></button>
					</td>
				</tr>
				<tr>
					<th><label for="mcrpd_new_changelog"><?php _e( 'Changelog', 'slm' ); ?></label></th>
					<td>
						<textarea id="mcrpd_new_changelog" name="mcrpd_new_changelog" rows="4" class="large-text" placeholder="<?php _e( 'What is new in this version...', 'slm' ); ?>"></textarea>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<label>
							<input type="checkbox" name="mcrpd_new_is_stable" value="1" checked />
							<?php _e( 'Set as Stable version immediately', 'slm' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function($){
		var file_frame;
		$('#mcrpd_upload_zip_btn').on('click', function(e){
			e.preventDefault();
			if (file_frame) {
				file_frame.open();
				return;
			}
			file_frame = wp.media({
				title: '<?php esc_attr_e( 'Select or Upload Plugin ZIP', 'slm' ); ?>',
				button: {
					text: '<?php esc_attr_e( 'Use this file', 'slm' ); ?>'
				},
				multiple: false,
				library: {
					type: 'application/zip'
				}
			});
			file_frame.on('select', function(){
				var attachment = file_frame.state().get('selection').first().toJSON();
				$('#mcrpd_new_zip_id').val(attachment.id);
				$('#mcrpd_new_zip_url').val(attachment.url);
			});
			file_frame.open();
		});
	});
	</script>
	<?php
}

/**
 * Save meta fields and process releases actions on save_post.
 */
function mcrpd_save_project_data( $post_id ) {
	// Verify nonce
	if ( ! isset( $_POST['mcrpd_project_settings_nonce'] ) || ! wp_verify_nonce( $_POST['mcrpd_project_settings_nonce'], 'mcrpd_save_project_settings' ) ) {
		return;
	}

	// Avoid autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// 1. Save main settings
	if ( isset( $_POST['mcrpd_product_ref'] ) ) {
		update_post_meta( $post_id, 'mcrpd_product_ref', sanitize_text_field( wp_unslash( $_POST['mcrpd_product_ref'] ) ) );
	}
	if ( isset( $_POST['mcrpd_tested_up_to'] ) ) {
		update_post_meta( $post_id, 'mcrpd_tested_up_to', sanitize_text_field( wp_unslash( $_POST['mcrpd_tested_up_to'] ) ) );
	}
	if ( isset( $_POST['mcrpd_requires_wp'] ) ) {
		update_post_meta( $post_id, 'mcrpd_requires_wp', sanitize_text_field( wp_unslash( $_POST['mcrpd_requires_wp'] ) ) );
	}
	if ( isset( $_POST['mcrpd_requires_php'] ) ) {
		update_post_meta( $post_id, 'mcrpd_requires_php', sanitize_text_field( wp_unslash( $_POST['mcrpd_requires_php'] ) ) );
	}
	if ( isset( $_POST['mcrpd_stable_version'] ) ) {
		update_post_meta( $post_id, 'mcrpd_stable_version', sanitize_text_field( wp_unslash( $_POST['mcrpd_stable_version'] ) ) );
	}

	// Load existing releases
	$releases = get_post_meta( $post_id, 'mcrpd_releases', true );
	$releases = is_array( $releases ) ? $releases : array();

	// 2. Process Actions: Delete Release
	if ( isset( $_POST['mcrpd_delete_release'] ) ) {
		$delete_ver = sanitize_text_field( wp_unslash( $_POST['mcrpd_delete_release'] ) );
		foreach ( $releases as $index => $rel ) {
			if ( $rel['version'] === $delete_ver ) {
				// Optionally delete attachment if we want to clean space, but keeping for safety.
				unset( $releases[ $index ] );
				break;
			}
		}
		// Reset keys
		$releases = array_values( $releases );
		update_post_meta( $post_id, 'mcrpd_releases', $releases );

		// If we deleted the stable version, clear it
		$stable_ver = get_post_meta( $post_id, 'mcrpd_stable_version', true );
		if ( $stable_ver === $delete_ver ) {
			update_post_meta( $post_id, 'mcrpd_stable_version', '' );
		}
	}

	// 3. Process Actions: Set Stable Release
	if ( isset( $_POST['mcrpd_set_stable'] ) ) {
		$new_stable = sanitize_text_field( wp_unslash( $_POST['mcrpd_set_stable'] ) );
		update_post_meta( $post_id, 'mcrpd_stable_version', $new_stable );
	}

	// 4. Process Actions: Add New Release
	if ( ! empty( $_POST['mcrpd_new_version'] ) && ! empty( $_POST['mcrpd_new_zip_id'] ) ) {
		$new_ver      = sanitize_text_field( wp_unslash( $_POST['mcrpd_new_version'] ) );
		$new_zip_id   = intval( $_POST['mcrpd_new_zip_id'] );
		$new_zip_url  = esc_url_raw( wp_unslash( $_POST['mcrpd_new_zip_url'] ) );
		$new_changelog = sanitize_textarea_field( wp_unslash( $_POST['mcrpd_new_changelog'] ) );

		// Avoid duplicate versions
		$exists = false;
		foreach ( $releases as $rel ) {
			if ( $rel['version'] === $new_ver ) {
				$exists = true;
				break;
			}
		}

		if ( ! $exists ) {
			$releases[] = array(
				'version'   => $new_ver,
				'file_id'   => $new_zip_id,
				'file_url'  => $new_zip_url,
				'date'      => current_time( 'mysql' ),
				'changelog' => $new_changelog,
			);

			update_post_meta( $post_id, 'mcrpd_releases', $releases );

			// If stable checkbox checked or no stable version set, make this stable
			$stable_ver = get_post_meta( $post_id, 'mcrpd_stable_version', true );
			if ( ! empty( $_POST['mcrpd_new_is_stable'] ) || empty( $stable_ver ) ) {
				update_post_meta( $post_id, 'mcrpd_stable_version', $new_ver );
			}
		}
	}
}
add_action( 'save_post', 'mcrpd_save_project_data' );

/**
 * Filter the upload directory and filename for Project attachments to secure them.
 *
 * Security strategy (server-agnostic, works on Apache, Nginx, LiteSpeed, IIS, etc.):
 * 1. Files are stored in a dedicated folder: wp-content/uploads/mcrpd-secure-releases/
 * 2. Files are renamed to random hashes with .bin extension (unguessable URLs)
 * 3. .htaccess blocks direct access on Apache/LiteSpeed (extra layer, not required)
 * 4. index.php prevents directory listing on any server
 * 5. Downloads are ONLY served through the signed-token API endpoint
 */
function mcrpd_upload_prefilter( $file ) {
	// Check if this upload is for a mcrpd_project post
	$is_project = false;
	if ( isset( $_REQUEST['post_id'] ) ) {
		$post_id = intval( wp_unslash( $_REQUEST['post_id'] ) );
		if ( get_post_type( $post_id ) === 'mcrpd_project' ) {
			$is_project = true;
		}
	}

	if ( $is_project ) {
		// Just apply our custom upload directory; renaming is deferred to postfilter
		// to avoid MIME/extension mismatch errors during WordPress validation.
		add_filter( 'upload_dir', 'mcrpd_custom_upload_dir' );
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'mcrpd_upload_prefilter' );

/**
 * Rename the uploaded file to a random hash with .bin extension after validation.
 */
function mcrpd_upload_postfilter( $fileinfo ) {
	remove_filter( 'upload_dir', 'mcrpd_custom_upload_dir' );

	// Check if this upload is for a mcrpd_project post
	$is_project = false;
	if ( isset( $_REQUEST['post_id'] ) ) {
		$post_id = intval( wp_unslash( $_REQUEST['post_id'] ) );
		if ( get_post_type( $post_id ) === 'mcrpd_project' ) {
			$is_project = true;
		}
	}

	if ( $is_project && isset( $fileinfo['file'] ) ) {
		$old_path = $fileinfo['file'];
		$dir      = dirname( $old_path );
		$ext      = pathinfo( $old_path, PATHINFO_EXTENSION );

		// Only rename if it's a zip file
		if ( 'zip' === strtolower( $ext ) ) {
			$new_filename = wp_generate_password( 32, false, false ) . '.bin';
			$new_path     = $dir . '/' . $new_filename;

			if ( rename( $old_path, $new_path ) ) {
				$fileinfo['file'] = $new_path;
				$fileinfo['url']  = dirname( $fileinfo['url'] ) . '/' . $new_filename;
			}
		}
	}

	return $fileinfo;
}
add_filter( 'wp_handle_upload', 'mcrpd_upload_postfilter' );

/**
 * Ensure ZIP files can be uploaded.
 */
function mcrpd_allow_zip_uploads( $mimes ) {
	$mimes['zip'] = 'application/zip';
	return $mimes;
}
add_filter( 'upload_mimes', 'mcrpd_allow_zip_uploads' );

/**
 * Bypass extension mismatch validation for ZIP files uploaded to Projects.
 */
function mcrpd_check_filetype_and_ext( $data, $file, $filename, $mimes ) {
	$is_project = false;
	if ( isset( $_REQUEST['post_id'] ) ) {
		$post_id = intval( wp_unslash( $_REQUEST['post_id'] ) );
		if ( get_post_type( $post_id ) === 'mcrpd_project' ) {
			$is_project = true;
		}
	}

	if ( $is_project ) {
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( 'zip' === strtolower( $ext ) ) {
			$data['ext']             = 'zip';
			$data['type']            = 'application/zip';
			$data['proper_filename'] = $filename;
		}
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'mcrpd_check_filetype_and_ext', 10, 4 );

function mcrpd_custom_upload_dir( $param ) {
	$subdir          = '/mcrpd-secure-releases';
	$param['path']   = $param['basedir'] . $subdir;
	$param['url']    = $param['baseurl'] . $subdir;
	$param['subdir'] = $subdir;

	if ( ! file_exists( $param['path'] ) ) {
		wp_mkdir_p( $param['path'] );
	}

	// .htaccess - extra protection for Apache/LiteSpeed (not required, but adds defense-in-depth)
	$htaccess_path = $param['path'] . '/.htaccess';
	if ( ! file_exists( $htaccess_path ) ) {
		$rules  = "# MCRPD Secure Releases - Block all direct access\n";
		$rules .= "Order deny,allow\n";
		$rules .= "Deny from all\n";
		file_put_contents( $htaccess_path, $rules );
	}

	// index.php - prevents directory listing on ALL servers
	$index_path = $param['path'] . '/index.php';
	if ( ! file_exists( $index_path ) ) {
		file_put_contents( $index_path, '<?php // Silence is golden.' );
	}

	return $param;
}


