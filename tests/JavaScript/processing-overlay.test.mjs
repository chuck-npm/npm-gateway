import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.document = { readyState: 'loading', addEventListener() {}, querySelector() { return null; }, querySelectorAll() { return []; } };
globalThis.window = { addEventListener() {} };
const { initializeProcessingOverlays } = await import('../../public/assets/js/processing-overlay.js');

class Control { constructor(disabled=false){this.disabled=disabled;this.dataset={};} }
class Message { constructor(){this.textContent='';} }
class Overlay { constructor(){this.hidden=true;this.attributes={};this.message=new Message();} setAttribute(k,v){this.attributes[k]=v;} querySelector(){return this.message;} }
class Form {
  constructor(message='',valid=true){this.dataset={processingMessage:message};this.valid=valid;this.attributes={};this.listeners=[];this.submit=new Control();this.input={disabled:false};}
  addEventListener(type,fn){if(type==='submit')this.listeners.push(fn);} querySelectorAll(){return [this.submit];} checkValidity(){return this.valid;} setAttribute(k,v){this.attributes[k]=v;} removeAttribute(k){delete this.attributes[k];}
  fire(prevented=false){const event={defaultPrevented:prevented,preventDefault(){this.defaultPrevented=true;}};this.listeners.forEach(fn=>fn(event));return event;}
}
class Root { constructor(forms=[],overlay=null){this.forms=forms;this.overlay=overlay;} querySelector(s){return s==='[data-processing-overlay]'?this.overlay:null;} querySelectorAll(s){return s==='[data-processing-form]'?this.forms:[];} }
const tick=()=>new Promise(resolve=>queueMicrotask(resolve));

test('valid submission activates, disables only submit controls, and blocks a second submit',async()=>{const form=new Form('Working…');const overlay=new Overlay();initializeProcessingOverlays(new Root([form],overlay));form.fire();await tick();assert.equal(overlay.hidden,false);assert.equal(overlay.message.textContent,'Working…');assert.equal(form.submit.disabled,true);assert.equal(form.input.disabled,false);assert.equal(form.attributes['aria-busy'],'true');assert.equal(form.fire().defaultPrevented,true);});
test('prevented and invalid submissions do not activate',async()=>{for(const form of [new Form('',false),new Form('',true)]){const overlay=new Overlay();initializeProcessingOverlays(new Root([form],overlay));form.fire(form.valid);await tick();assert.equal(overlay.hidden,true);assert.equal(form.submit.disabled,false);}});
test('reset restores controls and overlay, and fallback message is safe',async()=>{const form=new Form();const overlay=new Overlay();const controller=initializeProcessingOverlays(new Root([form],overlay));form.fire();await tick();assert.equal(overlay.message.textContent,'Processing your request…');controller.reset();assert.equal(overlay.hidden,true);assert.equal(form.submit.disabled,false);assert.equal(form.attributes['aria-busy'],undefined);});
test('forms initialize independently and reinitialization and missing overlay are safe',async()=>{const one=new Form('One');const two=new Form('Two');const root=new Root([one,two]);initializeProcessingOverlays(root);initializeProcessingOverlays(root);assert.equal(one.listeners.length,1);assert.equal(two.listeners.length,1);one.fire();await tick();assert.equal(one.submit.disabled,true);assert.equal(two.submit.disabled,false);assert.doesNotThrow(()=>initializeProcessingOverlays(new Root()));});
