import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const code=readFileSync(new URL('../assets/js/property-dossier.js',import.meta.url),'utf8');
function page({mapped=false,reduced=false}={}){
 const calls=[],links=[],status={textContent:''},root={dataset:{mapStyle:'/local/map.css'},querySelector:()=>status};let notify;
 class Observer{constructor(callback){notify=callback;}observe(el){calls.push(['observe',el]);}disconnect(){calls.push(['disconnect']);}}
 const target={focus:options=>calls.push(['focus',options]),scrollIntoView:options=>calls.push(['scroll',options])};
 const link={hash:'#property-location',addEventListener:(name,callback)=>links.push(callback)};
 const dossier={querySelectorAll:()=>[link],querySelector:()=>mapped?root:null};
 const document={querySelector:()=>dossier,getElementById:()=>target,createElement:()=>({}),head:{append:el=>calls.push(['asset',el])}};
 vm.runInNewContext(code,{document,window:{IntersectionObserver:Observer},IntersectionObserver:Observer,matchMedia:()=>({matches:reduced}),history:{replaceState:(_,__,hash)=>calls.push(['history',hash])}});
 return {calls,links,status,notify:entries=>notify(entries),target};
}
test('unmapped property never observes or requests map assets',()=>{const p=page();assert.equal(p.calls.length,0);});
test('mapped property defers map assets until approaching Location',()=>{const p=page({mapped:true});assert.equal(p.calls[0][0],'observe');assert.equal(p.calls.some(c=>c[0]==='asset'),false);p.notify([{isIntersecting:false}]);assert.equal(p.calls.some(c=>c[0]==='asset'),false);p.notify([{isIntersecting:true}]);assert.equal(p.calls.filter(c=>c[0]==='asset').length,1);assert.equal(p.calls.find(c=>c[0]==='asset')[1].href,'/local/map.css');assert.ok(p.calls.some(c=>c[0]==='disconnect'));});
test('failed map stylesheet leaves the property address fallback usable',async()=>{const p=page({mapped:true});p.notify([{isIntersecting:true}]);p.calls.find(c=>c[0]==='asset')[1].onerror();await Promise.resolve();assert.match(p.status.textContent,/map is unavailable.*address is shown above/);});
test('section navigation moves focus and honors reduced motion',()=>{for(const reduced of [true,false]){const p=page({reduced});let prevented=false;p.links[0]({preventDefault:()=>prevented=true});assert.ok(prevented);assert.equal(p.target.tabIndex,-1);assert.equal(p.calls.find(c=>c[0]==='history')[1],'#property-location');assert.equal(p.calls.find(c=>c[0]==='scroll')[1].behavior,reduced?'instant':'smooth');assert.ok(p.calls.find(c=>c[0]==='focus')[1].preventScroll);}});
