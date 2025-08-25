(() => {
  const OFFSET = 100; // adjust as needed

  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href^="#"]');
    if (!link) return;

    const targetId = link.getAttribute('href');
    if (!targetId || targetId === '#' || targetId === '#booking') return; // skip empty + modal

    const targetEl = document.querySelector(targetId);
    if (!targetEl) return;

    e.preventDefault();

    const top = targetEl.getBoundingClientRect().top + window.scrollY - OFFSET;

    window.scrollTo({
      top,
      behavior: 'smooth',
    });
  });
})();