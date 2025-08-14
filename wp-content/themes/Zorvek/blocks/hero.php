<?php
$headline = get_sub_field('headline', false, false);
$subline = get_sub_field('subline', false, false);
?>
<div id='hero' class='block'>
    <div class='container gutter'>
        <div class='copy'>
            <?php
            remove_filter('the_content', 'wpautop');
            ?>

            <div class='typo-h1'>
                <?php echo $headline ?>
            </div>
            <p class='typo-h2'>
                <?php echo $subline ?>
            </p>

            <?php
            add_filter('the_content', 'wpautop');
            ?>
        </div>
        <div class='visuals'>
            <?php $bg_img = get_sub_field('bg_img'); ?>
            <?php if ($bg_img) : ?>
                <img class='image' src="<?php echo esc_url($bg_img['url']); ?>" alt="<?php echo esc_attr($bg_img['alt']); ?>" />
            <?php endif; ?>

            <?php $images_images = get_sub_field('images'); ?>
            <?php if ($images_images) : ?>
                <?php foreach ($images_images as $images_image): ?>
                    <img class='image' src="<?php echo esc_url($images_image['sizes']['large']); ?>" alt="<?php echo esc_attr($images_image['alt']); ?>" />
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (have_rows('icon_acc')) : ?>
                <?php while (have_rows('icon_acc')) :
                    the_row(); ?>

                    <?php
                    $color_class = get_sub_field('color_picker');
                    ?>
                    <div class="icon bg-<?php echo esc_attr($color_class); ?>">
                        <?php $icon = get_sub_field('icon'); ?>
                        <?php if ($icon) : ?>
                            <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" />
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class='scroll-arrow'>
        <i class="fa-solid fa-chevron-down"></i>
    </div>
</div>