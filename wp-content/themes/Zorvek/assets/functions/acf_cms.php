<?php

add_action('acf/input/admin_head', 'dynamic_acf_block_titles');
function dynamic_acf_block_titles()
{
?>
    <script type="text/javascript">
        (function($) {
            // Function to update or append custom block titles
            function updateBlockTitles() {
                $('.acf-flexible-content .layout').each(function() {
                    if ($(this).attr('data-layout') === 'halfsies') {
                        var blockName = $(this).find('[data-name="block_name"] input').val();

                        // If blockName exists, update the title
                        if (blockName) {
                            $(this).find('.acf-fc-layout-handle').text('Halfsies - ' + blockName);
                        }

                        // If blockName exists, add it to a new div after the layout handle
                        if (blockName) {
                            // Check if custom label already exists, if not, append it
                            if ($(this).find('.custom-block-title').length === 0) {
                                $(this).find('.acf-fc-layout-handle').after('<div class="custom-block-title" style="margin-top: 5px; font-weight: bold; color: #333; position:absolute; top:4px; right: 30px; pointer-events: none;">' + blockName + '</div>');
                            } else {
                                // Update the existing custom label
                                $(this).find('.custom-block-title').text(blockName);
                            }
                        }
                    }
                });
            }

            $(document).ready(function() {
                // Initial title update
                updateBlockTitles();

                // Listen for ACF append event (when blocks are added dynamically)
                $(document).on('acf/append', function(e, $el) {
                    updateBlockTitles();
                });

                // Update title when block_name changes
                $(document).on('input', '[data-name="block_name"] input', function() {
                    updateBlockTitles();
                });
            });
        })(jQuery);
    </script>
<?php
}
