<?php
if (!defined('ASSET_BASE')) {
    define('ASSET_BASE', 'https://assets.walkerwonderstudio.com');
}
if (!defined('ASSET_VER')) {
    define('ASSET_VER', '2025-08-27-1'); // bump on deploy
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('zorvek-style', get_stylesheet_uri(), [], null);
    wp_enqueue_style('zorvek-main',  ASSET_BASE . '/css/main.css', [], ASSET_VER);
    wp_enqueue_script('zorvek-main', ASSET_BASE . '/script/main.min.js', [], ASSET_VER, true);
});

// --- Imports --- test

require_once get_template_directory() . '/assets/functions/post_types.php';
require_once get_template_directory() . '/assets/functions/taxonomies.php';
require_once get_template_directory() . '/assets/functions/duplicate.php';
require_once get_template_directory() . '/assets/functions/acf_post_selector.php';
require_once get_template_directory() . '/assets/functions/color_picker.php';
require_once get_template_directory() . '/assets/functions/handle_contact_form.php';


add_filter('relevanssi_live_search_add_result_div', '__return_true', 99);

add_filter('relevanssi_live_search_base_styles', '__return_false');
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('relevanssi-live-search');
}, 99);


// require_once get_template_directory() . '/assets/functions/acf_cms.php';

// --- Enqueue theme styles and scripts ---

// functions.php

// Renders modals from ACF Options repeater, late in the footer.
function zorvek_render_booking_modals()
{
    $is_admin_user = current_user_can('manage_options');

    // LOUD (admin-only) diagnostics so you see *why* nothing renders.
    if (! function_exists('have_rows')) {
        if ($is_admin_user) {
            echo '<div style="position:fixed;z-index:999999;bottom:10px;right:10px;background:#222;color:#fff;padding:10px 14px;border-radius:6px;">
        <strong>Modals not rendered:</strong> ACF functions not available. Is Advanced Custom Fields active?
      </div>';
        }
        return;
    }

    // Use have_rows on the Options page
    if (! have_rows('embed_codes', 'option')) {
        if ($is_admin_user) {
            $rows = get_field('embed_codes', 'option');
            $count = is_array($rows) ? count($rows) : 0;
            echo '<div style="position:fixed;z-index:999999;bottom:10px;right:10px;background:#222;color:#fff;padding:10px 14px;border-radius:6px;">
        <strong>Modals not rendered:</strong> No rows found in <code>embed_codes</code> on Options. (count=' . $count . ')
      </div>';
        }
        return;
    }

    // We have rows: output ALL modals
    while (have_rows('embed_codes', 'option')) : the_row();
        $raw_id   = (string) get_sub_field('id');
        $modal_id = sanitize_title($raw_id);            // normalized id ("Booking Link" -> "booking-link")
        $code     = (string) get_sub_field('embed_code'); // Calendly embed

        // Keep Calendly <script> tags (trusted admin-entered HTML).
        // If you prefer kses, allow <script> (see note below).
?>
        <div id="<?php echo esc_attr($modal_id); ?>" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="modal-overlay modal-toggle"></div>
            <div class="modal-wrapper modal-transition" role="document">
                <button class="modal-close modal-toggle" aria-label="Close modal">
                    <div class='modal-inner'>
                        <div class="line"></div>
                        <div class="line"></div>
                    </div>
                </button>
                <div class="modal-body">
                    <div class="modal-content">
                        <?php echo $code; // raw echo so Calendly loads 
                        ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    endwhile;
}

// Render in footer so ACF is definitely loaded.
add_action('wp_footer', 'zorvek_render_booking_modals', 99);

// OPTIONAL: also expose a shortcode so you can render modals anywhere.
add_shortcode('booking_modals', function () {
    ob_start();
    zorvek_render_booking_modals();
    return ob_get_clean();
});


function zorvek_enqueue_scripts()
{
    // Enqueue the default theme stylesheet
    wp_enqueue_style('zorvek-style', get_stylesheet_uri());

    // Enqueue additional main stylesheet
    wp_enqueue_style('zorvek-main-style', get_template_directory_uri() . '/src/css/main.css', array(), '1.0', 'all');
    wp_enqueue_style('font-awesome', get_template_directory_uri() . '/node_modules/@fortawesome/fontawesome-free/css/all.min.css', array(), null);

    // Enqueue jQuery (WordPress default)
    wp_enqueue_script('jquery');

    wp_enqueue_script('gsap-js', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', false);
    wp_enqueue_script('gsap-st', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', false, true);

    // Enqueue main JavaScript file as a module, with jQuery as a dependency
    wp_enqueue_script('zorvek-main-script', get_template_directory_uri() . '/script/main.min.js', array(), '1.0', false);

    wp_script_add_data('zorvek-main-script', 'type', 'module');
}
add_action('wp_enqueue_scripts', 'zorvek_enqueue_scripts');

function enqueue_contact_form_scripts()
{

    wp_enqueue_script('contact-form-script', get_template_directory_uri() . '/assets/scripts/form_ajax.js', false, true);

    // Get the success title and success copy from ACF
    $successTitle = get_field('success_title', 'options');
    $successCopy = get_field('success', 'options');

    // Localize the script to pass ajaxurl to it
    wp_localize_script('contact-form-script', 'contactFormData', array(
        'ajaxurl'      => admin_url('admin-ajax.php'),
        'successTitle' => esc_html($successTitle),
        'successCopy'  => esc_html($successCopy)
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_contact_form_scripts');


// --- Enqueue custom admin styles and scripts ---
function enqueue_custom_admin_styles()
{
    wp_enqueue_style('custom-admin-menu-styles', get_template_directory_uri() . '/src/css/admin_style.css');
}
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_styles');

function load_custom_admin_script()
{
    wp_enqueue_script('zorvek-main-admin-script', get_template_directory_uri() . '/src/script/admin.min.js', array('jquery', 'acf-input'), '1.0', true);
}
add_action('acf/input/admin_enqueue_scripts', 'load_custom_admin_script');



// Function to add prism.css and prism.js to the site
function add_prism()
{

    if (is_single() && ('post' === get_post_type() || 'resource' === get_post_type())) {

        // Register prism.css file
        wp_register_style(
            'prismCSS', // handle name for the style 
            get_stylesheet_directory_uri() . '/src/css/extras/prism.css' // location of the prism.css file
        );

        // Register prism.js file
        wp_register_script(
            'prismJS', // handle name for the script 
            get_stylesheet_directory_uri() . '/assets/scripts/prism.js' // location of the prism.js file
        );

        // Enqueue the registered style and script files
        wp_enqueue_style('prismCSS');
        wp_enqueue_script('prismJS');
    }
}
add_action('wp_enqueue_scripts', 'add_prism');

// --- Add theme support ---
function zorvek_theme_support()
{
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
}
add_action('after_setup_theme', 'zorvek_theme_support');

// Change Admin Menu Order
function custom_menu_order($menu_ord)
{
    if (!$menu_ord) return true;
    return array(
        'index.php', // Dashboard
        'edit.php?post_type=acf-field-group', // ACF

        'separator1', // Separator

        'edit.php?post_type=page', // Pages
        'edit.php?post_type=resource', // Resources
        'edit.php', // Posts
        'upload.php', // Media
        'edit-comments.php', // Comments
        'admin.php?page=information', // Information

        'separator2', // Separator

        'themes.php', // Themes
        'plugins.php', // Plugins

        'separator3', // Separator

        'tools.php', // Tools
        'users.php', // Users
        'options-general.php', // Settings

        'separator-last', // Separator

        'admin.php?page=bluehost', // Bluehost
    );
}
add_filter('custom_menu_order', 'custom_menu_order');
add_filter('menu_order', 'custom_menu_order');

// Organized Page Template Pull
function custom_template_include($template)
{
    $custom_templates = [
        'resource' => [
            'single' => 'templates/single-resource.php',
            'archive' => 'templates/resources.php'
        ],
        'post' => [
            'single' => 'templates/single.php',
            'archive' => 'templates/posts.php'
        ]
    ];

    foreach ($custom_templates as $post_type => $templates) {
        if (is_singular($post_type)) {
            $new_template = locate_template([$templates['single']]);
            if ($new_template) {
                return $new_template;
            }
        } elseif (is_post_type_archive($post_type)) {
            $new_template = locate_template([$templates['archive']]);
            if ($new_template) {
                return $new_template;
            }
        }
    }

    return $template;
}
add_filter('template_include', 'custom_template_include');

add_theme_support('post-thumbnails');

// Block Editor Settings
add_filter('use_block_editor_for_post_type', 'disable_block_editor_for_page_post_type', 10, 2);

function disable_block_editor_for_page_post_type($use_block_editor, $post_type)
{
    if ('page' === $post_type || 'resource' === $post_type || 'post' === $post_type) {
        return false;
    }
    return $use_block_editor;
}

function theme_register_menus()
{
    register_nav_menus(
        array(
            'primary' => __('Primary Menu', 'theme-text-domain'),
            'footer' => __('Footer Menu', 'theme-text-domain'),
        )
    );
}
add_action('after_setup_theme', 'theme_register_menus');

class Zorvek_Walker extends Walker_Nav_Menu
{

    // Start Level - this controls the submenu <ul> element
    function start_lvl(&$output, $depth = 0, $args = null)
    {
        $indent = str_repeat("\t", $depth);
        $submenu_class = ($depth == 0) ? 'first-submenu' : 'sub-submenu';
        $output .= "\n$indent<ul class=\"$submenu_class\">\n";
    }

    // Start Element - this controls each menu item <li> and <a> tag
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $class_names = $depth === 0 ? 'top-menu-item' : 'sub-menu-item';
        $classes = implode(' ', $item->classes); // All classes of the current menu item

        $output .= $indent . '<li class="' . $class_names . ' ' . esc_attr($classes) . '">';
        $output .= '<a href="' . $item->url . '">' . $item->title . '</a>';

        // If the item has the "about" class, append the custom data
        if (in_array('contact', $item->classes)) {
            // Append the additional data as list items within this menu item
            $output .= '<ul class="sub-submenu">';
            $output .= '<li class="sub-menu-item custom-appended">';
            $output .= esc_html(get_field('location', 'options'));
            $output .= '</li>';
            $output .= '<li class="sub-menu-item custom-appended">';
            $output .= '<a href="mailto:' . esc_html(get_field('email', 'options')) . '">' . esc_html(get_field('email', 'options')) . '</a>';
            $output .= '</li>';
            $output .= '<li class="sub-menu-item custom-appended">';
            $output .= '<a href="tel:' . esc_html(get_field('phone_number', 'options')) . '">' . esc_html(get_field('phone_number', 'options')) . '</a>';
            $output .= '</li>';
            $output .= '</ul>';
        }
    }

    // End Element - controls closing </li>
    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= "</li>\n";
    }
}

function zorvek_enqueue_relevanssi_live_search()
{
    if (is_search()) {
        // This script is built into the Relevanssi Live Ajax plugin, no need to specify a JS file.
    }
}
add_action('wp_enqueue_scripts', 'zorvek_enqueue_relevanssi_live_search');


// function zorvek_enqueue_relevanssi_live_search_js() {
//     // Check if it's a search page
//     if ( is_search() ) {
//         // Enqueue Relevanssi's Live Search JavaScript
//         // wp_enqueue_script( 'relevanssi-live-search', plugins_url( '../plugins/relevanssi-live-ajax-search/assets/javascript/src/script.js' ), array('jquery'), null, true );

//         wp_enqueue_script('relevanssi-live-search', plugins_url('/relevanssi-live-ajax-search/assets/javascript/src/script.js'), '1.0', false);

//     }
// }
// add_action( 'wp_enqueue_scripts', 'zorvek_enqueue_relevanssi_live_search_js' );
