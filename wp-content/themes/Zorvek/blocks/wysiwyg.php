<?php
$wys = get_sub_field('wysiwyg');
?>

<?php if ($wys) : ?>
    <div class='gutter'><?php echo $wys; ?></div>
<?php endif; ?>