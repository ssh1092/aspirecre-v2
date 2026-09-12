import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const code=readFileSync(new URL('../assets/property-tabs.js',import.meta.url),'utf8');
function harness(hash=''){
 const listeners={},events=[],history=[],location={hash};let focused;
 const tabs=['property','space','location','due-diligence'].map(id=>({id,attrs:{'aria-controls':id},events:{},getAttribute(key){return this.attrs[key];},setAttribute(key,value){this.attrs[key]=value;},addEventListener(key,fn){this.events[key]=fn;},focus(){focused=id;}}));
 const panels=tabs.map(tab=>({id:tab.id,hidden:false}));
 const root={querySelectorAll:s=>s==='[role="tab"]'?tabs:panels,dispatchEvent:e=>events.push(e.detail.id)};
 const window={addEventListener:(name,fn)=>listeners[name]=fn};
 vm.runInNewContext(code,{window,location,history:{pushState:(_,__,hash)=>{location.hash=hash;history.push(hash);}},CustomEvent:class{constructor(name,options){this.detail=options.detail;}}});window.AspirePropertyTabs(root);
 return {tabs,panels,events,history,location,listeners,get focused(){return focused;},click:index=>tabs[index].events.click({preventDefault(){}}),key:(index,key)=>tabs[index].events.keydown({key,preventDefault(){}})};
}
test('one visible panel and one keyboard tab stop, including deep links',()=>{for(const hash of ['','#space','#location','#due-diligence','#invalid']){const h=harness(hash);assert.equal(h.panels.filter(p=>!p.hidden).length,1);assert.equal(h.tabs.filter(t=>t.tabIndex===0).length,1);assert.equal(h.events[0],['#space','#location','#due-diligence'].includes(hash)?hash.slice(1):'property');}});
test('selection pushes history once and Back/Forward restores disclosure',()=>{const h=harness();h.click(1);h.click(2);h.click(2);assert.deepEqual(h.history,['#space','#location']);h.location.hash='#space';h.listeners.popstate();assert.equal(h.panels[1].hidden,false);assert.equal(h.panels[2].hidden,true);h.location.hash='#location';h.listeners.hashchange();assert.equal(h.panels[2].hidden,false);});
test('arrow navigation wraps, Home and End select and focus modes',()=>{const h=harness();h.key(0,'ArrowLeft');assert.equal(h.focused,'due-diligence');h.key(3,'ArrowRight');assert.equal(h.focused,'property');h.key(0,'End');assert.equal(h.focused,'due-diligence');h.key(3,'Home');assert.equal(h.focused,'property');});
const mapCode=readFileSync(new URL('../assets/property-map.js',import.meta.url),'utf8');
function mapHarness(){let visible=false,change;const assets=[],status={textContent:''};const root={dataset:{mapStyle:'/local/map.css'},querySelector:()=>status,getClientRects:()=>visible?[{}]:[]};const document={querySelector:s=>s==='[data-property-map]'?root:{addEventListener:(_,fn)=>change=fn},createElement:()=>({}),head:{append:el=>assets.push(el)}};vm.runInNewContext(mapCode,{document,window:{dispatchEvent(){}},Event:class{}});return {assets,status,open(){visible=true;change();}};}
test('single-property map never loads while its panel is hidden and loads only once',()=>{const h=mapHarness();assert.equal(h.assets.length,0);h.open();h.open();assert.equal(h.assets.length,1);assert.equal(h.assets[0].href,'/local/map.css');});
test('map asset failure leaves an address fallback',async()=>{const h=mapHarness();h.open();h.assets[0].onerror();await Promise.resolve();assert.match(h.status.textContent,/map is unavailable.*address is shown above/);});
