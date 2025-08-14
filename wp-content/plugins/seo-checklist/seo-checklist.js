jQuery(document).ready(function ($) {
    function checkMetaDescription() {
        const metaDescription = $('#meta-description textarea').val();
        const length = metaDescription.length;
        const $descriptionLabel = $('#meta-description-label');

        if (length >= 60 && length <= 160) {
            $descriptionLabel.removeClass('invalid').addClass('valid');
            $descriptionLabel.find('.status').removeClass('dashicons-no').addClass('dashicons-yes');
        } else {
            $descriptionLabel.removeClass('valid').addClass('invalid');
            $descriptionLabel.find('.status').removeClass('dashicons-yes').addClass('dashicons-no');
        }
    }

    function checkKeywords() {
        let filledKeywords = 0;
        $('#meta-keywords .acf-row').each(function () {
            const keyword = $(this).find('input').val();
            if (keyword) {
                filledKeywords++;
            }
        });

        const $keywordsLabel = $('#meta-keywords-label');

        if (filledKeywords >= 4) {
            $keywordsLabel.removeClass('invalid').addClass('valid');
            $keywordsLabel.find('.status').removeClass('dashicons-no').addClass('dashicons-yes');
        } else {
            $keywordsLabel.removeClass('valid').addClass('invalid');
            $keywordsLabel.find('.status').removeClass('dashicons-yes').addClass('dashicons-no');
        }
    }

    $('#meta-description textarea').on('keyup input', checkMetaDescription);
    $('#meta-keywords').on('keyup input change', 'input', checkKeywords);

    // Initial check on page load
    checkMetaDescription();
    checkKeywords();
});
