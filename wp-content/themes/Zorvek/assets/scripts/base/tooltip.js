document.addEventListener('DOMContentLoaded', function () {
    const copyLinks = document.querySelectorAll('.copy-link');

    copyLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const url = this.getAttribute('data-link');
            navigator.clipboard.writeText(url).then(() => {
                const tooltip = this.nextElementSibling;
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';

                // Hide the tooltip after 2 seconds
                setTimeout(() => {
                    tooltip.style.visibility = 'hidden';
                    tooltip.style.opacity = '0';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
    });
});
