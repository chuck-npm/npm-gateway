const root = document.getElementById('company-notice-editor');

if (root && !root.dataset.initialized) {
  root.dataset.initialized = 'true';
  const form = root.closest('form');
  const fallback = document.getElementById('company-notice-message');
  const rich = document.getElementById('company-notice-rich-message');
  const csrf = form?.querySelector('[name="_token"]')?.value || '';
  const compose = form?.querySelector('[name="compose_context"]')?.value || '';
  const status = document.getElementById('company-notice-upload-status');
  const table = document.getElementById('company-notice-asset-list');
  const list = table?.querySelector('tbody');
  const empty = document.getElementById('company-notice-upload-empty');
  const fileTotal = document.getElementById('company-notice-file-total');
  const spaceTotal = document.getElementById('company-notice-space-total');
  const picker = document.getElementById('company-notice-attachments');
  const dropZone = document.getElementById('company-notice-drop-zone');
  const maxFiles = 10;
  const maxBytes = 1000 * 1024 * 1024;
  let busy = 0;
  let failed = 0;
  let previewFailed = 0;
  let quill = null;

  const announce = (message, focus = false) => {
    status.textContent = '';
    window.setTimeout(() => { status.textContent = message; if (focus) status.focus(); }, 0);
  };
  const formatSize = (bytes) => bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
  const rows = () => [...(list?.querySelectorAll('tr') || [])];
  const updateTotals = () => {
    const current = rows();
    const bytes = current.reduce((total, row) => total + Number(row.dataset.byteSize || 0), 0);
    fileTotal.textContent = `${current.length} file${current.length === 1 ? '' : 's'} selected (${current.length} of ${maxFiles})`;
    spaceTotal.textContent = `${bytes ? formatSize(bytes) : '0 B'} of 1,000 MiB used`;
    empty.hidden = current.length > 0;
    table.hidden = current.length === 0;
  };
  const fileIcon = (type) => type === 'ZIP' ? '📦' : ['JPG', 'JPEG', 'PNG', 'WEBP'].includes(type.toUpperCase()) ? '🖼' : '📄';
  const statusCell = (row) => row.querySelector('[data-upload-state]');
  const makeRow = (file) => {
    const row = document.createElement('tr');
    const type = file.name.split('.').pop()?.toUpperCase() || 'File';
    row.dataset.byteSize = String(file.size);
    row.innerHTML = `<td data-label="File"><span aria-hidden="true">${fileIcon(type)}</span> <span class="gateway-upload-name"></span></td><td data-label="Size">${formatSize(file.size)}</td><td data-label="Type"></td><td data-label="Status" data-upload-state></td><td data-label="Action"></td>`;
    row.querySelector('.gateway-upload-name').textContent = file.name;
    row.children[2].textContent = type;
    list.append(row);
    updateTotals();
    return row;
  };
  const setProgress = (row, percent) => {
    statusCell(row).innerHTML = `<div class="gateway-upload-progress"><progress max="100" value="${percent}" aria-label="Uploading ${row.querySelector('.gateway-upload-name').textContent}: ${percent}%"></progress><div>${percent}% &mdash; Uploading...</div></div>`;
  };
  class UploadRequestError extends Error {
    constructor(message, code, statusCode) { super(message); this.name = 'UploadRequestError'; this.code = code; this.statusCode = statusCode; }
  }
  const requestUpload = (file, url, onProgress) => new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const body = new FormData();
    body.append('_token', csrf); body.append('compose_context', compose); body.append('file', file);
    xhr.open('POST', url); xhr.responseType = 'text'; xhr.withCredentials = true;
    xhr.upload.addEventListener('progress', (event) => { if (event.lengthComputable) onProgress(Math.round(event.loaded / event.total * 100)); });
    xhr.addEventListener('load', () => {
      const contentType = xhr.getResponseHeader('Content-Type') || '';
      const loginRedirect = xhr.responseURL && new URL(xhr.responseURL, window.location.href).pathname === '/login';
      if (loginRedirect) { reject(new UploadRequestError('Your session expired. Sign in and try again.', 'authentication_required', xhr.status)); return; }
      let json = null;
      if (contentType.toLowerCase().includes('application/json')) { try { json = JSON.parse(xhr.responseText); } catch { reject(new UploadRequestError('The server returned an invalid upload response.', 'invalid_json_response', xhr.status)); return; } }
      if (!json) { reject(new UploadRequestError('The server returned an unexpected upload response.', 'unexpected_response', xhr.status)); return; }
      if (xhr.status >= 200 && xhr.status < 300 && json.ok === true && json.asset) { resolve(json.asset); return; }
      reject(new UploadRequestError(json.message || 'The file could not be uploaded.', json.error_code || 'upload_failed', xhr.status));
    });
    xhr.addEventListener('error', () => reject(new UploadRequestError('The upload request could not reach the server.', 'network_failure', 0)));
    xhr.send(body);
  });
  const remove = async (row) => {
    const publicId = row.dataset.publicId;
    const button = row.querySelector('.gateway-upload-remove');
    button.disabled = true; busy += 1;
    const body = new FormData(); body.append('_token', csrf); body.append('compose_context', compose);
    try {
      const response = await fetch(`/company-notices/uploads/${publicId}/remove`, { method: 'POST', body, credentials: 'same-origin' });
      if (!response.ok) throw new Error((await response.json()).error || 'The file could not be removed.');
      const name = row.querySelector('.gateway-upload-name')?.textContent || row.children[0].textContent.trim();
      row.remove(); updateTotals(); announce(`${name} removed.`);
    } catch (error) { button.disabled = false; announce(error instanceof Error ? error.message : 'The file could not be removed.'); }
    finally { busy -= 1; }
  };
  const completeRow = (row, asset) => {
    row.dataset.publicId = asset.public_id;
    row.dataset.byteSize = String(asset.byte_size);
    row.children[1].textContent = asset.formatted_size;
    row.children[2].textContent = asset.type_label;
    statusCell(row).innerHTML = '<span class="gateway-status gateway-status--success"><span aria-hidden="true">✓</span> Uploaded</span>';
    const action = row.children[4];
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'btn btn-link gateway-upload-remove';
    button.setAttribute('aria-label', `Remove ${asset.display_filename}`);
    button.innerHTML = '<span aria-hidden="true">🗑</span> Remove';
    button.addEventListener('click', () => remove(row)); action.replaceChildren(button);
    updateTotals(); announce(`${asset.display_filename} uploaded.`);
  };
  const uploadAttachment = async (file, row = makeRow(file)) => {
    busy += 1; failed = Math.max(0, failed - (row.dataset.failed === 'true' ? 1 : 0)); delete row.dataset.failed; setProgress(row, 0);
    try { completeRow(row, await requestUpload(file, picker.dataset.uploadUrl, (percent) => setProgress(row, percent))); }
    catch (error) {
      failed += 1; row.dataset.failed = 'true';
      statusCell(row).innerHTML = '<span><span aria-hidden="true">⚠</span> Upload failed</span>';
      const retry = document.createElement('button'); retry.type = 'button'; retry.className = 'btn btn-link'; retry.textContent = 'Retry';
      const discard = document.createElement('button'); discard.type = 'button'; discard.className = 'btn btn-link gateway-upload-remove'; discard.textContent = 'Remove'; discard.setAttribute('aria-label', `Remove failed upload ${file.name}`);
      retry.addEventListener('click', () => uploadAttachment(file, row));
      discard.addEventListener('click', () => { failed = Math.max(0, failed - 1); row.remove(); updateTotals(); announce(`${file.name} removed.`); });
      row.children[4].replaceChildren(retry, discard);
      if (error instanceof UploadRequestError) console.warn('Company Notice upload failed', { code: error.code, status: error.statusCode });
      announce(error instanceof Error ? error.message : 'Upload failed.');
    } finally { busy -= 1; }
  };
  const addFiles = (files) => {
    const available = maxFiles - rows().length;
    const accepted = [...files].slice(0, Math.max(0, available));
    if (accepted.length < files.length) announce(`Only ${available} more file${available === 1 ? '' : 's'} can be selected.`);
    for (const file of accepted) uploadAttachment(file);
  };

  rows().forEach((row) => row.querySelector('.gateway-upload-remove')?.addEventListener('click', () => remove(row)));
  updateTotals();

  const uploadImage = async (file, url) => {
    busy += 1;
    try { const asset = await requestUpload(file, url, () => {}); announce(`${asset.display_filename} uploaded.`); return asset; }
    catch (error) { failed += 1; announce(error instanceof Error ? error.message : 'Upload failed.'); throw error; }
    finally { busy -= 1; }
  };

  try {
    if (typeof window.Quill !== 'function') throw new Error('Quill unavailable');
    const Parchment = window.Quill.import('parchment');
    const approvedWidths = Array.from({ length: 91 }, (_, index) => String(index + 10));
    const GatewayImageWidth = new Parchment.Attributor('gateway-image-width', 'data-gateway-image-width', { scope: Parchment.Scope.INLINE, whitelist: approvedWidths });
    window.Quill.register(GatewayImageWidth, true);
    const GatewayImageAlign = new Parchment.Attributor('gateway-image-align', 'data-gateway-image-align', { scope: Parchment.Scope.INLINE, whitelist: ['left', 'center', 'right'] });
    window.Quill.register(GatewayImageAlign, true);
    const BaseImage = window.Quill.import('formats/image');
    class GatewayImage extends BaseImage {
      static create(value) {
        const publicId = typeof value === 'object' ? value.publicId : '';
        const node = super.create(publicId ? value.previewUrl : value);
        if (publicId) {
          node.setAttribute('src', value.previewUrl);
          node.setAttribute('data-storage-object-public-id', publicId);
          node.setAttribute('alt', value.alt);
          node.setAttribute('data-gateway-image-width', String(value.width || 25));
          node.setAttribute('data-gateway-image-align', value.align || 'left');
          node.setAttribute('tabindex', '0');
        }
        return node;
      }
      static formats(node) {
        return {
          ...super.formats(node),
          alt: node.getAttribute('alt') || '',
          storagePublicId: node.getAttribute('data-storage-object-public-id') || '',
          'gateway-image-width': node.getAttribute('data-gateway-image-width') || '50',
          'gateway-image-align': node.getAttribute('data-gateway-image-align') || 'left',
        };
      }
      format(name, value) {
        if (name === 'alt') this.domNode.setAttribute('alt', value);
        else if (name === 'storagePublicId') this.domNode.setAttribute('data-storage-object-public-id', value);
        else if (name === 'gateway-image-width') this.domNode.setAttribute('data-gateway-image-width', approvedWidths.includes(String(value)) ? String(value) : '50');
        else if (name === 'gateway-image-align') this.domNode.setAttribute('data-gateway-image-align', ['left', 'center', 'right'].includes(value) ? value : 'left');
        else super.format(name, value);
      }
    }
    GatewayImage.blotName = 'image';
    GatewayImage.tagName = 'IMG';
    window.Quill.register(GatewayImage, true);
    const temporaryPreviewUrl = (publicId) => `/company-notices/uploads/${publicId}/preview?compose_context=${encodeURIComponent(compose)}`;
    const hydrateEditorImages = () => {
      for (const image of quill.root.querySelectorAll('img[data-storage-object-public-id]')) {
        const publicId = image.getAttribute('data-storage-object-public-id') || '';
        const legacyWidth = { small: 25, medium: 50, large: 75, full: 100 }[image.getAttribute('data-gateway-image-size')] || 50;
        const width = approvedWidths.includes(image.getAttribute('data-gateway-image-width')) ? Number(image.getAttribute('data-gateway-image-width')) : legacyWidth;
        image.removeAttribute('data-gateway-image-size');
        image.setAttribute('data-gateway-image-width', String(width));
        if (!['left', 'center', 'right'].includes(image.getAttribute('data-gateway-image-align'))) image.setAttribute('data-gateway-image-align', 'left');
        image.style.width = `${width}%`; image.style.height = 'auto'; image.style.maxWidth = '100%';
        image.setAttribute('tabindex', '0');
        if (/^[0-9A-HJKMNP-TV-Z]{26}$/.test(publicId)) {
          const previewUrl = temporaryPreviewUrl(publicId); if (image.getAttribute('src') !== previewUrl) image.setAttribute('src', previewUrl);
          if (image.dataset.previewEvents !== 'bound') { image.dataset.previewEvents = 'bound';
            image.addEventListener('load', () => { if (image.dataset.previewState === 'failed') previewFailed = Math.max(0, previewFailed - 1); image.dataset.previewState = 'loaded'; const retry=document.getElementById('company-notice-image-preview-retry');if(retry)retry.hidden=true; });
            image.addEventListener('error', () => { if (image.dataset.previewState !== 'failed') previewFailed += 1; image.dataset.previewState = 'failed'; const retry=document.getElementById('company-notice-image-preview-retry');if(retry)retry.hidden=false;announce('Embedded image preview failed. Retry the preview before Review.', true); });
          }
        }
      }
    };
    const canonicalEditorHtml = () => {
      const copy = quill.root.cloneNode(true);
      for (const image of copy.querySelectorAll('img[data-storage-object-public-id]')) {
        const publicId = image.getAttribute('data-storage-object-public-id') || '';
        if (/^[0-9A-HJKMNP-TV-Z]{26}$/.test(publicId)) image.setAttribute('src', `gateway-storage:${publicId}`);
        image.removeAttribute('style'); image.removeAttribute('data-gateway-image-selected'); image.removeAttribute('data-preview-state'); image.removeAttribute('data-preview-events'); image.removeAttribute('tabindex');
      }
      return copy.innerHTML;
    };
    const icons = window.Quill.import('ui/icons'); icons.undo = '↶'; icons.redo = '↷';
    quill = new window.Quill(root, { theme: 'snow', formats: ['bold', 'italic', 'list', 'link', 'image', 'gateway-image-width', 'gateway-image-align'], modules: { history: { delay: 500, maxStack: 100, userOnly: true }, toolbar: { container: [['undo', 'redo'], ['bold', 'italic'], [{ list: 'bullet' }, { list: 'ordered' }], ['link', 'image']], handlers: { undo() { this.quill.history.undo(); }, redo() { this.quill.history.redo(); }, image() {
      const toolbar = this; const imagePicker = document.createElement('input'); imagePicker.type = 'file'; imagePicker.accept = '.jpg,.jpeg,.png,.webp';
      imagePicker.onchange = async () => { const file = imagePicker.files?.[0]; if (!file) return; const alt = window.prompt('Describe this image for people who cannot see it:', '')?.trim() || ''; if (!alt) { announce('Meaningful image alt text is required.'); return; }
        try { const asset = await uploadImage(file, root.dataset.imageUpload); const range = toolbar.quill.getSelection(true); toolbar.quill.insertEmbed(range.index, 'image', { publicId: asset.public_id, alt, width: 25, align: 'left', previewUrl: temporaryPreviewUrl(asset.public_id) }, 'user'); hydrateEditorImages(); const inserted=quill.root.querySelector(`img[data-storage-object-public-id="${asset.public_id}"]`);if(inserted)selectImage(inserted); } catch { /* Announced by upload helper. */ }
      }; imagePicker.click();
    } } } } });
    quill.root.setAttribute('aria-describedby', 'company-notice-message-help');
    if (rich.value) { quill.clipboard.dangerouslyPasteHTML(rich.value); hydrateEditorImages(); } else if (fallback.value) quill.setText(fallback.value);
    fallback.hidden = true; fallback.required = false;
    const resizeControl = document.getElementById('company-notice-image-resize-controls');
    const resizeHandle = document.getElementById('company-notice-image-resize-handle');
    const widthOutput = document.getElementById('company-notice-image-width');
    const decrease = document.getElementById('company-notice-image-decrease');
    const increase = document.getElementById('company-notice-image-increase');
    const alignButtons = [...document.querySelectorAll('[data-company-notice-image-align]')];
    const previewRetry = document.getElementById('company-notice-image-preview-retry');
    let selectedImageId = '';
    let drag = null;
    const activeImage = () => selectedImageId ? quill.root.querySelector(`img[data-storage-object-public-id="${selectedImageId}"]`) : null;
    const imageWidth = (image) => approvedWidths.includes(image?.getAttribute('data-gateway-image-width')) ? Number(image.getAttribute('data-gateway-image-width')) : 50;
    const positionHandle = () => { const image=activeImage();if(!image||resizeHandle.hidden)return;const imageRect=image.getBoundingClientRect();const wrapperRect=root.parentElement.getBoundingClientRect();resizeHandle.style.left=`${imageRect.right-wrapperRect.left}px`;resizeHandle.style.top=`${imageRect.bottom-wrapperRect.top}px`; };
    const showWidth = (width) => { widthOutput.textContent=`Image width: ${width}%`; };
    const showAlignment = (image) => { const alignment=image?.getAttribute('data-gateway-image-align')||'left';for(const button of alignButtons)button.setAttribute('aria-pressed',String(button.dataset.companyNoticeImageAlign===alignment)); };
    const clearImageSelection = () => { const image=activeImage();if(image)image.removeAttribute('data-gateway-image-selected');selectedImageId='';drag=null;resizeControl.hidden=true;resizeHandle.hidden=true; };
    const selectImage = (image) => { const previous=activeImage();if(previous&&previous!==image)previous.removeAttribute('data-gateway-image-selected');selectedImageId=image.getAttribute('data-storage-object-public-id')||'';image.setAttribute('data-gateway-image-selected','true');showWidth(imageWidth(image));showAlignment(image);resizeControl.hidden=false;resizeHandle.hidden=false;positionHandle(); };
    const commitWidth = (width) => { const image=activeImage();if(!image)return false;const blot=window.Quill.find(image);if(!blot||typeof blot.offset!=='function'){clearImageSelection();return false;}const approved=Math.max(10,Math.min(100,Math.round(width)));const index=blot.offset(quill.scroll);quill.formatText(index,1,'gateway-image-width',String(approved),'user');const current=activeImage();if(!current)return false;current.style.width=`${approved}%`;current.style.height='auto';current.style.maxWidth='100%';current.setAttribute('data-gateway-image-selected','true');showWidth(approved);rich.value=canonicalEditorHtml();positionHandle();announce(`Image width changed to ${approved} percent.`);return true; };
    quill.on('text-change', () => { hydrateEditorImages();rich.value=canonicalEditorHtml();fallback.value=quill.getText().trim();const image=activeImage();if(image){image.setAttribute('data-gateway-image-selected','true');showWidth(imageWidth(image));showAlignment(image);positionHandle();}else if(selectedImageId)clearImageSelection(); });
    quill.root.addEventListener('click', (event) => { const image = event.target.closest?.('img[data-storage-object-public-id]'); if (image) selectImage(image); });
    quill.root.addEventListener('focusin', (event) => { const image = event.target.closest?.('img[data-storage-object-public-id]'); if (image) selectImage(image); });
    decrease?.addEventListener('click', () => { const image=activeImage();if(image)commitWidth(imageWidth(image)-10); });
    increase?.addEventListener('click', () => { const image=activeImage();if(image)commitWidth(imageWidth(image)+10); });
    for(const button of alignButtons)button.addEventListener('click',()=>{const image=activeImage();if(!image)return;const alignment=button.dataset.companyNoticeImageAlign;const blot=window.Quill.find(image);if(!blot||typeof blot.offset!=='function'){clearImageSelection();return;}quill.formatText(blot.offset(quill.scroll),1,'gateway-image-align',alignment,'user');const current=activeImage();if(current){current.setAttribute('data-gateway-image-selected','true');showAlignment(current);positionHandle();}rich.value=canonicalEditorHtml();announce(`Image aligned ${alignment}.`);});
    resizeHandle?.addEventListener('pointerdown', (event) => { const image=activeImage();if(!image)return;event.preventDefault();const editorWidth=quill.root.clientWidth;const renderedWidth=image.getBoundingClientRect().width;if(editorWidth<=0||renderedWidth<=0)return;drag={pointerId:event.pointerId,startX:event.clientX,startWidth:renderedWidth,editorWidth,imageId:selectedImageId,width:imageWidth(image)};resizeHandle.setPointerCapture?.(event.pointerId); });
    resizeHandle?.addEventListener('pointermove', (event) => { if(!drag||event.pointerId!==drag.pointerId||selectedImageId!==drag.imageId)return;const image=activeImage();if(!image){clearImageSelection();return;}const pixels=Math.max(drag.editorWidth*.1,Math.min(drag.editorWidth,drag.startWidth+event.clientX-drag.startX));drag.width=Math.max(10,Math.min(100,Math.round(pixels/drag.editorWidth*100)));image.style.width=`${drag.width}%`;image.style.height='auto';image.style.maxWidth='100%';showWidth(drag.width);positionHandle(); });
    const finishDrag = (event) => { if(!drag||event.pointerId!==drag.pointerId)return;const width=drag.width;drag=null;resizeHandle.releasePointerCapture?.(event.pointerId);commitWidth(width); };
    resizeHandle?.addEventListener('pointerup', finishDrag);
    resizeHandle?.addEventListener('pointercancel', (event) => { if(!drag||event.pointerId!==drag.pointerId)return;const image=activeImage();if(image)image.style.width=`${imageWidth(image)}%`;resizeHandle.releasePointerCapture?.(event.pointerId);drag=null;positionHandle(); });
    previewRetry?.addEventListener('click', () => { const image=activeImage();if(!image)return;const publicId=image.getAttribute('data-storage-object-public-id')||'';image.dataset.previewState='retrying';image.removeAttribute('src');image.setAttribute('src',temporaryPreviewUrl(publicId));announce('Retrying embedded image preview.'); });
    document.addEventListener('keydown', (event) => { if(event.key==='Escape'&&activeImage()){const image=activeImage();clearImageSelection();image?.focus();} });
    document.addEventListener('pointerdown',(event)=>{if(!resizeControl.contains(event.target)&&event.target!==resizeHandle&&!event.target.closest?.('img[data-storage-object-public-id]'))clearImageSelection();});
    window.addEventListener('resize',positionHandle);quill.root.addEventListener('scroll',positionHandle);
    for (const type of ['drop', 'paste']) quill.root.addEventListener(type, (event) => { const image = type === 'drop' ? event.dataTransfer?.files?.length : [...event.clipboardData.items].some((item) => item.type.startsWith('image/')); if (image) { event.preventDefault(); announce('Use Insert Image to upload images.'); } });
  } catch { root.hidden = true; fallback.hidden = false; fallback.required = true; announce('Rich editor unavailable; plain-text editing remains available.'); }

  picker?.addEventListener('change', (event) => { addFiles(event.target.files); event.target.value = ''; });
  for (const type of ['dragenter', 'dragover']) dropZone?.addEventListener(type, (event) => { event.preventDefault(); dropZone.classList.add('is-dragging'); });
  for (const type of ['dragleave', 'drop']) dropZone?.addEventListener(type, (event) => { event.preventDefault(); dropZone.classList.remove('is-dragging'); if (type === 'drop') addFiles(event.dataTransfer.files); });
  form?.addEventListener('submit', (event) => { if (busy || failed || previewFailed) { event.preventDefault(); event.stopImmediatePropagation(); announce(busy ? 'Wait for uploads to finish.' : (previewFailed ? 'Retry failed embedded image previews before Review.' : 'Resolve or remove failed uploads before review.'), true); return; } if (quill) { rich.value = canonicalEditorHtml(); fallback.value = quill.getText().trim(); } });
}
