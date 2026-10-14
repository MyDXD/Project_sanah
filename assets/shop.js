document.addEventListener("submit", (event) => {
  const form = event.target;
  if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  if (form.checkValidity()) {
    const button = form.querySelector(
      'button[type="submit"],button:not([type])',
    );
    if (button) {
      setTimeout(() => {
        button.disabled = true;
      }, 0);
    }
  }
});
document.addEventListener("click", (event) => {
  if (event.target.matches("[data-print]")) window.print();
});
