<?php
// Minimal router so WP sees a root index.php and you keep using templates/index.php
$tpl = get_theme_file_path('templates/index.php');
if ( file_exists($tpl) ) {
    include $tpl;
    return;
}

// Ultra-safe fallback if templates/index.php is missing for any reason
get_header();
echo '<main class="site-main"><p>No template found.</p></main>';
get_footer();
