<?php
/*
Plugin Name: SEO Checklist
Description: Watches specific ACF fields in the WordPress admin and updates a postbox container based on certain conditions for SEO optimization.
Version: 1.0
Author: MJ
*/

function seo_checklist_enqueue_scripts($hook) {
    global $post;
    
    // Check if we're on the edit or add new screen for posts/pages/resources
    if ( ($hook == 'post.php' || $hook == 'post-new.php') && 
        ( 'post' === $post->post_type || 'page' === $post->post_type || 'resource' === $post->post_type ) ) {
        wp_enqueue_script('seo-checklist-script', plugin_dir_url(__FILE__) . 'seo-checklist.js', array('jquery'), '1.0', true);
        wp_enqueue_style('seo-checklist-style', plugin_dir_url(__FILE__) . 'seo-checklist.css');
    }
}
add_action('admin_enqueue_scripts', 'seo_checklist_enqueue_scripts');


function seo_checklist_add_meta_box() {
    $screens = ['post', 'page', 'resource']; // Add your custom post type here
    foreach ($screens as $screen) {
        add_meta_box(
            'seo_checklist_meta_box',           // Unique ID for the meta box
            __('SEO Checklist', 'seo-checklist'),  // Title of the meta box
            'seo_checklist_meta_box_callback',  // Callback function that renders the box content
            $screen,                            // Post type where the meta box should appear
            'side',                             // Context ('normal', 'advanced', or 'side')
            'default'                           // Priority ('default', 'high', 'low')
        );
    }
}
add_action('add_meta_boxes', 'seo_checklist_add_meta_box');


function seo_checklist_meta_box_callback($post) {
    wp_nonce_field('seo_checklist_nonce_action', 'seo_checklist_nonce_name');

    echo '<div id="seo-checklist">';

    // Keywords
    echo '<p id="meta-keywords-label" class="label"><span class="status dashicons dashicons-no"></span> ' . __('Keywords', 'seo-checklist') . '</p>';
    echo '<p class="description">' . __('Minimum 4 keywords', 'seo-checklist') . '</p>';

    // Meta Description
    echo '<p id="meta-description-label" class="label"><span class="status dashicons dashicons-no"></span> ' . __('Meta Description', 'seo-checklist') . '</p>';
    echo '<p class="description">' . __('Between 60 and 160 characters', 'seo-checklist') . '</p>';

    echo '</div>';
}


function seo_checklist_admin_styles() {
    echo '<style>
        #seo-checklist .label {
            margin-left: -10px;
            margin-bottom: 0px;
            font-weight: bold;
        }
        #seo-checklist .description {
            margin-left: 25px;
            opacity: 0.7;
            font-size: 11px;
        }
        #seo-checklist .status {
            font-size: 18px;
        }
        #seo-checklist .dashicons-yes {
            color: green;
        }
        #seo-checklist .dashicons-no {
            color: red;
        }
    </style>';
}
add_action('admin_head', 'seo_checklist_admin_styles');
