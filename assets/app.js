(function () {
  const input = document.getElementById('password');
  const btn = document.querySelector('.toggle-eye');

  if (!input || !btn) return;

  btn.addEventListener('click', () => {
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.setAttribute('aria-pressed', String(isHidden));
    btn.textContent = isHidden ? '🙈' : '👁';
  });
})();