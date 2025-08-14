<?php
function zorvek_populate_color_picker_field( $field ) {
    if ( have_rows( 'colors', 'options' ) ) {
        $field['choices'] = array();

        while ( have_rows( 'colors', 'options' ) ) {
            the_row();

            $color_name = get_sub_field( 'name' );
            $color_code = get_sub_field( 'color' );

            if ( is_string( $color_name ) && !empty( $color_name ) ) {
                $field['choices'][ esc_attr( $color_name ) ] = esc_html( $color_name );
            }
        }
    }

    return $field;
}

add_filter( 'acf/load_field/name=color_picker', 'zorvek_populate_color_picker_field' );



function generate_tailwind_colors_file() {
    $colors = array();

    if ( have_rows( 'colors', 'options' ) ) {
        while ( have_rows( 'colors', 'options' ) ) {
            the_row();
            $color_name = sanitize_title( get_sub_field( 'name' ) );
            $color_code = get_sub_field( 'color' );
            $colors[$color_name] = $color_code;
        }
    }

    $file_path = get_template_directory() . '/assets/tailwind-colors.json';
    file_put_contents( $file_path, json_encode( $colors ) );
}
add_action('acf/save_post', 'generate_tailwind_colors_file');

?>
