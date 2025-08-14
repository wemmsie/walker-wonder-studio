<footer class='block'>
    <div class='container gutter'>
        <div>
            <h1>One last thing...</h1>
        </div>
        <div class='bulk'>
            <div class='info'>
                <div>
                    <p>Email the boss</p>
                    <span><?php echo esc_html(get_field('email', 'options')); ?></span>
                </div>
                <div class=''>
                    <p>Call us</p>
                    <span><?php echo esc_html(get_field('phone_number', 'options')); ?></span>
                </div>
            </div>
            <div class='search-container'>
                <form role="search" method="get" id="searchform" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="text" name="s" id="s" placeholder="Search" class='search' value="<?php echo get_search_query(); ?>" />
                    <button type="submit"><i class="icon fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
        </div>
        <div class='credit typo-p'>
            <p>Website design by <a class='sparkle' href='https://thisjones.com' target='_blank'>MJ</a></p>
        </div>
    </div>
</footer>