<?php
$title = esc_html(get_field('contact_title', 'options'));
$copy = esc_html(get_field('contact_block_copy', 'options'));
$message = esc_html(get_field('message_placeholder', 'options'));

$successTitle = get_field('success_title', 'options');
$success = get_field('success', 'options');
?>

<div id="contact" class="block">
    <div class="container gutter">
        <div class="copy">
            <h1><?php echo $title ?></h1>
            <h2><?php echo $copy ?></h2>
        </div>

        <!-- Form Section -->
        <form id="contactForm" method="post">
            <div class="flex w-full gap-5 name-email">
                <input class="typo-p" type="text" id="name" name="name" required placeholder="Name">
                <input class="typo-p" type="email" id="email" name="email" required placeholder="Email">
            </div>

            <?php if (have_rows('help_options', 'option')) : ?>
                <select id="select" name="select" required class="typo-p">
                    <option value="0" disabled selected>How can we help?</option>

                    <?php while (have_rows('help_options', 'option')) : the_row(); ?>
                        <option value="<?php the_sub_field('option'); ?>"><?php the_sub_field('option'); ?></option>
                    <?php endwhile; ?>

                </select>
            <?php endif; ?>

            <textarea class="typo-p" id="message" name="message" required placeholder="<?php echo $message ?>" rows="4"></textarea>

            <button type="submit" class="pill pill-blue">Send Message</button>
        </form>

        <!-- Loading Icon -->
        <div id="loadingIcon" style='display: none;'>
            <div class="loader">
                <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                    width="40px" height="40px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve">
                    <path fill="none" d="m25,3C12.85,3,3,12.85,3,25">

                        <animateTransform attributeType="xml"
                            attributeName="transform"
                            type="rotate"
                            from="0 25 25"
                            to="360 25 25"
                            dur="0.6s"
                            repeatCount="indefinite" />
                    </path>
                </svg>
            </div>
        </div>

        <!-- Success/Error Message -->
        <div id="formMessage" style="display: none;"></div>
    </div>
</div>