import {AREAS,defaultFilters,filterProperties,propertyMetric} from './filters.js';
import './find-space.css';

// One state owner for controls, accessible cards, and the map adapter. No network work here.
export function createFindSpace(root, reduced) {
 const state={mode:'explore',filters:defaultFilters(),selectedPropertyId:null,hoveredPropertyId:null};
 const opening=root.querySelector('.atlas-opening'), find=root.querySelector('.atlas-find');
 const list=find.querySelector('.atlas-result-list'), results=find.querySelector('.atlas-results');
 const count=find.querySelector('.atlas-result-count'), empty=find.querySelector('.atlas-empty');
 const selectedLabel=find.querySelector('.atlas-result-selection');
 const controls=[...find.querySelectorAll('[data-filter]')];
 let features=[],visible=[],loaded=false,error=false,adapter=()=>{};
 const cards=new Map();
 const area=find.querySelector('[data-filter="area"]');
 for(const [value,preset] of Object.entries(AREAS)) area.add(new Option(preset.label,value));
 const emit=reason=>{
  root.dataset.mode=state.mode;
  root.dataset.selectedProperty=state.selectedPropertyId??'';
  root.dataset.hoveredProperty=state.hoveredPropertyId??'';
  root.dataset.resultCount=String(visible.length);
  for(const [id,card] of cards){
   card.setAttribute('aria-pressed',String(id===state.selectedPropertyId));
   card.classList.toggle('is-hovered',id===state.hoveredPropertyId);
  }
  adapter(state,visible,reason);
 };
 function hover(id) {if(state.hoveredPropertyId===id)return;state.hoveredPropertyId=id;emit('hover');}
 function select(id,origin='card') {
  if(id!==null && !visible.some(f=>f.id===id))return;
  state.selectedPropertyId=id;
  const f=features.find(f=>f.id===id);
  selectedLabel.textContent=f?`${f.properties.title} selected.`:'';
  emit(origin);
  // Scroll only the horizontal rail: never move the whole document or steal keyboard focus.
  const card=cards.get(id);
  if(origin==='marker' && card){
   const box=card.getBoundingClientRect(), rail=list.getBoundingClientRect();
   if(box.left<rail.left || box.right>rail.right) list.scrollTo({left:list.scrollLeft+box.left-rail.left-(rail.width-box.width)/2,behavior:reduced?'instant':'smooth'});
  }
 }
 function render() {
  list.replaceChildren();cards.clear();
  results.setAttribute('aria-busy',String(!loaded && !error));
  count.textContent=error?'OPPORTUNITIES UNAVAILABLE':!loaded?'Loading opportunities…':`${visible.length} ${visible.length===1?'OPPORTUNITY':'OPPORTUNITIES'}`;
  empty.hidden=!loaded || visible.length>0;
  find.querySelector('.atlas-results-error').hidden=!error;
  for(const f of visible){
   const p=f.properties, item=document.createElement('li'),card=document.createElement('button');
   card.type='button';card.className='atlas-property';card.dataset.propertyId=String(f.id);
   card.setAttribute('aria-pressed',String(f.id===state.selectedPropertyId));
   // DOM text APIs keep imported property content out of HTML parsing.
   if(p.image?.url){const image=document.createElement('img');image.src=p.image.url;image.alt=p.image.alt??'';image.loading='lazy';image.width=p.image.width;image.height=p.image.height;card.append(image);}
   else {const placeholder=document.createElement('span');placeholder.className='atlas-image-placeholder';placeholder.textContent='ASPIRE';card.append(placeholder);}
   const body=document.createElement('span');body.className='atlas-property-body';
   for(const [className,value] of [
    ['atlas-property-kind',[p.propertyType?.label,p.transactionType?.label].filter(Boolean).join(' · ')],
    ['atlas-property-title',p.title],
    ['atlas-property-metric',propertyMetric(p)],
    ['atlas-property-location',[p.location?.city,p.location?.state].filter(Boolean).join(', ')],
   ]){const line=document.createElement('span');line.className=className;line.textContent=value;body.append(line);}
   const arrow=document.createElement('span');arrow.className='atlas-property-arrow';arrow.textContent='↗';arrow.setAttribute('aria-hidden','true');
   card.append(body,arrow);item.append(card);list.append(item);cards.set(f.id,card);
   card.addEventListener('pointerenter',()=>hover(f.id));
   card.addEventListener('pointerleave',()=>hover(document.activeElement===card?f.id:null));
   card.addEventListener('focus',()=>hover(f.id));
   card.addEventListener('blur',()=>hover(null));
   card.addEventListener('click',()=>select(f.id,'card'));
  }
 }
 function update(reason) {
  visible=state.mode==='find-space'?filterProperties(features,state.filters):features;
  state.hoveredPropertyId=null;
  if(!visible.some(f=>f.id===state.selectedPropertyId)){state.selectedPropertyId=null;selectedLabel.textContent='';}
  render();emit(reason);
 }
 function mode(next) {
  state.mode=next;state.selectedPropertyId=null;state.hoveredPropertyId=null;
  opening.hidden=next==='find-space';find.hidden=next!=='find-space';
  root.querySelector('.atlas-fallback-properties').hidden=true;
  root.querySelector('.atlas-fallback-link').setAttribute('aria-expanded','false');
  root.setAttribute('aria-labelledby',next==='find-space'?find.querySelector('h2').id:opening.querySelector('h1').id);
  // Clear selection on return, but retain filters for the next visit during this page session.
  root.querySelectorAll('.atlas-intent').forEach(b=>b.setAttribute('aria-pressed',String(next==='find-space' && b.hasAttribute('data-find-space'))));
  update(next==='find-space'?'enter':'explore');
  if(root.getBoundingClientRect().top<0)root.scrollIntoView({block:'start',behavior:reduced?'instant':'smooth'});
  (next==='find-space'?find.querySelector('h2'):root.querySelector('[data-find-space]')).focus({preventScroll:true});
 }
 controls.forEach(control=>control.addEventListener('change',()=>{state.filters[control.dataset.filter]=control.value;update(control.dataset.filter==='area'?'area':'filter');}));
 find.querySelectorAll('.atlas-reset').forEach(button=>button.addEventListener('click',()=>{
  state.filters=defaultFilters();controls.forEach(c=>c.value=state.filters[c.dataset.filter]);
  // Empty-state reset disappears after reset, so restore a useful focus destination first.
  if(empty.contains(button))controls[0].focus({preventScroll:true});
  update('reset');
 }));
 root.querySelector('[data-find-space]').addEventListener('click',()=>mode('find-space'));
 find.querySelector('.atlas-explore').addEventListener('click',()=>mode('explore'));
 root.dataset.mode='explore';
 return {
  state,hover,select,
  setData(data){loaded=!!data;error=!data;features=data?.features??[];update('data');},
  connect(fn){adapter=fn;emit('ready');},
 };
}
