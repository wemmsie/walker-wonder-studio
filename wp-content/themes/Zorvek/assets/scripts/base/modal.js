
(() => {
  // --- Collect all modal IDs present on the page ---
  let modalIds = new Set();
  function refreshModalIds() {
    modalIds = new Set([...document.querySelectorAll('.modal[id]')].map(m => m.id));
  }
  refreshModalIds();

  // Also refresh when DOM changes (e.g., ACF/blocks inject modals)
  const mo = new MutationObserver(() => refreshModalIds());
  mo.observe(document.documentElement, { childList: true, subtree: true });

  // --- Helpers ---
  const openModalById = (id) => {
    if (!id) return false;
    const modal = document.getElementById(id);
    if (!modal) return false;
    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('modal-open');
    return true;
  };

  const closeVisibleModal = () => {
    const visible = document.querySelector('.modal.is-visible');
    if (!visible) return;
    visible.classList.remove('is-visible');
    visible.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('modal-open');
  };

  const getHash = (el) => {
    // Works with <a href="#id"> and <a href="/page#id">
    const raw = el.getAttribute('href');
    if (!raw) return '';
    try {
      // Absolute or relative? URL() needs a base for relative
      const u = new URL(raw, window.location.href);
      return u.hash.replace(/^#/, '');
    } catch {
      // Fallback for malformed hrefs like just "#id"
      const m = raw.match(/#(.+)$/);
      return m ? m[1] : '';
    }
  };

  // Try to derive a target id from the clicked element or its ancestors
  const deriveTargetId = (origin) => {
    // 1) data-modal-target on self or ancestor
    const withData = origin.closest('[data-modal-target]');
    if (withData) {
      const id = withData.getAttribute('data-modal-target')?.trim();
      if (id && modalIds.has(id)) return id;
    }

    // 2) <a> with a hash (anywhere in the URL)
    const link = origin.closest('a[href*="#"]');
    if (link) {
      const id = getHash(link);
      if (id && modalIds.has(id)) return id;
    }

    // 3) Class-based triggers: class equals id or matches open-{id} / js-modal-{id}
    let node = origin;
    while (node && node !== document) {
      if (node.classList && node.classList.length) {
        // exact class equals a modal id
        for (const cls of node.classList) {
          if (modalIds.has(cls)) return cls;
        }
        // open-{id} or js-modal-{id}
        for (const cls of node.classList) {
          const m = cls.match(/^(?:open|js-modal)-(.+)$/);
          if (m && modalIds.has(m[1])) return m[1];
        }
      }
      node = node.parentElement;
    }

    return '';
  };

  // --- OPEN: capture clicks anywhere and try to resolve a target id ---
  document.addEventListener('click', (e) => {
    const id = deriveTargetId(e.target);
    if (!id) return; // nothing to do

    // If the click came from a link with hash, prevent the native jump
    const link = e.target.closest('a[href*="#"]');
    if (link) e.preventDefault();

    openModalById(id);
  });

  // --- CLOSE: close button
  document.addEventListener('click', (e) => {
    const closeBtn = e.target.closest('.modal-close');
    if (!closeBtn) return;
    const modal = closeBtn.closest('.modal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('modal-open');
  });

  // --- CLOSE: overlay click
  document.addEventListener('click', (e) => {
    if (!e.target.classList?.contains('modal-overlay')) return;
    const modal = e.target.closest('.modal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('modal-open');
  });

  // --- CLOSE: Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeVisibleModal();
  });

  // --- Debug helper (optional) ---
  window.addEventListener('DOMContentLoaded', () => {
    // console.log('Modals found:', [...modalIds]);
  });
})();