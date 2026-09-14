import {prioritizeProperties,placePropertyCards} from './hero-data.js';
import {propertyMetric} from './filters.js';
import {localUrl} from './focus-data.js';
const node=(tag,cls,text)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(text)el.textContent=text;return el;};
export function createMapCards(root,map,features,discovery){
 const layer=node('div','atlas-map-cards');layer.setAttribute('role','group');layer.setAttribute('aria-label','Aspire property previews');
 const lines=document.createElementNS('http://www.w3.org/2000/svg','svg');lines.classList.add('atlas-card-connectors');lines.setAttribute('aria-hidden','true');layer.append(lines);
 const entries=new Map(),controls=node('div','atlas-preview-controls'),previous=node('button','','←'),next=node('button','','→'),count=node('span');
 previous.type=next.type='button';previous.setAttribute('aria-label','Previous map property');next.setAttribute('aria-label','Next map property');count.setAttribute('role','status');controls.append(previous,count,next);layer.append(controls);root.append(layer);
 // The existing map remains the sole geographic surface and source of coordinates.
 const mapElement=root.querySelector('.atlas-map');for(const el of root.querySelectorAll('.atlas-map-meta,.atlas-attribution'))mapElement.append(el);
 const stableLayoutOrder=[...features],expandedHeights=new Map();
 let state=discovery.state,frame=0,ordered=[],effective=null;
 const mobile=()=>matchMedia('(max-width:767px)').matches;
 function choose(id){discovery.select(id,'preview');}
 function shift(delta){const i=ordered.findIndex(f=>f.id===effective);choose(ordered[(i+delta+ordered.length)%ordered.length]?.id);}
 previous.onclick=()=>shift(-1);next.onclick=()=>shift(1);
 let swipe=null,suppressClickUntil=0;
 layer.addEventListener('pointerdown',e=>{if(mobile()&&!e.target.closest('a,.atlas-preview-controls'))swipe={x:e.clientX,y:e.clientY};});
 // Photos are inside selection buttons, so also accept a touch gesture on them.
 layer.addEventListener('touchstart',e=>{if(!e.target.closest('a'))swipe={x:e.touches[0].clientX,y:e.touches[0].clientY};},{passive:true});
 const finish=(x,y)=>{if(swipe){const dx=x-swipe.x,dy=y-swipe.y;if(Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy)*1.4){suppressClickUntil=Date.now()+350;shift(dx<0?1:-1);}swipe=null;}};
 layer.addEventListener('touchend',e=>finish(e.changedTouches[0].clientX,e.changedTouches[0].clientY),{passive:true});
 layer.addEventListener('pointerup',e=>finish(e.clientX,e.clientY));
 layer.addEventListener('pointercancel',()=>{swipe=null;});layer.addEventListener('touchcancel',()=>{swipe=null;},{passive:true});
 layer.addEventListener('click',e=>{if(Date.now()<suppressClickUntil){e.preventDefault();e.stopPropagation();}},true);
 layer.addEventListener('keydown',e=>{if(['ArrowLeft','ArrowRight'].includes(e.key)&&mobile()){e.preventDefault();shift(e.key==='ArrowRight'?1:-1);}});
 for(const f of features){
  const p=f.properties,article=node('article','atlas-map-preview'),button=node('button','atlas-preview-select'),body=node('span','atlas-preview-copy'),image=node('img'),detail=node('div','atlas-preview-detail');
  article.dataset.propertyId=String(f.id);button.type='button';button.setAttribute('aria-label',`Select ${p.displayTitle||p.title}`);button.setAttribute('aria-pressed','false');
  const preview=p.image?.preview,url=localUrl(preview?.url,location.origin);image.alt=p.image?.alt||p.displayTitle||p.title;image.width=preview?.width||300;image.height=preview?.height||200;image.decoding='async';
  if(url)button.append(image);
  body.append(node('span','atlas-preview-kind',[p.propertyType?.label,p.transactionType?.label].filter(Boolean).join(' · ')),node('span','atlas-preview-title',p.displayTitle||p.title),node('span','atlas-preview-metric',propertyMetric(p)));button.append(body);
  detail.append(node('p','atlas-preview-address',[p.location?.address,[p.location?.city,p.location?.state,p.location?.postalCode].filter(Boolean).join(' ')].filter(Boolean).join(', ')));
  const permalink=localUrl(p.permalink,location.origin);if(permalink){const link=node('a','atlas-preview-link','View Property →');link.href=permalink;detail.append(link);}
  article.append(button,detail);layer.insertBefore(article,controls);entries.set(f.id,{article,button,image,detail,url,preview});
  button.onclick=()=>choose(f.id);button.addEventListener('pointerenter',()=>discovery.hover(f.id));button.addEventListener('pointerleave',()=>discovery.hover(null));button.addEventListener('focus',()=>discovery.hover(f.id));button.addEventListener('blur',()=>discovery.hover(null));
  image.addEventListener('error',()=>{entries.get(f.id).url=null;image.remove();schedule();},{once:true});
 }
 function measureExpandedHeights(){
  const visibility=layer.style.visibility;layer.style.visibility='hidden';
  for(const [id,e] of entries){const hidden=e.detail.hidden;e.detail.hidden=false;expandedHeights.set(id,Math.max(expandedHeights.get(id)??0,Math.ceil(e.article.getBoundingClientRect().height)));e.detail.hidden=hidden;}
  layer.style.visibility=visibility;
 }
 function load(entry){if(!entry.url||entry.image.getAttribute('src'))return;entry.image.sizes='(max-width:767px) calc(100vw - 36px), 208px';if(entry.preview.srcset)entry.image.srcset=entry.preview.srcset;entry.image.src=entry.url;}
 function render(){
  frame=0;const showing=state.mode==='explore'&&!state.briefOpen&&!state.propertyFocusOpen;layer.hidden=!showing;if(!showing)return;
  ordered=mobile()?prioritizeProperties(features,root.dataset.heroIntent):stableLayoutOrder;effective=state.selectedPropertyId??ordered[0]?.id;
  controls.hidden=!mobile()||features.length<2;count.textContent=`${ordered.findIndex(f=>f.id===effective)+1} / ${features.length}`;
  lines.replaceChildren();
  const transaction=root.dataset.heroIntent==='find-space'?'for-lease':root.dataset.heroIntent==='invest'?'for-sale':null;
  for(const [id,e] of entries){e.article.hidden=false;e.article.classList.toggle('is-selected',id===state.selectedPropertyId);e.article.classList.toggle('is-hovered',id===state.hoveredPropertyId);e.article.classList.toggle('has-matching-intent',!!transaction);e.article.classList.toggle('is-priority',!!transaction&&[transaction,'for-sale-or-lease'].includes(features.find(f=>f.id===id).properties.transactionType?.slug));e.button.setAttribute('aria-pressed',String(id===state.selectedPropertyId));e.detail.hidden=!mobile()&&id!==state.selectedPropertyId;}
  if(mobile()){
   for(const [id,e] of entries){e.article.hidden=id!==effective;if(id===effective)load(e);e.article.style.removeProperty('transform');}
   return;
  }
  const rootBox=root.getBoundingClientRect(),opening=root.querySelector('.atlas-opening').getBoundingClientRect(),nav=root.querySelector('.atlas-nav')?.getBoundingClientRect();
  const bounds={left:opening.right-rootBox.left+24,right:root.clientWidth-22,top:(nav?.bottom??rootBox.top+90)-rootBox.top+45,bottom:root.clientHeight-62};
  // Measure every card fully expanded while the layer cannot paint. Collision
  // geometry then remains independent of intent, selection and emphasis.
  measureExpandedHeights();
  const items=stableLayoutOrder.filter(f=>entries.get(f.id).url).map(f=>({id:f.id,point:map.project(f.geometry.coordinates),height:expandedHeights.get(f.id)}));
  const reserved=[...mapElement.querySelectorAll('.maplibregl-ctrl-group')].map(el=>{const b=el.getBoundingClientRect();return{x:b.left-rootBox.left,y:b.top-rootBox.top,width:b.width,height:b.height};});
  const placements=placePropertyCards(items,bounds,208,reserved);
  for(const [id,e] of entries){const item=placements.find(p=>p.id===id);e.article.hidden=!item?.box;if(!item?.box)continue;
   const b=item.box;e.article.style.transform=`translate(${b.x}px,${b.y}px)`;load(e);
   const line=document.createElementNS(lines.namespaceURI,'line');line.setAttribute('x1',item.point.x);line.setAttribute('y1',item.point.y);line.setAttribute('x2',Math.max(b.x,Math.min(b.x+b.width,item.point.x)));line.setAttribute('y2',Math.max(b.y,Math.min(b.y+b.height,item.point.y)));line.classList.toggle('is-selected',id===state.selectedPropertyId);lines.append(line);
  }
  root.dataset.visibleMapCards=String(placements.filter(p=>p.box).length);
 }
 function schedule(){if(!frame)frame=requestAnimationFrame(render);}
 map.on('move',schedule);map.on('resize',schedule);root.addEventListener('atlas-hero-intent',schedule);
 new ResizeObserver(schedule).observe(root);
 return{sync(nextState){state=nextState;schedule();}};
}
