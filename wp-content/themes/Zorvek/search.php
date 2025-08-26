<?php
get_header();
?>
<div class='container gutter search-page'>

    <div class='search-container'>
        <form role="search" method="get" id="searchform" action="<?php echo esc_url(home_url('/')); ?>">
            <input
                type="text"
                name="s"
                id="s"
                class="search"
                placeholder="Search"
                value="<?php echo get_search_query(); ?>"
                data-rlvlive="true"
                data-rlvconfig="default"
                data-rlvparentel="#rlvlive" />
            <button type="submit"><i class="icon fa-solid fa-magnifying-glass"></i></button>

            <!-- The plugin will render results here -->
        </form>
    </div>

    <div class='results'>
        <?php if (have_posts()) : ?>
            <h1><?php printf(esc_html__('Search Results for: %s', 'zorvek'), '<span>' . esc_html(get_search_query()) . '</span>'); ?></h1>
            <ul>
                <?php while (have_posts()) : the_post(); ?>
                    <a href="<?php the_permalink(); ?>">
                        <div class='result'>
                            <h2><?php the_title(); ?></h2>
                            <p><?php the_excerpt(); ?></p>
                        </div>
                    </a>

                <?php endwhile; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e('Sorry, no results found.', 'zorvek'); ?></p>
        <?php endif; ?>
    </div>

</div>

<?php
get_footer();
