<?php
$code = get_sub_field('code');
$language = get_sub_field('language');
?>
<?php if ($code) : ?>
    <div class='gutter'>
        <pre><code class="language-<?php echo esc_attr($language); ?>"><?php echo $code; ?></code></pre>
    </div>
<?php endif; ?>