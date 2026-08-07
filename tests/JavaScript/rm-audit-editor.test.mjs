import test from 'node:test';
import assert from 'node:assert/strict';
import { hasMeaningfulAuditFindings, initializeRmAuditEditor, synchronizeAuditFindings } from '../../public/assets/js/rm-audit-editor.js';

class FakeElement {
  constructor() { this.hidden=true;this.attributes={};this.classList={values:[],add:(v)=>this.classList.values.push(v)};this.listeners={};this.tabIndex=0;this.textContent=''; }
  setAttribute(k,v){this.attributes[k]=v;} removeAttribute(k){delete this.attributes[k];}
  addEventListener(type,fn,options){this.listeners[type]={fn,options};}
}
class FakeQuill {
  constructor(host,options){this.host=host;this.options=options;this.root=new FakeElement();this.text='\n';this.html='<p><br></p>';this.handlers={};this.focused=false;this.clipboard={dangerouslyPasteHTML:(html)=>{this.restored=html;this.html=html||'<p><br></p>';this.text=html?html.replace(/<[^>]+>/g,'')+'\n':'\n';}};FakeQuill.instance=this;}
  getText(){return this.text;} getSemanticHTML(){return this.html;} on(type,fn){this.handlers[type]=fn;} focus(){this.focused=true;}
  change(text,html){this.text=text;this.html=html;this.handlers['text-change']?.();}
}
function fixture(restored=''){
  const field=new FakeElement();field.value=restored;field.required=true;field.removeAttribute=(k)=>{if(k==='required')field.required=false;delete field.attributes[k];};
  const host=new FakeElement();const error=new FakeElement();error.hidden=true;
  const form=new FakeElement();form.querySelector=(selector)=>selector==='#audit_findings_html'?field:selector==='#rm-audit-editor'?host:selector==='[data-rm-audit-findings-error]'?error:null;
  const root={querySelector:(selector)=>selector==='[data-rm-audit-editor]'?form:null};return{root,form,field,host,error};
}

test('empty and whitespace-only Quill structures are not meaningful',()=>{for(const text of ['\n','   \n','\u00a0\n'])assert.equal(hasMeaningfulAuditFindings({getText:()=>text}),false);assert.equal(hasMeaningfulAuditFindings({getText:()=> 'Lease missing\n'}),true);});
test('semantic HTML synchronization preserves bold lists and links',()=>{const field={value:''};for(const html of ['<p><strong>Lease</strong></p>','<ol><li>Lease</li></ol>','<p><a href="https://example.test">Lease</a></p>']){const ok=synchronizeAuditFindings({getText:()=> 'Lease\n',getSemanticHTML:()=>html},field);assert.equal(ok,true);assert.equal(field.value,html);}});
test('initialization restores content and continuously synchronizes text changes',()=>{const f=fixture('<p><strong>Restored</strong></p>');const result=initializeRmAuditEditor(f.root,FakeQuill);assert.ok(result);assert.equal(FakeQuill.instance.restored,'<p><strong>Restored</strong></p>');assert.equal(f.field.required,false);assert.ok(f.field.classList.values.includes('visually-hidden'));FakeQuill.instance.change('New lease\n','<ul><li>New lease</li></ul>');assert.equal(f.field.value,'<ul><li>New lease</li></ul>');});
test('final capture-phase synchronization runs before submission',()=>{const f=fixture();initializeRmAuditEditor(f.root,FakeQuill);FakeQuill.instance.text='Formatted\n';FakeQuill.instance.html='<p><u>Formatted</u></p>';assert.equal(f.form.listeners.submit.options.capture,true);f.form.listeners.submit.fn({preventDefault(){throw new Error('should submit');}});assert.equal(f.field.value,'<p><u>Formatted</u></p>');});
test('empty enhanced editor blocks submission, exposes error, and focuses editor',()=>{const f=fixture();initializeRmAuditEditor(f.root,FakeQuill);let prevented=false;f.form.listeners.submit.fn({preventDefault(){prevented=true;}});assert.equal(prevented,true);assert.equal(f.field.value,'');assert.equal(f.error.hidden,false);assert.equal(f.error.textContent,'Audit Findings are required.');assert.equal(FakeQuill.instance.root.attributes['aria-invalid'],'true');assert.equal(FakeQuill.instance.focused,true);});
test('toolbar and formats remain restricted',()=>{const f=fixture();initializeRmAuditEditor(f.root,FakeQuill);const toolbar=JSON.stringify(FakeQuill.instance.options.modules.toolbar);for(const item of ['bold','italic','underline','ordered','bullet','indent','link','clean'])assert.ok(toolbar.includes(item));for(const item of ['image','video','header','color','font','size','code-block'])assert.ok(!toolbar.includes(item));});
