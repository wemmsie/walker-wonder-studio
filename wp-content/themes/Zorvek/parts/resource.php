<!-- template-parts/resource.php -->
<?php
$type = get_query_var('type', '');
?>

<a class='resource item <?php echo esc_attr($type); ?>' href='<?php the_permalink()?>'>
    <?php if (has_post_thumbnail()) : ?>
        <div class='resource--thumbnail'>
            <?php the_post_thumbnail(); ?>
        </div>
    <?php endif; ?>
    <div class='resource--data flex flex-col gap-sub'>
        <div class='resource--title typo-h3'><?php the_title(); ?></div>
        <div class='resource--excerpt typo-p'><?php the_excerpt(); ?></div>
    </div>
</a>