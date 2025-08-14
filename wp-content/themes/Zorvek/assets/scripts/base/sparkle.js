document.addEventListener('DOMContentLoaded', () => {
  const sparkleElements = document.querySelectorAll('.sparkle');

  sparkleElements.forEach((sparkle) => {
    for (let i = 0; i < 1; i++) {
      const star = document.createElement('span');
      star.classList.add('star');

      // Append Font Awesome icon inside the span
      const starIcon = document.createElement('i');
      starIcon.classList.add('fa-solid', 'fa-star');
      star.appendChild(starIcon);

      sparkle.appendChild(star);
    }
  });
});
