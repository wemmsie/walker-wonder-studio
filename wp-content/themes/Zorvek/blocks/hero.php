<?php
$headline = get_sub_field('headline', false, false);
$subline = get_sub_field('subline', false, false);
$svg   = file_get_contents(get_template_directory() . '/components/squiggly.svg');
?>

<div id='hero' class='block'>

    <div class="container gutter <?php if (have_rows('squiggly_feat')) : ?><?php while (have_rows('squiggly_feat')) : the_row(); ?>
has-squig<?php endwhile; ?><?php endif; ?>">

        <?php $image_type = get_sub_field('image_type'); ?>

        <?php if ($image_type === 'logo') : ?>
            <?php $lg_logo = get_sub_field('lg_logo'); ?>
            <?php if ($lg_logo) : ?>
                <div class='visuals logo'>
                    <img class='image' src="<?php echo esc_url($lg_logo['url']); ?>" alt="<?php echo esc_attr($lg_logo['alt']); ?>" />
                <?php endif; ?>
                <?php if (have_rows('squiggly_feat')) : ?>

                    <?php while (have_rows('squiggly_feat')) : the_row(); ?>
                        <?php $color = get_sub_field('color_picker'); ?>
                        <?php $textColor = get_sub_field('color_picker_text'); ?>
                        <?php $link = get_sub_field('link'); ?>
                        <a href="<?php echo esc_url($link['url']); ?>" target="<?php echo esc_attr($link['target']); ?>">

                            <div class="squiggly">
                                <?php $svg = preg_replace('/<svg\b/i', '<svg class="fill-' . esc_attr($color) . '"', $svg, 1);
                                echo $svg;
                                ?>


                                <?php
                                $longText = get_sub_field('arching_text');

                                if ($longText === '') {
                                    $part1 = $part2 = '';
                                } else {
                                    $words = preg_split('/\s+/', $longText, -1, PREG_SPLIT_NO_EMPTY);
                                    $count = count($words);
                                    $leftCount = (int) ceil($count / 2);

                                    $part1 = implode(' ', array_slice($words, 0, $leftCount));
                                    $part2 = implode(' ', array_slice($words, $leftCount));
                                }
                                ?>

                                <div class='text'>
                                    <div class='curved'>
                                        <svg viewBox="0 0 500 500" aria-hidden="true" focusable="false">
                                            <defs>
                                                <!-- Upward arch (unchanged) -->
                                                <path id="curveUp" fill="none"
                                                    d="M73.2,148.6c4-6.1,65.5-96.8,178.6-95.6c111.3,1.2,170.8,90.3,175.1,97" />

                                                <!-- Downward arch: flipped vertically + path reversed -->
                                                <path id="curveDown" fill="none"
                                                    d="M73.2,148.6c4-6.1,65.5-96.8,178.6-95.6c111.3,1.2,170.8,90.3,175.1,97"
                                                    transform="scale(1,-1) translate(0,-500)" />
                                                <!-- <path id="curveDown" fill="none"
                                            d="M426.9,148.6c-4-6.1-65.5-96.8-178.6-95.6c-111.3,1.2-170.8,90.3-175.1,97"
                                            transform="scale(1,-1) translate(0,-500)" /> -->
                                            </defs>

                                            <!-- Text on the upward arch -->
                                            <text width="500" dy="-4" class='up'>
                                                <textPath href="#curveUp" startOffset="50%" text-anchor="middle" class=' fill-<?php echo $textColor ?>'>
                                                    <?php echo esc_html($part1); ?>
                                                </textPath>
                                            </text>

                                            <!-- Text on the downward arch, mirrored back so it reads left-to-right -->
                                            <g transform="scale(1,+1) translate(0,0)" class='down'>
                                                <text width="500" dy="6">
                                                    <textPath href="#curveDown" startOffset="50%" text-anchor="middle" class=' fill-<?php echo $textColor ?>'>
                                                        <?php echo esc_html($part2); ?>
                                                    </textPath>
                                                </text>
                                            </g>
                                        </svg>
                                    </div>


                                    <span class='main text-<?php echo $textColor ?>'><?php the_sub_field('main_text'); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($image_type === 'full') : ?>
                <?php $bg_img = get_sub_field('bg_img'); ?>
                <?php if ($bg_img) : ?>
                    <div class='visuals full'>
                        <img class='image' src=" <?php echo esc_url($bg_img['url']); ?>" alt="<?php echo esc_attr($bg_img['alt']); ?>" />
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($image_type === 'multiple') : ?>
                <?php $images_images = get_sub_field('images'); ?>
                <?php if ($images_images) : ?>
                    <div class='visuals multiple'>
                        <?php foreach ($images_images as $images_image): ?>
                            <img class='image multiple' src="<?php echo esc_url($images_image['sizes']['large']); ?>" alt="<?php echo esc_attr($images_image['alt']); ?>" />
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

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
    </div>
    <div class='scroll-arrow'>
        <i class="fa-solid fa-chevron-down"></i>
    </div>
</div>