<?php

// Register Resources Post Type
function resources_custom_post_type() {
	register_post_type('resource', array (
        'labels' => array (
            'name' => __('Resources'),
            'singular_name' => __('Resource'),
            'edit_item' => __('Edit Resource'),
            'add_new' => __( 'Add New'),
        ),
        'supports' => array('title', 'excerpt', 'thumbnail'),
        'public'      => true,
        'has_archive' => false,
        'rewrite'     => array( 'slug' => 'resources' ),
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'taxonomies' => array( 'type' ),
		)
	);
}
add_action('init', 'resources_custom_post_type');