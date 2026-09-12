import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const code=readFileSync(new URL('../assets/js/property-dossier.js',import.meta.url),'utf8');
function galleryPage(){
 const calls=[],listeners={},photoListeners=[],classes=new Set();let active;
 const element=name=>({name,addEventListener:(event,fn)=>listeners[name+':'+event]=fn,focus(){active=this;},removeAttribute(key){delete this[key];}});
 const close=element('close'),prev=element('prev'),next=element('next'),image=element('image'),count=element('count'),caption=element('caption'),opener=element('opener');opener.dataset={galleryOpen:'0'};
 const photos=[{src:'/one.webp',srcset:'/one-small.webp 500w',alt:'Property exterior'},{src:'/two.webp',srcset:'/two-small.webp 500w',alt:'Property interior'}];
 const dialog=element('dialog');dialog.open=false;dialog.showModal=()=>dialog.open=true;dialog.close=()=>{dialog.open=false;listeners['dialog:close']();};dialog.querySelectorAll=()=>[close,prev,next];dialog.querySelector=selector=>({'[data-gallery-data]':{textContent:JSON.stringify(photos)},'[data-gallery-close]':close,'[data-gallery-prev]':prev,'[data-gallery-next]':next,'[data-gallery-image]':image,'[data-gallery-count]':count,'[data-gallery-caption]':caption}[selector]);
 const dossier={querySelector:selector=>selector==='.dossier-viewer'?dialog:null,querySelectorAll:selector=>selector==='[data-gallery-open]'?[opener]:[]};
 const document={querySelector:()=>dossier,documentElement:{classList:{add:s=>classes.add(s),remove:s=>classes.delete(s)}},get activeElement(){return active;}};
 vm.runInNewContext(code,{document});
 const trigger=(name,event,extra={})=>listeners[name+':'+event]({preventDefault(){calls.push('prevent');},...extra});
 return {trigger,dialog,opener,image,count,classes,close,prev,next,get active(){return active;}};
}
test('gallery defers image loading, opens accessible modal and wraps next/previous',()=>{const p=galleryPage();assert.equal(p.image.src,undefined);p.trigger('opener','click');assert.equal(p.dialog.open,true);assert.equal(p.active,p.close);assert.equal(p.image.alt,'Property exterior');assert.equal(p.count.textContent,'1 / 2');assert.ok(p.classes.has('dossier-gallery-locked'));p.trigger('dialog','keydown',{key:'ArrowLeft'});assert.equal(p.count.textContent,'2 / 2');p.trigger('next','click');assert.equal(p.count.textContent,'1 / 2');});
test('gallery traps focus and Escape restores opener and background scrolling',()=>{const p=galleryPage();p.trigger('opener','click');p.trigger('dialog','keydown',{key:'Tab',shiftKey:true});assert.equal(p.active,p.next);p.trigger('dialog','keydown',{key:'Tab',shiftKey:false});assert.equal(p.active,p.close);p.trigger('dialog','keydown',{key:'Escape'});assert.equal(p.dialog.open,false);assert.equal(p.classes.size,0);assert.equal(p.active,p.opener);assert.equal(p.image.src,undefined);});
