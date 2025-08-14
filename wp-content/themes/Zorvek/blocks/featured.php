<?php
// Get the post type and taxonomy terms
$postType = get_sub_field('post_type');
$taxTerms = get_sub_field($postType . '_tax');

// Initialize an array to track excluded post IDs
$excluded_ids = array();
?>

<div id='featured_posts' class='gutter spotlight flex gap-post lg:flex-nowrap flex-wrap'>
    <div class='typo-h1'>
        <?php echo $postType; ?>
    </div>

    <!-- Featured Resource Section -->
    <div class='featured-resource'>
        <?php
        if (is_array($taxTerms) && !empty($taxTerms)) {
            // Extract the slugs from the WP_Term objects
            $term_slugs = wp_list_pluck($taxTerms, 'slug');
            $taxonomy = $taxTerms[0]->taxonomy;

            // Query for the featured resource
            $featured_query = new WP_Query(array(
                'post_type'      => $postType,
                'posts_per_page' => 1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $term_slugs,
                        'operator' => 'IN',
                    )
                ),
            ));

            if ($featured_query->have_posts()) :
                while ($featured_query->have_posts()) : $featured_query->the_post();
                    $excluded_ids[] = get_the_ID(); // Add featured post ID to excluded array
                    set_query_var('type', 'vertical');
                    set_query_var('format', $postType);
                    get_template_part('./parts/post');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No featured resource found.</p>';
            endif;
        }
        ?>
    </div>


    <!-- Next 4 Resources Section -->
    <div class='small-resource w-1/3 flex flex-col gap-post'>
        <p class='typo-h3'>Spotlight</p>
        <?php
        if (is_array($taxTerms) && !empty($taxTerms)) {
            // Query for the next 4 resources
            $resources_query = new WP_Query(array(
                'post_type'      => $postType,
                'posts_per_page' => 4,
                'post__not_in'   => $excluded_ids, // Exclude already displayed posts
                'tax_query'      => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $term_slugs,
                        'operator' => 'IN',
                    )
                ),
            ));

            if ($resources_query->have_posts()) :
                while ($resources_query->have_posts()) : $resources_query->the_post();
                    $excluded_ids[] = get_the_ID(); // Add resource post ID to excluded array
                    set_query_var('type', 'horizontal');
                    set_query_var('format', $postType);
                    get_template_part('./parts/post');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No additional resources found.</p>';
            endif;
        }
        ?>
    </div>
</div>