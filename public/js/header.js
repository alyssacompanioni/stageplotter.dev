document.addEventListener('click', function(e) {
  const toggle = document.getElementById('dropdown-menu-toggle');
  if (!toggle) return;

  const userMenu = document.querySelector('.user-menu');
  if (!userMenu.contains(e.target)) {
    toggle.checked = false;
  }
});
