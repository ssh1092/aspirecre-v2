import {AREAS,defaultFilters,filterProperties,propertyMetric} from './filters.js';
import './find-space.css';
import {createPropertyFocus} from './property-focus.js';

// One state owner for controls, accessible cards, and the map adapter. No network work here.
export function createFindSpace(root, reduced) {
 const state={mode:'explore',filters:defaultFilters(),selectedPropertyId:null,hoveredPropertyId:null,propertyFocusOpen:false,cameraBeforeFocus:null,railScrollBeforeFocus:0};
 const opening=root.querySelector('.atlas-opening'), find=root.querySelector('.atlas-find');
 const list=find.querySelector('.atlas-result-list'), results=find.querySelector('.atlas-results');
 const count=find.querySelector('.atlas-result-count'), empty=find.querySelector('.atlas-empty');
 const selectedLabel=find.querySelector('.atlas-result-selection');
 const dossier=createPropertyFocus(root),focusAnnouncement=root.querySelector('.atlas-focus-announcement'),focusError=root.querySelector('.atlas-focus-error');
 let returnFocus=null;
 const controls=[...find.querySelectorAll('[data-filter]')];
 let features=[],visible=[],loaded=false,error=false,adapter=()=>{};
 const cards=new Map();
 const area=find.querySelector('[data-filter="area"]');
 for(const [value,preset] of Object.entries(AREAS)) area.add(new Option(preset.label,value));
 const emit=reason=>{
  root.dataset.propertyFocus=String(state.propertyFocusOpen);
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
 function focusLayout() {
  dossier.panel.hidden=!state.propertyFocusOpen;
  root.setAttribute('aria-labelledby',state.propertyFocusOpen?dossier.panel.querySelector('h2').id:state.mode==='find-space'?find.querySelector('h2').id:opening.querySelector('h1').id);
  opening.hidden=state.propertyFocusOpen || state.mode==='find-space';
  find.querySelector('.atlas-filter-rail').hidden=state.propertyFocusOpen;
  results.hidden=state.propertyFocusOpen;
  root.dataset.propertyFocus=String(state.propertyFocusOpen);
 }
 function closeFocus(message='') {
  if(!state.propertyFocusOpen){if(message){focusError.hidden=false;focusError.textContent=message;}return;}
  state.propertyFocusOpen=false;state.hoveredPropertyId=null;focusLayout();emit('focus-close');
  list.scrollLeft=state.railScrollBeforeFocus;
  focusAnnouncement.textContent=state.mode==='find-space'?'Returned to property results.':'Returned to Explore.';
  if(message){focusError.hidden=false;focusError.textContent=message;}
  const target=returnFocus?.isConnected?returnFocus:find.querySelector('h2');
  target.focus({preventScroll:true});
  // Focusing a card highlights it but does not change the saved camera or rail position.
  list.scrollLeft=state.railScrollBeforeFocus;
 }
 function select(id,origin='card') {
  const f=visible.find(f=>f.id===id);
  if(id!==null && !f){closeFocus('This property is unavailable. Please choose another opportunity.');return;}
  if(!f){state.selectedPropertyId=null;emit(origin);return;}
  const opens=['card','marker','keyboard'].includes(origin);
  if(opens){
   try{if(!dossier.render(f))throw Error('Missing property');}
   catch{closeFocus('This property could not be opened. Please choose another opportunity.');return;}
   if(!state.propertyFocusOpen){
    state.railScrollBeforeFocus=list.scrollLeft;
    returnFocus=origin==='card'?cards.get(id):origin==='keyboard'?root.querySelector('.atlas-selection select'):root.querySelector('.maplibregl-canvas');
   }
   state.selectedPropertyId=id;state.hoveredPropertyId=null;state.propertyFocusOpen=true;
   focusError.hidden=true;
   dossier.back.textContent=state.mode==='find-space'?'← Back to results':'← Back to Explore';
   focusLayout();emit('focus-open');
   focusAnnouncement.textContent=`Property Focus opened: ${f.properties.title}.`;
   if(root.clientWidth>700 && root.getBoundingClientRect().top<0)root.scrollIntoView({block:'start',behavior:reduced?'instant':'smooth'});
   dossier.back.focus({preventScroll:true});
   const box=dossier.back.getBoundingClientRect();
   if(box.top<0 || box.bottom>innerHeight)dossier.back.scrollIntoView({block:'nearest',behavior:reduced?'instant':'smooth'});
  }
 }
 dossier.back.addEventListener('click',()=>closeFocus());
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
  if(state.propertyFocusOpen)closeFocus();
  visible=state.mode==='find-space'?filterProperties(features,state.filters):features;
  state.hoveredPropertyId=null;
  if(!visible.some(f=>f.id===state.selectedPropertyId)){state.selectedPropertyId=null;selectedLabel.textContent='';}
  render();emit(reason);
 }
 function mode(next) {
  if(state.propertyFocusOpen)closeFocus();
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
