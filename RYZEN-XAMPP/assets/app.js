document.addEventListener('DOMContentLoaded', () => {
  const menu = document.getElementById('mobileMenu');
  const sidebar = document.getElementById('sidebar');
  if (menu && sidebar) menu.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('button[type="submit"],button:not([type])');
      if (btn && !form.hasAttribute('onsubmit')) {
        btn.dataset.original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Memproses...';
      }
    });
  });
});
