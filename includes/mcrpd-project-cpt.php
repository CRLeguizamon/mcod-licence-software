<?php
/**
 * Register Project Custom Post Type
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mcrpd_register_project_cpt() {
	$labels = array(
		'name'                  => _x( 'Projects', 'Post Type General Name', 'slm' ),
		'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'slm' ),
		'menu_name'             => __( 'Projects', 'slm' ),
		'name_admin_bar'        => __( 'Project', 'slm' ),
		'archives'              => __( 'Project Archives', 'slm' ),
		'attributes'            => __( 'Project Attributes', 'slm' ),
		'parent_item_colon'     => __( 'Parent Project:', 'slm' ),
		'all_items'             => __( 'Projects', 'slm' ),
		'add_new_item'          => __( 'Add New Project', 'slm' ),
		'add_new'               => __( 'Add New', 'slm' ),
		'new_item'              => __( 'New Project', 'slm' ),
		'edit_item'             => __( 'Edit Project', 'slm' ),
		'update_item'           => __( 'Update Project', 'slm' ),
		'view_item'             => __( 'View Project', 'slm' ),
		'view_items'            => __( 'View Projects', 'slm' ),
		'search_items'          => __( 'Search Project', 'slm' ),
		'not_found'             => __( 'Not found', 'slm' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'slm' ),
		'featured_image'        => __( 'Featured Image', 'slm' ),
		'set_featured_image'    => __( 'Set featured image', 'slm' ),
		'remove_featured_image' => __( 'Remove featured image', 'slm' ),
		'use_featured_image'    => __( 'Use as featured image', 'slm' ),
		'insert_into_item'      => __( 'Insert into project', 'slm' ),
		'uploaded_to_this_item' => __( 'Uploaded to this project', 'slm' ),
		'items_list'            => __( 'Projects list', 'slm' ),
		'items_list_navigation' => __( 'Projects list navigation', 'slm' ),
		'filter_items_list'     => __( 'Filter projects list', 'slm' ),
	);
	$args = array(
		'label'                 => __( 'Project', 'slm' ),
		'description'           => __( 'Projects for License Manager', 'slm' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => SLM_MAIN_MENU_SLUG, // Submenu of the SLM plugin
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'post',
		'capabilities' => array(
			'edit_post'          => 'manage_options',
			'read_post'          => 'manage_options',
			'delete_post'        => 'manage_options',
			'edit_posts'         => 'manage_options',
			'edit_others_posts'  => 'manage_options',
			'delete_posts'       => 'manage_options',
			'publish_posts'      => 'manage_options',
			'read_private_posts' => 'manage_options'
		),
	);
	register_post_type( 'mcrpd_project', $args );
}
add_action( 'init', 'mcrpd_register_project_cpt', 0 );
