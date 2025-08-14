(function ($) {
    // Wait for ACF to be ready
    acf.addAction('ready', function () {
        console.log('ACF ready event triggered.');

        // Function to handle updating the layout handle with block_name
        function updateBlockName($layout) {
            var layoutType = $layout.attr('data-layout');

            // Only target 'halfsies' layouts
            if (layoutType === 'halfsies') {
                var blockName = $layout.find('[data-name="block_name"] input').val();

                // If blockName exists, append it to the layout handle
                if (blockName) {
                    var $handle = $layout.children('.acf-fc-layout-handle');

                    // Check if we already appended a custom label to avoid duplication
                    if ($handle.find('.custom-block-label').length === 0) {
                        // Create the new label and hide it initially
                        var $newLabel = $('<span style="font-weight: bold; display:none;" class="custom-block-label"> - ' + blockName + '</span>');
                        
                        // Append the label and then fade it in
                        $handle.append($newLabel);
                        $newLabel.fadeIn(300); // Fade in over 300ms
                    }
                }
            }
        }

        // Initial trigger when page is fully loaded
        $('.acf-flexible-content .layout').each(function () {
            updateBlockName($(this));
        });

        // Trigger when a new layout is added dynamically
        acf.addAction('append', function ($el) {
            $el.find('.layout').each(function () {
                updateBlockName($(this));
            });
        });

        // Listen for changes in the block_name field dynamically
        $(document).on('input', '[data-name="block_name"] input', function () {
            var $layout = $(this).closest('.layout');
            updateBlockName($layout);
        });

        // Listen for collapse/expand events and update the title accordingly
        $(document).on('click', '.acf-fc-layout-handle', function () {
            var $layout = $(this).closest('.layout');
            setTimeout(function () {
                updateBlockName($layout);
            }, 500); // Add a small delay to retry initialization
        });

        // Override the ACF collapse and close logic to keep the appended title
        acf.addAction('hide', function ($layout) {
            // When a layout is collapsed
            updateBlockName($layout);
        });

        acf.addAction('show', function ($layout) {
            // When a layout is expanded
            updateBlockName($layout);
        });
    });
})(jQuery);
