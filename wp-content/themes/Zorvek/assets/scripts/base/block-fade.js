document.addEventListener("DOMContentLoaded", () => {
  // Options for the observer (which part of the viewport to observe)
  const options = {
    root: null, // use the viewport as the root
    rootMargin: "0px",
    threshold: 0.1, // Trigger when 10% of the element is visible
  };

  // Callback function to execute when the target element is in the viewport
  const handleIntersect = (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        observer.unobserve(entry.target); // Stop observing once it's visible
      }
    });
  };

  // Create an intersection observer
  const observer = new IntersectionObserver(handleIntersect, options);

  // Target all elements with the class 'block'
  const blocks = document.querySelectorAll(".block");
  blocks.forEach((block) => {
    observer.observe(block);
  });
});
