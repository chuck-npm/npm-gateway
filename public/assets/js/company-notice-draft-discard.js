const dialog = document.getElementById('company-notice-discard-dialog');

if (dialog instanceof HTMLDialogElement) {
  const openers = [...document.querySelectorAll('[data-discard-open]')];
  const cancel = dialog.querySelector('[data-discard-cancel]');
  const announcement = dialog.querySelector('[data-discard-announcement]');
  let opener = null;

  const close = () => {
    dialog.close();
    opener?.focus();
  };

  for (const button of openers) button.addEventListener('click', () => {
    opener = button;
    dialog.showModal();
    if (announcement) announcement.textContent = 'Discard draft confirmation opened.';
    cancel?.focus();
  });
  cancel?.addEventListener('click', close);
  dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
  dialog.addEventListener('close', () => { if (announcement) announcement.textContent = ''; });
}
