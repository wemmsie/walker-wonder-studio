<?php
/*
 * Page Type: Resource
 */
?>

<?php get_header(); ?>

<main id='resource' class='bg-grey-1'>
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
    ?>
            <div class='gutter hero'>
                <div class='copy'>
                    <h1 class='typo-h1'><?php the_title(); ?></h1>
                    <p class='typo-h3'><?php the_field('meta_desc'); ?></p>
                </div>
                <div class='image'>
                    <?php the_post_thumbnail(); ?>
                </div>
            </div>

            <div class='resource-container'>
                <div class='share'>
                    <?php get_template_part('/parts/share-icons');
                    ?>
                </div>
                <article id='post-<?php the_ID(); ?>' class='resource-section'>
                    <?php
                    // Check if the flexible content field has rows of data
                    if (have_rows('flexible_resource')) :

                        // Loop through the rows of data
                        while (have_rows('flexible_resource')) : the_row();

                            // Load template part based on the row layout
                            get_template_part('blocks/' . get_row_layout());

                        endwhile;

                    else :
                        // No layouts found
                        echo '<p>No content available.</p>';

                    endif;
                    ?>
                </article>

            </div>
    <?php
        endwhile;
    else :
        echo '<p>No resources found.</p>';
    endif;
    ?>
</main>

<?php get_footer(); ?>