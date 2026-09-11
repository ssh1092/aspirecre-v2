import {AREAS,defaultFilters,filterProperties,propertyMetric} from './filters.js';
import './find-space.css';
import {isDiscovery,isGuided,defaultInvestFilters,filterInvest,investMetric,SF_OPTIONS,ACRE_OPTIONS} from './invest.js';
import {createGuided} from './guided.js';
import {newOwner,newManagement} from './workflow-data.js';
import {createPropertyFocus} from './property-focus.js';

// One state owner for controls, accessible cards, and the map adapter. No network work here.
export function createFindSpace(root, reduced) {
 const state={mode:'explore',filters:defaultFilters(),selectedPropertyId:null,hoveredPropertyId:null,propertyFocusOpen:false,cameraBeforeFocus:null,railScrollBeforeFocus:0,findSpaceFilters:defaultFilters(),investFilters:defaultInvestFilters(),ownerDisposition:newOwner(),management:newManagement(),briefPreparation:null};
 const opening=root.querySelector('.atlas-opening'), find=root.querySelector('.atlas-find');
 const list=find.querySelector('.atlas-result-list'), results=find.querySelector('.atlas-results');
 const count=find.querySelector('.atlas-result-count'), empty=find.querySelector('.atlas-empty');
 const selectedLabel=find.querySelector('.atlas-result-selection');
 const dossier=createPropertyFocus(root),focusAnnouncement=root.querySelector('.atlas-focus-announcement'),focusError=root.querySelector('.atlas-focus-error');
 let returnFocus=null;
 const controls=[...find.querySelectorAll('[data-filter]')];
 let features=[],visible=[],loaded=false,error=false,adapter=()=>{};
 const cards=new Map();
 const guided=createGuided(root,state,reason=>emit(reason),()=>mode('explore'));
 let lastIntent='find-space';
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
 function hover(id) {if(isGuided(state.mode))return;if(state.hoveredPropertyId===id)return;state.hoveredPropertyId=id;emit('hover');}
 function focusLayout() {
  dossier.panel.hidden=!state.propertyFocusOpen;
  root.setAttribute('aria-labelledby',state.propertyFocusOpen?dossier.panel.querySelector('h2').id:isDiscovery(state.mode)?find.querySelector('h2').id:opening.querySelector('h1').id);
  opening.hidden=state.propertyFocusOpen || isDiscovery(state.mode);
  find.querySelector('.atlas-filter-rail').hidden=state.propertyFocusOpen;
  results.hidden=state.propertyFocusOpen;
  root.dataset.propertyFocus=String(state.propertyFocusOpen);
 }
 function closeFocus(message='') {
  if(!state.propertyFocusOpen){if(message){focusError.hidden=false;focusError.textContent=message;}return;}
  state.propertyFocusOpen=false;state.hoveredPropertyId=null;focusLayout();emit('focus-close');
  list.scrollLeft=state.railScrollBeforeFocus;
  focusAnnouncement.textContent=isDiscovery(state.mode)?'Returned to property results.':'Returned to Explore.';
  if(message){focusError.hidden=false;focusError.textContent=message;}
  const target=returnFocus?.isConnected?returnFocus:find.querySelector('h2');
  target.focus({preventScroll:true});
  // Focusing a card highlights it but does not change the saved camera or rail position.
  list.scrollLeft=state.railScrollBeforeFocus;
 }
 function select(id,origin='card') {
  if(isGuided(state.mode))return;
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
   dossier.back.textContent=isDiscovery(state.mode)?'← Back to results':'← Back to Explore';
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
    ['atlas-property-title',p.displayTitle||p.title],
    ['atlas-property-metric',state.mode==='invest'?investMetric(p):propertyMetric(p)],
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
  visible=state.mode==='invest'?filterInvest(features,state.filters):isDiscovery(state.mode)?filterProperties(features,state.filters):features;
  state.hoveredPropertyId=null;
  if(!visible.some(f=>f.id===state.selectedPropertyId)){state.selectedPropertyId=null;selectedLabel.textContent='';}
  render();emit(reason);
 }
 function configureControls(){
  const investing=state.mode==='invest';
  find.querySelector('h2').textContent=investing?'BUY / INVEST':'FIND SPACE';
  find.querySelector('.atlas-filter-rail').setAttribute('aria-label',investing?'Buy / Invest filters':'Find Space filters');
  find.querySelector('.atlas-results-heading>span').textContent=investing?'EXPLORE OPPORTUNITIES':'HOUSTON & SURROUNDING AREAS';
  const transaction=find.querySelector('[data-filter="transactionType"]');
  transaction.querySelector('option[value="for-sale"]')?.remove();
  if(investing)transaction.add(new Option('For Sale','for-sale'));
  transaction.parentElement.hidden=investing;
  find.querySelector('[data-filter="price"]').parentElement.hidden=!investing;
  const land=investing && state.filters.propertyType==='land',size=find.querySelector('[data-filter="size"]');
  find.querySelector('.atlas-size-label').textContent=investing?(land?'Lot Acres':'Building SF'):'Size';
  size.replaceChildren(...(land?ACRE_OPTIONS:SF_OPTIONS).map(([value,label])=>new Option(label,value)));
  controls.forEach(c=>c.value=state.filters[c.dataset.filter]??'');
 }
 function mode(next) {
  if(state.propertyFocusOpen)closeFocus();
  if(isDiscovery(state.mode))state[state.mode==='invest'?'investFilters':'findSpaceFilters']=state.filters;
  if(isGuided(state.mode)){state[state.mode==='manage-asset'?'management':'ownerDisposition']=state.mode==='manage-asset'?newManagement():newOwner();state.briefPreparation=null;}
  if(next!=='explore')lastIntent=next;
  focusAnnouncement.textContent='';focusError.hidden=true;
  state.mode=next;state.selectedPropertyId=null;state.hoveredPropertyId=null;
  state.filters=next==='invest'?state.investFilters:state.findSpaceFilters;
  opening.hidden=next!=='explore';find.hidden=!isDiscovery(next);
  configureControls();guided.render(false);
  root.querySelector('.atlas-fallback-properties').hidden=true;
  root.querySelector('.atlas-fallback-link').setAttribute('aria-expanded','false');
  root.setAttribute('aria-labelledby',isGuided(next)?guided.heading.id:isDiscovery(next)?find.querySelector('h2').id:opening.querySelector('h1').id);
  root.querySelectorAll('.atlas-intent').forEach(b=>b.setAttribute('aria-pressed',String(next===b.dataset.intent)));
  update(next==='explore'?'explore':'enter');
  if(root.getBoundingClientRect().top<0)root.scrollIntoView({block:'start',behavior:reduced?'instant':'smooth'});
  (isGuided(next)?guided.heading:isDiscovery(next)?find.querySelector('h2'):root.querySelector(`[data-intent="${lastIntent}"]`)).focus({preventScroll:true});
 }
 controls.forEach(control=>control.addEventListener('change',()=>{
  if(state.mode==='invest' && control.dataset.filter==='propertyType' && (state.filters.propertyType==='land')!==(control.value==='land'))state.filters.size='';
  state.filters[control.dataset.filter]=control.value;configureControls();update(control.dataset.filter==='area'?'area':'filter');
 }));
 find.querySelectorAll('.atlas-reset').forEach(button=>button.addEventListener('click',()=>{
  state.filters=state.mode==='invest'?defaultInvestFilters():defaultFilters();configureControls();
  if(empty.contains(button))controls[0].focus({preventScroll:true});
  update('reset');
 }));
 root.querySelectorAll('[data-intent]').forEach(button=>button.addEventListener('click',()=>mode(button.dataset.intent)));
 find.querySelector('.atlas-explore').addEventListener('click',()=>mode('explore'));
 root.dataset.mode='explore';
 return {
  state,hover,select,
  setData(data){loaded=!!data;error=!data;features=data?.features??[];update('data');},
  connect(fn){adapter=fn;emit('ready');},
 };
}
