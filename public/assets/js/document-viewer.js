const SUPPORTED = new Set(['application/pdf', 'image/jpeg', 'image/png']);
const initialized = new WeakSet();

function safeRoute(value) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) return null;
  try {
    const url = new URL(value, window.location.origin);
    return url.origin === window.location.origin && url.username === '' && url.password === '' && url.hash === '' ? url.pathname + url.search : null;
  } catch { return null; }
}

export function initializeDocumentViewer(root = document) {
  const viewer = root.querySelector('[data-document-viewer]');
  if (!(viewer instanceof HTMLElement) || initialized.has(viewer)) return;
  initialized.add(viewer);
  const dialog = viewer.querySelector('[role="dialog"]');
  const title = viewer.querySelector('[data-document-viewer-title]');
  const filename = viewer.querySelector('[data-document-viewer-filename]');
  const frame = viewer.querySelector('[data-document-viewer-frame]');
  const image = viewer.querySelector('[data-document-viewer-img]');
  const pdfPanel = viewer.querySelector('[data-document-viewer-pdf]');
  const imagePanel = viewer.querySelector('[data-document-viewer-image]');
  const loading = viewer.querySelector('[data-document-viewer-loading]');
  const failure = viewer.querySelector('[data-document-viewer-failure]');
  const failureMessage = viewer.querySelector('[data-document-viewer-failure-message]');
  const newTab = viewer.querySelector('[data-document-viewer-new-tab]');
  const download = viewer.querySelector('[data-document-viewer-download]');
  if (!(dialog instanceof HTMLElement) || !(frame instanceof HTMLIFrameElement) || !(image instanceof HTMLImageElement) || !(newTab instanceof HTMLAnchorElement) || !(download instanceof HTMLAnchorElement)) return;
  let opener = null; let scrollY = 0; let inerted = [];
  const showFailure = (message = 'The document could not be displayed.') => { loading.hidden = true; pdfPanel.hidden = true; imagePanel.hidden = true; failureMessage.textContent = message; failure.hidden = false; };
  const loaded = () => { loading.hidden = true; failure.hidden = true; };
  frame.addEventListener('load', loaded); image.addEventListener('load', loaded); image.addEventListener('error', () => showFailure());
  const close = () => { if (viewer.hidden) return; viewer.hidden = true; frame.removeAttribute('src'); image.removeAttribute('src'); pdfPanel.hidden = true; imagePanel.hidden = true; loading.hidden = false; failure.hidden = true; document.body.classList.remove('gateway-document-viewer-open'); inerted.forEach((node) => { node.inert = false; }); inerted = []; window.scrollTo(0, scrollY); opener?.focus(); opener = null; };
  viewer.querySelectorAll('[data-document-viewer-close]').forEach((control) => control.addEventListener('click', close));
  root.querySelectorAll('[data-document-viewer-trigger]').forEach((trigger) => trigger.addEventListener('click', (event) => {
    const viewRoute = safeRoute(trigger.dataset.documentViewUrl); const downloadRoute = safeRoute(trigger.dataset.documentDownloadUrl); const mime = trigger.dataset.documentMime;
    if (!viewRoute || !downloadRoute) return;
    event.preventDefault(); opener = trigger; scrollY = window.scrollY; title.textContent = trigger.dataset.documentTitle || 'Document'; filename.textContent = trigger.dataset.documentFilename || ''; filename.title = trigger.dataset.documentFilename || ''; newTab.href = viewRoute; download.href = downloadRoute; loading.hidden = false; failure.hidden = true; frame.removeAttribute('src'); image.removeAttribute('src'); pdfPanel.hidden = true; imagePanel.hidden = true; viewer.hidden = false; document.body.classList.add('gateway-document-viewer-open'); inerted = [...document.querySelectorAll('.gateway-app-header, .gateway-main > .container > :not([data-document-viewer]), .gateway-footer')]; inerted.forEach((node) => { node.inert = true; });
    if (!SUPPORTED.has(mime)) showFailure('This file type cannot be displayed in Gateway.'); else if (mime === 'application/pdf') { pdfPanel.hidden = false; frame.src = viewRoute; } else { imagePanel.hidden = false; image.alt = trigger.dataset.documentDescription || 'Gateway document'; image.src = viewRoute; }
    dialog.focus();
  }));
  document.addEventListener('keydown', (event) => { if (viewer.hidden) return; if (event.key === 'Escape') { event.preventDefault(); close(); return; } if (event.key === 'Tab') { const focusable = [...viewer.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter((node) => !node.hidden); if (!focusable.length) return; const first = focusable[0]; const last = focusable[focusable.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } } });
}

if (typeof document !== 'undefined') initializeDocumentViewer();
