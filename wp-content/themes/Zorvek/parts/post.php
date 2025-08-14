<?php
$type = get_query_var('type', '');
$format = get_query_var('format', '');
$postType = get_sub_field('post_type');
$taxTerms = get_sub_field($postType . '_tax');

// Check if taxTerms is not empty
if (!empty($taxTerms)) {
    $taxonomy = $taxTerms[0]->taxonomy;
    $terms = get_the_terms($post->ID, $taxonomy);

    // Get the first term name
    if (!empty($terms) && !is_wp_error($terms)) {
        $first_term = $terms[0]->name;
    }
}
?>

<a class='<?php echo esc_attr($format); ?> w-full item <?php echo esc_attr($type); ?>' href='<?php the_permalink(); ?>'>
    <div class='sub'>
        <?php if (has_post_thumbnail()) : ?>
            <div class='<?php echo esc_attr($format); ?>--thumbnail'>
                <?php the_post_thumbnail(); ?>
            </div>
        <?php endif; ?>
        <div class='<?php echo esc_attr($format); ?>--data flex flex-col gap-sub'>
            <div class='tiny-pill tax'><?php echo esc_html($first_term); ?></div>
            <div class='<?php echo esc_attr($format); ?>--title typo-h3'><?php the_title(); ?></div>
            <div class='<?php echo esc_attr($format); ?>--excerpt typo-p'><?php echo get_field('meta_desc'); ?></div>
        </div>
    </div>
</a>