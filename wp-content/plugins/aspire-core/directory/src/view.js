import {loadProperties} from '../../atlas/src/properties.js';
import {areas,SF,ACRES,defaults,fromURL,toURL,filter,sort,cardData} from './data.js';
const node=(tag,text,cls)=>{const el=document.createElement(tag);if(text)el.textContent=text;if(cls)el.className=cls;return el;};
async function start(root){
 const controls=[...root.querySelectorAll('.directory-filters select')],list=root.querySelector('.directory-cards'),count=root.querySelector('.directory-count'),more=root.querySelector('.directory-more'),mapPanel=root.querySelector('.directory-map-panel'),mapSelect=root.querySelector('.directory-map-select'),status=root.querySelector('.directory-selection');
 const type=controls.find(c=>c.name==='type'),transaction=controls.find(c=>c.name==='transaction'),size=controls.find(c=>c.name==='size'),area=controls.find(c=>c.name==='area');
 const types=[...type.options].map(o=>o.value),transactions=[...transaction.options].map(o=>o.value),reduced=matchMedia('(prefers-reduced-motion: reduce)');
 areas.forEach(a=>area.add(new Option(a.label,a.value)));
 let filters=fromURL(location.search,types,transactions),features=[],visible=[],selected=null,hovered=null,order='featured',map=null,mapPromise=null,loaded=false;
 const perLoad=Number(root.dataset.perLoad),cards=new Map();let limit=perLoad||Infinity;
 function configure(){size.replaceChildren(...(filters.type==='land'?ACRES:SF).map(([value,label])=>new Option(label,value)));root.querySelector('.directory-size-label').textContent=filters.type==='land'?'SIZE · ACRES':'SIZE';controls.forEach(c=>c.value=filters[c.name]);root.querySelectorAll('[data-quick-type]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.quickType===filters.type)));}
 function visual(reason){root.dataset.selectedProperty=selected??'';root.dataset.hoveredProperty=hovered??'';for(const [id,card] of cards){card.classList.toggle('is-selected',id===selected);card.classList.toggle('is-hovered',id===hovered);card.querySelector('.directory-card-select').setAttribute('aria-pressed',String(id===selected));}if(mapSelect)mapSelect.value=selected===null?'':String(selected);map?.update(visible,selected,hovered,reason);}
 function hover(id){if(hovered===id)return;hovered=id;visual('hover');}
 function select(id,origin='card'){
  if(id!==null&&!visible.some(f=>f.id===id))return;selected=id;hovered=null;
  if(id!==null){const index=visible.findIndex(f=>f.id===id);if(index>=limit){limit=perLoad?Math.ceil((index+1)/perLoad)*perLoad:Infinity;render();}status.textContent=`${visible[index].properties.displayTitle||visible[index].properties.title} selected.`;}
  else status.textContent='Property selection cleared.';
  visual('select');if(origin==='map'&&cards.has(id))cards.get(id).scrollIntoView({block:'center',behavior:reduced.matches?'instant':'smooth'});
 }
 function render(){
  list.replaceChildren();cards.clear();
  for(const feature of visible.slice(0,limit)){
   const data=cardData(feature,location.origin);if(!data)continue;
   const item=node('li'),card=node('article',null,'directory-card'),media=node('div',null,'directory-card-media'),body=node('div',null,'directory-card-body');
   card.dataset.propertyId=String(feature.id);
   if(data.image){const image=node('img');image.src=data.image.url;image.alt=data.image.alt;image.loading='lazy';image.decoding='async';image.width=feature.properties.image.width;image.height=feature.properties.image.height;media.append(image);}else media.append(node('span','ASPIRE COMMERCIAL','directory-image-placeholder'));
   if(data.featured)media.append(node('span','FEATURED','directory-featured'));
   const choose=node('button','⌖','directory-card-select');choose.type='button';choose.setAttribute('aria-label',`Select ${data.title}`);choose.setAttribute('aria-pressed',String(selected===feature.id));choose.title='Select property';choose.addEventListener('click',()=>select(feature.id));media.append(choose);
   body.append(node('p',data.eyebrow,'directory-card-kind'),node('h2',data.title),node('p',[feature.properties.location?.city,feature.properties.location?.state].filter(Boolean).join(', '),'directory-card-location'));
   const metrics=node('dl',null,'directory-card-metrics');data.metrics.forEach(({label,value})=>{const group=node('div');group.append(node('dt',label),node('dd',value));metrics.append(group);});if(data.metrics.length)body.append(metrics);
   if(data.highlights.length){const highlights=node('ul',null,'directory-highlights');data.highlights.forEach(line=>highlights.append(node('li',line)));body.append(highlights);}
   if(data.permalink){const link=node('a','VIEW PROPERTY →','directory-card-link');link.href=data.permalink;link.setAttribute('aria-label',`View property: ${data.title}`);body.append(link);}
   card.append(media,body);item.append(card);list.append(item);cards.set(feature.id,card);
   card.addEventListener('pointerenter',()=>hover(feature.id));card.addEventListener('pointerleave',()=>hover(card.contains(document.activeElement)?feature.id:null));card.addEventListener('focusin',()=>hover(feature.id));card.addEventListener('focusout',e=>{if(!card.contains(e.relatedTarget))hover(null);});card.addEventListener('click',e=>{if(!e.target.closest('a,button'))select(feature.id);});
  }
  more.hidden=visible.length<=limit;
  count.textContent=`${visible.length} ${visible.length===1?'OPPORTUNITY':'OPPORTUNITIES'}`;
  root.querySelector('.directory-empty').hidden=visible.length>0;root.dataset.resultCount=String(visible.length);
  if(mapSelect)mapSelect.replaceChildren(new Option('Choose a property',''),...visible.map(f=>new Option(f.properties.displayTitle||f.properties.title,String(f.id))));
 }
 function update(reason,write=false){configure();visible=sort(filter(features,filters),order);if(!visible.some(f=>f.id===selected))selected=null;hovered=null;limit=perLoad||Infinity;if(loaded)render();if(write){const url=toURL(root.dataset.base,filters);if(url!==location.pathname+location.search)history.pushState(null,'',url);}visual(reason);}
 async function ensureMap(){if(!root.dataset.mapModule||root.dataset.view!=='split'||!loaded)return;if(map){map.resize();return;}if(!mapPromise){mapPromise=import(root.dataset.mapModule).then(module=>module.createDirectoryMap(root,features,{hover,select})).then(value=>{map=value;map.update(visible,selected,hovered,'ready');}).catch(()=>{root.querySelector('.directory-map-status').textContent='The map is unavailable. All property details remain available in the list.';});}await mapPromise;}
 function view(value){root.dataset.view=value;if(mapPanel)mapPanel.hidden=value==='list';root.querySelectorAll('[data-view-choice]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.viewChoice===value)));ensureMap();}
 controls.forEach(c=>c.addEventListener('change',()=>{if(c.name==='type'&&(filters.type==='land')!==(c.value==='land'))filters.size='';filters[c.name]=c.value;update('filter',true);}));
 root.querySelector('.directory-filters').addEventListener('submit',e=>e.preventDefault());
 root.querySelectorAll('[data-quick-type]').forEach(b=>b.addEventListener('click',()=>{if((filters.type==='land')!==(b.dataset.quickType==='land'))filters.size='';filters.type=b.dataset.quickType;update('filter',true);}));
 root.querySelectorAll('.directory-reset').forEach(b=>b.addEventListener('click',()=>{filters=defaults();update('filter',true);if(!b.isConnected||b.closest('.directory-empty'))type.focus();}));
 root.querySelector('[name=sort]').addEventListener('change',e=>{order=e.target.value;root.querySelector('.directory-sort-note').hidden=order!=='largest';update('sort');});
 root.querySelectorAll('[data-view-choice]').forEach(b=>b.addEventListener('click',()=>view(b.dataset.viewChoice)));
 more.addEventListener('click',()=>{const firstNew=limit;limit+=perLoad;render();visual('more');cards.get(visible[firstNew]?.id)?.querySelector('button').focus({preventScroll:true});});
 mapSelect?.addEventListener('change',()=>select(mapSelect.value?Number(mapSelect.value):null,'map'));
 window.addEventListener('popstate',()=>{filters=fromURL(location.search,types,transactions);update('filter');});configure();view(root.dataset.view);
 const abort=new AbortController(),timer=setTimeout(()=>abort.abort(),15000);
 try{const data=await loadProperties(root.dataset.endpoint,abort.signal);features=data.features;loaded=true;update('ready');await ensureMap();}
 catch{count.textContent='OPPORTUNITIES UNAVAILABLE';root.querySelector('.directory-error').hidden=false;if(mapPanel)mapPanel.hidden=true;}
 finally{clearTimeout(timer);}
}
document.querySelectorAll('[data-directory]').forEach(start);
