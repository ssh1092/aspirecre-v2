/* Progressive presentation only. All service, property and people content comes from WordPress. */
(() => {
 'use strict';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 function enhanceJourney(){
 const journey=document.querySelector('.hp-journey');
 if(!journey)return;
 const scene=journey.querySelector('.hp-journey-scene');
 if(!scene)return;
 const keys=['tenant','owner','investor','management'].filter(key=>journey.querySelector(`#journey-${key} .hp-stage`));
 const tracks=keys.map(key=>journey.querySelector(`#journey-${key}`));
 if(!keys.length)return;
 const stages=Object.fromEntries(keys.map((key,i)=>[key,[...tracks[i].querySelectorAll('.hp-stage')]]));
 const progress={tenant:0,owner:0,investor:0,management:0};
 let active=keys[0],chosen=false,reading=false,lastIllustrated=null,lastOrdered=undefined;
 const controls=new Map();
 const mobile=matchMedia('(max-width: 767px)');
 const experience=journey.querySelector('.hp-journey-experience');
 const mobileNavigation=document.createElement('div');mobileNavigation.className='hp-mobile-stage-navigation';experience.prepend(mobileNavigation);
 function placeNavigation(){
  controls.forEach((control,key)=>{
   const track=tracks[keys.indexOf(key)];
   if(mobile.matches&&key===active){mobileNavigation.append(control.service,control.navigation);}
   else {track.prepend(control.service,control.navigation);}
  });
 }
 mobile.addEventListener('change',placeNavigation);
 const finalAction=document.querySelector('.hp-final-action a');
 const defaultCTA=finalAction?.textContent;
  const sceneMap=scene.querySelector('.hp-scene-map');
 if(!sceneMap||!scene.querySelector('.hp-brief-object'))return;
 const mapProperty=document.createElement('article');mapProperty.className='hp-map-property';sceneMap.append(mapProperty);
 const originalPhoto=scene.querySelector('.hp-scene-photo img');
 const termLinks=[...scene.querySelectorAll('.hp-term-link a')];
 const termCopy=[...scene.querySelectorAll('.hp-term-explanation')];
 const portfolio=document.querySelector('[data-hp-rail="portfolio"] [data-hp-rail-track]');
 const originalProperties=portfolio?[...portfolio.children]:[];
 const compatible=(item,key)=>{const types=item.dataset.transactions?.split(' ')??[];return types.includes(key==='tenant'?'for-lease':'for-sale')||types.includes('for-sale-or-lease');};
 function orderProperties(key){
  const ordered=[...originalProperties].sort((a,b)=>{
   const relevant=['tenant','investor'].includes(key)?Number(compatible(b,key))-Number(compatible(a,key)):0;
   return relevant||Number(a.dataset.editorialOrder)-Number(b.dataset.editorialOrder);
  });
  if(lastOrdered!==key){ordered.forEach(item=>portfolio.append(item));portfolio?.scrollTo({left:0,behavior:'instant'});portfolio?.dispatchEvent(new Event('scroll'));lastOrdered=key;}
  if(lastIllustrated!==active){const illustrated=[...ordered].sort((a,b)=>['tenant','investor'].includes(active)?Number(compatible(b,active))-Number(compatible(a,active)):active==='management'?Number(b.querySelector('.hp-property-tags span')?.textContent==='Office')-Number(a.querySelector('.hp-property-tags span')?.textContent==='Office'):0);comparison(illustrated,active);lastIllustrated=active;}
 }
 // These are existing published facts, not a generated match or underwriting result.
 const compare=document.createElement('div');compare.className='hp-compare-object';compare.setAttribute('aria-label','Current properties to discuss with Aspire');scene.append(compare);
 function comparison(properties,key){
  compare.replaceChildren();
  const selected=properties.slice(0,2);
  for(const item of selected){
   const figure=item.querySelector('img'),title=item.querySelector('h3'),metric=item.querySelector('dd'),type=item.querySelector('.hp-property-tags span');
   const card=document.createElement('article');card.className='hp-compare-property';
   if(figure){const photo=figure.cloneNode(true);photo.removeAttribute('id');photo.setAttribute('sizes','(max-width: 767px) 50vw, 32vw');card.append(photo);}
   const body=document.createElement('div'),name=document.createElement('h4');name.textContent=title?.textContent??'';body.append(name);
   if(metric){const fact=document.createElement('p');fact.className='hp-compare-fact';fact.textContent=metric.textContent;body.append(fact);}
   const use=document.createElement('p');use.textContent=type?.textContent??'';body.append(use);
   const unknown=document.createElement('p');unknown.textContent='Confirm: use, availability and the full commercial terms.';body.append(unknown);card.append(body);compare.append(card);
  }
  const note=document.createElement('p');note.className='hp-compare-disclaimer';note.textContent='Current opportunities for discussion. Suitability has not been determined.';compare.append(note);
  // The same real photo is carried through Define, Validate and Execute.
  const image=selected[0]?.querySelector('img');
  if(image&&originalPhoto){for(const attr of ['src','srcset','alt']){const value=image.getAttribute(attr);if(value)originalPhoto.setAttribute(attr,value);else originalPhoto.removeAttribute(attr);}originalPhoto.setAttribute('sizes','(max-width: 767px) 100vw, 65vw');}
  mapProperty.replaceChildren();
  if(selected[0]){
   if(image){const photo=image.cloneNode(true);photo.setAttribute('sizes','(max-width: 767px) 45vw, 20vw');mapProperty.append(photo);}
   const body=document.createElement('div'),title=document.createElement('h4'),facts=document.createElement('p'),link=selected[0].querySelector('.hp-property-link')?.cloneNode(true);
   title.textContent=selected[0].querySelector('h3')?.textContent;facts.textContent=[selected[0].querySelector('.hp-property-tags span')?.textContent,selected[0].querySelector('dd')?.textContent].filter(Boolean).join(' · ');body.append(title,facts);if(link){link.removeAttribute('class');body.append(link);}mapProperty.append(body);
  }
 }
 function selectTerm(index){termLinks.forEach((link,i)=>link.setAttribute('aria-current',String(i===index)));termCopy.forEach((copy,i)=>copy.hidden=i!==index);}
 termLinks.forEach((link,i)=>{link.setAttribute('role','button');link.addEventListener('click',event=>{event.preventDefault();selectTerm(i);});link.addEventListener('keydown',event=>{if(event.key===' '){event.preventDefault();link.click();}});});selectTerm(0);
 scene.querySelectorAll('.hp-property-question').forEach((question,i)=>{const title=question.querySelector('.hp-question-title'),detail=question.querySelector('.hp-question-detail');if(!title||!detail)return;const button=document.createElement('button');button.type='button';button.textContent=title.textContent;button.setAttribute('aria-expanded','false');detail.id=`hp-diligence-question-${i}`;button.setAttribute('aria-controls',detail.id);detail.hidden=true;title.replaceWith(button);button.addEventListener('click',()=>{const opened=button.getAttribute('aria-expanded')==='true';button.setAttribute('aria-expanded',String(!opened));detail.hidden=opened;question.classList.toggle('is-open',!opened);});});
 const sceneObjects=[sceneMap,scene.querySelector('.hp-brief-object'),scene.querySelector('.hp-diligence-notes'),scene.querySelector('.hp-terms-object'),scene.querySelector('.hp-execute-object'),compare];
 function stage(index,focus=false){
  const current=stages[active];index=Math.max(0,Math.min(current.length-1,index));progress[active]=index;journey.dataset.stage=String(index);
  current.forEach((node,i)=>{node.hidden=!reading&&i!==index;});
  const track=tracks[keys.indexOf(active)];
  controls.get(active).navigation.querySelectorAll('.hp-stage-link a').forEach((link,i)=>{if(i===index)link.setAttribute('aria-current','step');else link.removeAttribute('aria-current');});
  const c=controls.get(active);c.prev.disabled=index===0;c.next.disabled=index===current.length-1;c.count.textContent=`${String(index+1).padStart(2,'0')} / 06`;
  // Off-scene visual objects do not leave invisible links in the keyboard order.
  const visibleObjects=index===0?[1]:index===1?[0,1]:index===2?[5]:index===3?[2]:index===4?[3]:[4];
  sceneObjects.forEach((object,i)=>{if(!object)return;const visible=visibleObjects.includes(i);object.inert=!visible;object.setAttribute('aria-hidden',String(!visible));});
  if(focus){
   if(mobile.matches){c.navigation.querySelectorAll('.hp-stage-link a')[index]?.focus({preventScroll:true});experience.scrollIntoView({behavior:reduced.matches?'instant':'smooth',block:'start'});}
   else {const heading=current[index].querySelector('h3');heading.setAttribute('tabindex','-1');heading.focus({preventScroll:true});}
  }
 }
 function selectJourney(key,explicit=true){
  if(!keys.includes(key))return;
  active=key;chosen=chosen||explicit;journey.dataset.intent=key;
  tracks.forEach((track,i)=>track.hidden=keys[i]!==key);
  const selectedTrack=tracks[keys.indexOf(key)],artifact=selectedTrack.querySelector('.hp-artifact-document');
  if(artifact){const documentObject=scene.querySelector('.hp-brief-object');documentObject.replaceChildren(...[...artifact.children].map(child=>child.cloneNode(true)));const milestones=selectedTrack.querySelector('.hp-artifact-milestones');const destination=scene.querySelector('.hp-milestones');if(milestones&&destination){destination.replaceChildren(...milestones.textContent.split('→').map((label,i)=>{const step=document.createElement('span');step.textContent=label.trim();step.dataset.number=String(i+1).padStart(2,'0');return step;}));}}
  selectedTrack.querySelectorAll('.hp-artifact-topic').forEach((topic,i)=>{if(!termLinks[i]||!termCopy[i])return;termLinks[i].textContent=topic.querySelector('.hp-artifact-topic-label')?.textContent;termCopy[i].querySelector('p').textContent=topic.querySelector('.hp-artifact-topic-explanation')?.textContent;});
  journey.querySelectorAll('.hp-objective-link a').forEach(link=>link.setAttribute('aria-current',String(link.hash===`#journey-${key}`)));
  placeNavigation();stage(progress[key]);orderProperties(chosen?key:null);
  if(finalAction){finalAction.textContent=chosen?tracks[keys.indexOf(key)].querySelector('.hp-journey-advisor a').textContent:defaultCTA;}
 }
 tracks.forEach((track,ti)=>{
  const key=keys[ti];
  track.querySelectorAll('.hp-journey-artifact,.hp-artifact-topics').forEach(source=>source.hidden=true);
  const bar=document.createElement('div');bar.className='hp-step-controls';
  const count=document.createElement('span');count.setAttribute('aria-live','polite');count.setAttribute('aria-atomic','true');
  const prev=document.createElement('button'),next=document.createElement('button');
  prev.type=next.type='button';prev.textContent='←';next.textContent='→';prev.setAttribute('aria-label','Previous journey stage');next.setAttribute('aria-label','Next journey stage');
  prev.addEventListener('click',()=>stage(progress[key]-1,true));next.addEventListener('click',()=>stage(progress[key]+1,true));bar.append(count,prev,next);track.append(bar);controls.set(key,{prev,next,count,navigation:track.querySelector('.hp-stage-navigation'),service:track.querySelector('.hp-journey-service')});
  const read=document.createElement('button');read.type='button';read.className='hp-read-journey';read.textContent='Read the complete journey';read.setAttribute('aria-expanded','false');read.addEventListener('click',()=>{reading=!reading;journey.classList.toggle('is-reading',reading);tracks.forEach(t=>{const b=t.querySelector('.hp-read-journey');b.setAttribute('aria-expanded',String(reading));b.textContent=reading?'Return to the visual journey':'Read the complete journey';});stage(progress[active]);});track.append(read);
  track.querySelectorAll('.hp-stage-link a').forEach((link,index)=>{
   const text=link.textContent;link.dataset.number=text.slice(0,2);link.dataset.label=text.slice(3);link.setAttribute('aria-label',text);link.setAttribute('aria-controls',stages[key][index].id);
   link.addEventListener('click',e=>{e.preventDefault();selectJourney(key);stage(index,true);history.replaceState(null,'',`#${stages[key][index].id}`);});
   link.addEventListener('keydown',e=>{let i=index;if(e.key==='ArrowRight')i++;else if(e.key==='ArrowLeft')i--;else if(e.key==='Home')i=0;else if(e.key==='End')i=5;else return;e.preventDefault();i=Math.max(0,Math.min(5,i));stage(i);controls.get(key).navigation.querySelectorAll('.hp-stage-link a')[i].focus();});
  });
 });
 document.addEventListener('click',event=>{
  const link=event.target.closest('a');if(!link)return;
  if(link.origin!==location.origin||link.pathname!==location.pathname)return;
  const key=keys.find(k=>link.hash===`#journey-${k}`);if(!key)return;
  event.preventDefault();selectJourney(key);
  history.pushState(null,'',link.hash);
  journey.querySelector('.hp-journey-experience').scrollIntoView({behavior:reduced.matches?'instant':'smooth',block:'start'});
 });
 document.addEventListener('aspire:intent',event=>selectJourney(event.detail?.intent));
 document.addEventListener('aspire:map-preview',event=>{
  const data=event.detail;if(!data?.src?.startsWith('data:image/'))return;
  const image=document.createElement('img');image.className='hp-map-preview';image.src=data.src;image.width=data.width;image.height=data.height;image.alt='Houston reference view from the interactive Aspire Atlas map';image.decoding='async';
  image.addEventListener('load',()=>sceneMap.classList.add('has-preview'),{once:true});sceneMap.prepend(image);
  const pins=(data.points||[]).filter(point=>Number.isFinite(point.x)&&Number.isFinite(point.y)).map(point=>{const pin=document.createElement('span');pin.className='hp-preview-pin';pin.setAttribute('aria-hidden','true');sceneMap.append(pin);return {point,pin};});
  const position=()=>{const w=sceneMap.clientWidth,h=sceneMap.clientHeight,scale=Math.max(w/data.width,h/data.height),x=(w-data.width*scale)/2,y=(h-data.height*scale)/2;pins.forEach(({point,pin})=>{pin.style.left=`${x+point.x*scale}px`;pin.style.top=`${y+point.y*scale}px`;});};new ResizeObserver(position).observe(sceneMap);position();
  const attribution=document.createElement('p');attribution.className='hp-preview-attribution';
  const osm=document.createElement('a');osm.href='https://www.openstreetmap.org/copyright';osm.textContent='© OpenStreetMap contributors';
  const proto=document.createElement('a');proto.href='https://protomaps.com/';proto.textContent='Protomaps';attribution.append(osm,' · ',proto);sceneMap.append(attribution);
 });
 // Swiping the scene changes stages; vertical gestures always remain native page scrolling.
 let startTouch=null;
 scene.addEventListener('touchstart',event=>{const t=event.touches[0];startTouch={x:t.clientX,y:t.clientY};},{passive:true});
 scene.addEventListener('touchend',event=>{if(!startTouch)return;const t=event.changedTouches[0],dx=t.clientX-startTouch.x,dy=t.clientY-startTouch.y;if(Math.abs(dx)>60&&Math.abs(dx)>Math.abs(dy)*1.5)stage(progress[active]+(dx<0?1:-1));startTouch=null;},{passive:true});
 const initial=document.querySelector('[data-atlas]')?.dataset.journeyIntent;
 journey.classList.add('is-enhanced');selectJourney(keys.includes(initial)?initial:keys[0],Boolean(initial));
 const followHash=()=>{let hash;try{hash=decodeURIComponent(location.hash.slice(1));}catch{return false;}for(const key of keys){const index=stages[key].findIndex(item=>item.id===hash);if(hash===`journey-${key}`||index>=0){selectJourney(key);if(index>=0)stage(index);return true;}}return false;};
 if(followHash())requestAnimationFrame(()=>journey.querySelector('.hp-journey-experience').scrollIntoView({block:'start'}));
 addEventListener('hashchange',followHash);addEventListener('popstate',followHash);
 }
 enhanceJourney();
 // Native overflow remains the fallback. Buttons, arrow keys and mouse dragging add precision.
 document.querySelectorAll('[data-hp-rail]').forEach(rail=>{
  const track=rail.querySelector('[data-hp-rail-track]'),bar=rail.querySelector('[data-hp-rail-controls]');if(!track||!bar)return;
  const prev=bar.querySelector('[data-hp-rail-prev]'),next=bar.querySelector('[data-hp-rail-next]'),count=bar.querySelector('[data-hp-rail-count]');
  track.tabIndex=0;track.setAttribute('aria-label',rail.getAttribute('aria-label')||'Browse items');bar.hidden=false;
  const items=()=>[...track.querySelectorAll(':scope > [data-hp-rail-item]')];
  const current=()=>{const bounds=track.getBoundingClientRect(),padding=parseFloat(getComputedStyle(track).scrollPaddingLeft)||0;let best=0,distance=Infinity;items().forEach((item,i)=>{const d=Math.abs(item.getBoundingClientRect().left-bounds.left-padding);if(d<distance){best=i;distance=d;}});return best;};
  const move=direction=>{const all=items(),i=Math.max(0,Math.min(all.length-1,current()+direction));const item=all[i];if(!item)return;const padding=parseFloat(getComputedStyle(track).scrollPaddingLeft)||0;const delta=item.getBoundingClientRect().left-track.getBoundingClientRect().left-padding;track.scrollBy({left:delta,behavior:reduced.matches?'instant':'smooth'});};
  const update=()=>{const all=items(),i=current();bar.hidden=track.scrollWidth<=track.clientWidth+2;prev.disabled=track.scrollLeft<=2;next.disabled=track.scrollLeft>=track.scrollWidth-track.clientWidth-2;count.textContent=`${i+1} / ${all.length}`;};
  prev.addEventListener('click',()=>move(-1));next.addEventListener('click',()=>move(1));
  track.addEventListener('keydown',event=>{if(event.target!==track)return;if(event.key==='ArrowRight'||event.key==='ArrowLeft'){event.preventDefault();move(event.key==='ArrowRight'?1:-1);}});
  let frame=0;track.addEventListener('scroll',()=>{if(!frame)frame=requestAnimationFrame(()=>{update();frame=0;});},{passive:true});new ResizeObserver(update).observe(track);update();
  let drag=null,dragged=false;
  track.addEventListener('pointerdown',event=>{if(event.pointerType!=='mouse'||event.button!==0||event.target.closest('button,video'))return;drag={x:event.clientX,left:track.scrollLeft,id:event.pointerId};dragged=false;});
  track.addEventListener('pointermove',event=>{if(!drag)return;if(event.buttons===0){end();return;}const dx=event.clientX-drag.x;if(Math.abs(dx)>7){dragged=true;track.setPointerCapture(event.pointerId);track.style.scrollSnapType='none';track.classList.add('is-dragging');track.scrollLeft=drag.left-dx;}});
  const end=()=>{if(!drag)return;drag=null;track.style.scrollSnapType='';track.classList.remove('is-dragging');};track.addEventListener('pointerup',end);track.addEventListener('pointercancel',end);window.addEventListener('pointerup',end);window.addEventListener('blur',end);
  track.addEventListener('click',event=>{if(dragged){event.preventDefault();event.stopPropagation();dragged=false;}},true);
  track.querySelectorAll('img').forEach(img=>img.draggable=false);
 });
 // Expired social CDN images retain their genuine caption/link; no misleading replacement.
 document.querySelectorAll('.hp-field-image img').forEach(image=>image.addEventListener('error',()=>{image.hidden=true;image.closest('figure')?.classList.add('is-unavailable');}));
})();
