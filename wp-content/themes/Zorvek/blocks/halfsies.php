<?php
$ns = get_sub_field('halfsies');
$flip = get_sub_field('flip_direction');
$copy = get_sub_field('wysiwyg');
$name = get_sub_field('block_name');

$slug = mb_strtolower($name, 'UTF-8');
$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');
?>

<?php if ($ns) : ?>
    <div id='halfsies' class='block'>
        <div id="<?php echo esc_attr($slug); ?>" class='container gutter <?php echo $flip ?>'>
            <div class='half copy'>
                <?php echo $copy ?>
                <?php $cta = get_sub_field('cta'); ?>
                <?php if ($cta) : ?>
                    <a class='pill pill-blue mr-auto' href=" <?php echo esc_url($cta['url']); ?>" target="<?php echo esc_attr($cta['target']); ?>"><?php echo esc_html($cta['title']); ?></a>
                <?php endif; ?>
            </div>


            <?php if (have_rows('halfsies')): ?>
                <?php while (have_rows('halfsies')) : the_row(); ?>
                    <?php if (get_row_layout() == 'image') :
                        $image = get_sub_field('image');
                    ?>
                        <div class='half <?php echo esc_attr(get_row_layout()); ?>'>
                            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
                        </div>
                        <!--- Columns Half --->
                    <?php elseif (get_row_layout() == 'columns') : ?>
                        <div class='half <?php echo esc_attr(get_row_layout()); ?>'>
                            <?php if (have_rows('columns')) : ?>
                                <?php while (have_rows('columns')) : the_row(); ?>
                                    <?php $color_class = get_sub_field('color_picker'); ?>
                                    <div class='column bg-<?php echo esc_attr($color_class); ?>'>
                                        <?php the_sub_field('copy'); ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <?php // No rows found 
                                ?>
                            <?php endif; ?>
                        </div>
                        <!--- Featurettes Half --->
                    <?php elseif (get_row_layout() == 'linked_featurettes') : ?>
                        <div class='half <?php echo esc_attr(get_row_layout()); ?>'>
                            <?php if (have_rows('featurettes')) : ?>
                                <?php while (have_rows('featurettes')) : the_row();
                                    $icon = get_sub_field('icon');
                                ?>
                                    <div class='featurette'>
                                        <?php if ($icon) :
                                        ?>
                                            <?php $color_class = get_sub_field('color_picker'); ?>
                                            <div class='icon bg-<?php echo esc_attr($color_class); ?>'>
                                                <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" />
                                            </div>

                                        <?php endif; ?>
                                        <div class='copy'>
                                            <div class='typo-h3'>
                                                <?php the_sub_field('label'); ?>
                                            </div>
                                            <p>
                                                <?php the_sub_field('copy'); ?>
                                            </p>


                                            <?php $link = get_sub_field('link'); ?>
                                            <?php if ($link) : ?>
                                                <a class='pill pill-sm button' href="<?php echo esc_url($link['url']); ?>" target="<?php echo esc_attr($link['target']); ?>"><?php echo esc_html($link['title']); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                        </div>
                    <?php else : ?>
                        <?php // No rows found 
                        ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endwhile; ?>

        <?php else: ?>
            <?php // No layouts found 
            ?>
        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>