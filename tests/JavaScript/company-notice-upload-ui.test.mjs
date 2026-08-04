import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../../public/assets/js/company-notice-editor.js', import.meta.url), 'utf8');

test('attachment uploads expose real progress, retry, removal, and live totals', () => {
  for (const required of ['XMLHttpRequest', "xhr.upload.addEventListener('progress'", 'progress max="100"', 'Upload failed', "retry.textContent = 'Retry'", '/remove', 'updateTotals()', '0 B', '1,000 MiB used']) {
    assert.match(source, new RegExp(required.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }
});

test('attachment upload interactions support choosing, dropping, and accessible status', () => {
  for (const required of ["picker?.addEventListener('change'", "'dragenter', 'dragover'", "'dragleave', 'drop'", "setAttribute('aria-label'", 'announce(']) {
    assert.ok(source.includes(required), `Missing accessible upload behavior: ${required}`);
  }
});

test('request contract sends approved fields and diagnoses redirects and non-JSON safely', () => {
  for (const required of ["body.append('_token', csrf)", "body.append('compose_context', compose)", "body.append('file', file)", 'xhr.withCredentials = true', "pathname === '/login'", 'authentication_required', 'unexpected_response', 'invalid_json_response', 'error_code']) {
    assert.ok(source.includes(required), `Missing upload request diagnostic: ${required}`);
  }
  assert.ok(source.indexOf("body.append('compose_context', compose)") < source.indexOf('xhr.send(body)'));
});

test('failed local Remove avoids server deletion and Retry reuses the captured file and context', () => {
  assert.ok(source.includes("retry.addEventListener('click', () => uploadAttachment(file, row))"));
  assert.ok(source.includes("discard.addEventListener('click', () => { failed = Math.max(0, failed - 1); row.remove()"));
  assert.equal((source.match(/body\.append\('compose_context', compose\)/g) || []).length, 2);
});

test('embedded images serialize as stable storage placeholders across review and back-to-edit', () => {
  for (const required of [
    "window.Quill.import('formats/image')",
    "node.setAttribute('src', value.previewUrl)",
    "node.setAttribute('data-storage-object-public-id', publicId)",
    "node.setAttribute('alt', value.alt)",
    "window.Quill.register(GatewayImage, true)",
    "insertEmbed(range.index, 'image', { publicId: asset.public_id, alt, width: 25, align: 'left', previewUrl: temporaryPreviewUrl(asset.public_id) }, 'user')",
    'temporaryPreviewUrl(publicId)',
    "image.setAttribute('src', `gateway-storage:${publicId}`)",
    'rich.value = canonicalEditorHtml()',
  ]) assert.ok(source.includes(required), `Missing stable embedded-image contract: ${required}`);
});

test('new embedded images default to 25 percent and activate resizing from live state', () => {
  assert.ok(source.includes('width: 25'));
  assert.ok(source.includes('if(inserted)selectImage(inserted)'));
  assert.ok(source.includes("node.setAttribute('data-gateway-image-width', String(value.width || 25))"));
  assert.ok(source.includes("image.getAttribute('data-gateway-image-width')"));
});

test('embedded image resizing is controlled, accessible, and preview failures block Review', () => {
  for (const required of ['data-gateway-image-width', 'length: 91', 'canonicalEditorHtml()', "event.key==='Escape'", 'previewFailed', 'Retry failed embedded image previews before Review.', 'Image width changed to ${approved} percent.']) assert.ok(source.includes(required), `Missing image resizing or preview safety: ${required}`);
  assert.ok(!source.includes('previewUrl: asset.url'));
});

test('pointer resize commits one controlled Quill operation and guards stale selection', () => {
  for (const required of [
    "new Parchment.Attributor('gateway-image-width'",
    "formats: ['bold', 'italic', 'list', 'link', 'image', 'gateway-image-width', 'gateway-image-align']",
    "quill.formatText(index,1,'gateway-image-width',String(approved),'user')",
    "addEventListener('pointerdown'",
    "addEventListener('pointermove'",
    "addEventListener('pointerup'",
    "addEventListener('pointercancel'",
    'Math.max(10,Math.min(100',
    'event.pointerId!==drag.pointerId||selectedImageId!==drag.imageId',
    'const activeImage = () => selectedImageId ?',
    "selectedImageId=image.getAttribute('data-storage-object-public-id')",
    'clearImageSelection()',
  ]) assert.ok(source.includes(required), `Missing stable Quill size-format behavior: ${required}`);
  assert.equal((source.match(/quill\.formatText\(index,1,'gateway-image-width'/g) || []).length, 1);
});

test('preset dropdown is gone and pointer/keyboard controls are present', () => {
  const view = readFileSync(new URL('../../resources/views/company-notices/create.php', import.meta.url), 'utf8');
  const css = readFileSync(new URL('../../public/assets/css/gateway.css', import.meta.url), 'utf8');
  assert.ok(!view.includes('company-notice-image-size-select'));
  for (const required of ['company-notice-image-resize-handle', 'Decrease image size', 'Image width: 25%', 'Increase image size']) assert.ok(view.includes(required));
  for (const required of ['cursor:nwse-resize', 'touch-action:none', 'data-gateway-image-selected="true"']) assert.ok(css.includes(required));
  assert.ok(source.includes("decrease?.addEventListener('click'"));
  assert.ok(source.includes("increase?.addEventListener('click'"));
  assert.ok(css.includes('data-gateway-image-selected="true"'));
});

test('image alignment is a controlled Quill format with accessible controls', () => {
  const view = readFileSync(new URL('../../resources/views/company-notices/create.php', import.meta.url), 'utf8');
  for (const required of ["new Parchment.Attributor('gateway-image-align'", "'gateway-image-align': node.getAttribute", "align: 'left'", "quill.formatText(blot.offset(quill.scroll),1,'gateway-image-align'", 'Image aligned ${alignment}.']) assert.ok(source.includes(required));
  for (const alignment of ['left', 'center', 'right']) assert.ok(view.includes(`data-company-notice-image-align="${alignment}"`));
  assert.ok(view.includes('role="group" aria-label="Image alignment"'));
  assert.ok(view.includes('aria-pressed="true">Left'));
});

test('compose and delivered notice bodies share readable typography and alignment', () => {
  const css = readFileSync(new URL('../../public/assets/css/gateway.css', import.meta.url), 'utf8');
  for (const required of ['font-size:16px', 'line-height:1.6', 'margin:0 0 .7rem', 'data-gateway-image-align="left"', 'data-gateway-image-align="center"', 'data-gateway-image-align="right"']) assert.ok(css.includes(required));
  assert.ok(css.includes('.gateway-company-notice-editor .ql-container.ql-snow > .ql-editor[contenteditable="true"]'));
  const header = readFileSync(new URL('../../resources/views/components/header.php', import.meta.url), 'utf8');
  assert.ok(header.indexOf('quill.snow.css') < header.indexOf('/assets/css/gateway.css'));
});
