<?php
/*
 * Template Name: Resources
 */
?>

<?php get_header(); ?>


<main class='resources-archive'>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <?php
            // Check if the flexible content field has rows of data
            if (have_rows('flexible_resources')) :

                // Loop through the rows of data
                while (have_rows('flexible_resources')) : the_row();

                    // Load template part based on the row layout
                    get_template_part('blocks/' . get_row_layout());

                endwhile;

            else :
                // No layouts found
                echo '<p>No content available.</p>';

            endif;
            ?>
    <?php endwhile;
    endif; ?>
</main>




<?php get_footer(); ?>