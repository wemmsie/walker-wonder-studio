<?php
// This is a basic index.php file for a WordPress theme.

get_header(); ?>

<main id="main" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            // Your template content here
            the_content();
        endwhile;
    else :
        // If no content, include a "No posts found" template
        echo '<p>No content found</p>';
    endif;
    ?>
</main>

<?php
get_sidebar();
get_footer();
