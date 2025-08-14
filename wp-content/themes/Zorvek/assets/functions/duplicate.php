<?php

function add_duplicate_post_link($actions, $post)
{
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=duplicate_post_as_draft&post=' . $post->ID, 'duplicate_nonce_' . $post->ID) . '" title="Duplicate this post" rel="permalink">Duplicate</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'add_duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'add_duplicate_post_link', 10, 2);

// Add to custom post types
function add_duplicate_link_to_custom_post_types()
{
    $post_types = get_post_types(array('public' => true), 'names');
    foreach ($post_types as $post_type) {
        add_filter("{$post_type}_row_actions", 'add_duplicate_post_link', 10, 2);
    }
}
add_action('admin_init', 'add_duplicate_link_to_custom_post_types');


function duplicate_post_as_draft()
{
    global $wpdb;

    // Ensure the request method is handled correctly
    if (!(isset($_REQUEST['post']) || (isset($_REQUEST['action']) && 'duplicate_post_as_draft' == $_REQUEST['action']))) {
        wp_die('No post to duplicate has been supplied!');
    }

    // Get the original post ID
    $post_id = isset($_REQUEST['post']) ? absint($_REQUEST['post']) : 0;
    $post = get_post($post_id);

    if (!$post) {
        wp_die('Post not found.');
    }

    $new_post_author = wp_get_current_user()->ID;

    // Create the new post as a draft
    $args = array(
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
        'post_author'    => $new_post_author,
        'post_content'   => $post->post_content,
        'post_excerpt'   => $post->post_excerpt,
        'post_name'      => $post->post_name,
        'post_parent'    => $post->post_parent,
        'post_password'  => $post->post_password,
        'post_status'    => 'draft',
        'post_title'     => $post->post_title . ' (Copy)',
        'post_type'      => $post->post_type,
        'to_ping'        => $post->to_ping,
        'menu_order'     => $post->menu_order
    );

    // Insert the post into the database
    $new_post_id = wp_insert_post($args);

    if ($new_post_id) {
        // Copy all the taxonomies/terms
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        // Duplicate all post meta
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id={$post_id}");
        if ($post_meta_infos) {
            $sql_query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ";
            $sql_query_values = array();
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = esc_sql($meta_info->meta_key);
                $meta_value = esc_sql($meta_info->meta_value);
                $sql_query_values[] = "({$new_post_id}, '{$meta_key}', '{$meta_value}')";
            }
            $sql_query .= implode(', ', $sql_query_values);
            $wpdb->query($sql_query);
        }

        // Redirect to the edit post screen for the new draft
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    } else {
        wp_die('Post creation failed, could not find original post.');
    }
}
add_action('admin_action_duplicate_post_as_draft', 'duplicate_post_as_draft');
