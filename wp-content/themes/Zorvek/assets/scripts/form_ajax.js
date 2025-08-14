jQuery(document).ready(function ($) {
  console.log(contactFormData.ajaxurl); // Debug ajaxurl

  $('#contactForm').submit(function (e) {
    e.preventDefault(); // Prevent form submission

    // Hide form, show loading icon
    $('#contactForm').hide();
    $('#loadingIcon').show();

    // Collect form data
    var formData = {
      action: 'handle_contact_form', // The WordPress AJAX action
      name: $('#name').val(),
      email: $('#email').val(),
      select: $('#select').val(),
      message: $('#message').val(),
    };

    // Send AJAX request
    $.ajax({
      url: contactFormData.ajaxurl, // Use the localized ajaxurl
      type: 'POST',
      data: formData,
      success: function (response) {
        // Hide loading icon
        $('#loadingIcon').hide();

        // Fade out the current title and copy
        $('#contact .copy h1, #contact .copy h2').fadeOut(300, function () {
          // After fade out, update the content
          $('#contact .copy h1').text(contactFormData.successTitle);
          $('#contact .copy h2').text(contactFormData.successCopy);

          // Fade in the new title and copy
          $('#contact .copy h1, #contact .copy h2').fadeIn(300);
        });

        // Optionally, reset the form and show it again
        $('#contactForm').trigger('reset').fadeIn();
      },
      error: function () {
        // Hide loading icon, show error message
        $('#loadingIcon').hide();
        $('#formMessage').html('<p class="error">Something went wrong. Please try again later.</p>').fadeIn();
      },
    });
  });
});

jQuery(document).ready(function ($) {
  $('#message').on('input', function () {
    this.style.height = 'auto'; // Reset the height
    this.style.height = this.scrollHeight + 'px'; // Set the height based on content
  });
});
