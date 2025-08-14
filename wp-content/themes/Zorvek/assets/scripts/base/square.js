document.addEventListener("DOMContentLoaded", function() {
    const squareImages = document.querySelectorAll('img.square');

    squareImages.forEach(function(img) {
        // Create a wrapper div
        const wrapper = document.createElement('div');
        wrapper.className = 'square-wrapper';

        // Insert the wrapper before the img in the DOM
        img.parentNode.insertBefore(wrapper, img);

        // Move the img inside the wrapper
        wrapper.appendChild(img);
    });
});
