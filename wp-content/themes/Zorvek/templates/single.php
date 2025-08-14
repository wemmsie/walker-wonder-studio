<?php
/*
 * Page Type: Post
 */
?>

<?php get_header(); ?>

<main class='post'>
    <div class='wp-block-query alignwide gutter'>
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
        ?>
                <article id='post-<?php the_ID(); ?>' class='single-post'>
                    <div class='gutter'>
                        <h1 class='typo-h2'><?php the_title(); ?></h1>
                    </div>
                    <?php the_content(); ?>
                </article>
            <?php
            endwhile;
        else :
            echo '<p>No resources found.</p>';
        endif;
            ?>
    </div>
</main>

<?php get_footer(); ?>