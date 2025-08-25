<?php
function handle_contact_form()
{
    // Check if this is an AJAX request
    if (isset($_POST['action']) && $_POST['action'] === 'handle_contact_form') {
        $name    = sanitize_text_field($_POST['name']);
        $email   = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);
        $help_selection = sanitize_text_field($_POST['select']);

        // Basic validation
        if (!empty($name) && !empty($email) && !empty($message) && !empty($help_selection)) {
            $to      = 'walkerwondermusic@gmail.com, emily@thisjones.com';
            $subject = "Form Submission from $name";

            // Add 'From' and 'Reply-To' headers
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $name,
                'Reply-To: ' . $email
            );

            $body = "<h3>Contact Form Submission from <u>$email</u><br></h3><b>Name:</b><br> $name <br><br> <b>Email:</b><br> $email <br><br> <b>Requested Topic:</b><br> $help_selection <br><br><b>Message:</b> <br> $message";

            if (wp_mail($to, $subject, $body, $headers)) {
                echo 'success'; // Send a simple success message back to AJAX
            } else {
                echo 'error';  // Send error message
            }            
        } else {
            echo 'Please fill in all fields.';
        }

        wp_die(); // Required to terminate AJAX requests properly
    }
}
add_action('wp_ajax_handle_contact_form', 'handle_contact_form');
add_action('wp_ajax_nopriv_handle_contact_form', 'handle_contact_form');
