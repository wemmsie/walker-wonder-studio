<?php
get_header();
?>
<div class='gutter'>

    <div class='search-container'>
        <form role="search" method="get" id="searchform" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="text" name="s" id="s" data-rlvlive="true" class='search' placeholder="Search" value="<?php echo get_search_query(); ?>" />
            <button type="submit"><i class="icon fa-solid fa-magnifying-glass"></i></button>
        </form>

    </div>


    <?php if (have_posts()) : ?>
        <h1><?php printf(esc_html__('Search Results for: %s', 'zorvek'), '<span>' . esc_html(get_search_query()) . '</span>'); ?></h1>
        <ul>
            <?php while (have_posts()) : the_post(); ?>
                <li>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p><?php esc_html_e('Sorry, no results found.', 'zorvek'); ?></p>
    <?php endif; ?>

</div>

<?php
get_footer();
