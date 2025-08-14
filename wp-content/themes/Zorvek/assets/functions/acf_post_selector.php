<?php

// Function to get all relevant post types (excluding 'page' and 'attachment')
function get_post_type_choices()
{
    $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false), 'objects');
    $choices = array();

    foreach ($post_types as $post_type) {
        if (!in_array($post_type->name, array('page', 'attachment'))) {
            $choices[$post_type->name] = $post_type->label;
        }
    }

    return $choices;
}

// Populate the "post_type" field using the correct field key
function populate_post_type_field($field)
{
    $field['choices'] = get_post_type_choices();
    return $field;
}
add_filter('acf/load_field/key=field_66ccfacb97d39', 'populate_post_type_field');