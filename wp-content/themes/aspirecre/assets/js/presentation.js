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
 const mobileNavigation=document.createElement('div');mobileNavigation.className='hp-mobile-stage-navigation hp-journey-rail';experience.prepend(mobileNavigation);
 function placeNavigation(){
  controls.forEach((control,key)=>{
   const track=tracks[keys.indexOf(key)];
   if(key===active){mobileNavigation.append(control.service,control.navigation);}
   else {track.prepend(control.service,control.navigation);}
  });
 }
 mobile.addEventListener('change',placeNavigation);
 const finalAction=document.querySelector('.hp-final-action a');
 const defaultCTA=finalAction?.textContent;
  const sceneMap=scene.querySelector('.hp-scene-map');
 if(!sceneMap||!scene.querySelector('.hp-brief-object'))return;
 const changeObjective=document.createElement('button');changeObjective.type='button';changeObjective.className='hp-change-objective';changeObjective.textContent='Change objective';changeObjective.setAttribute('aria-expanded','false');journey.querySelector('.hp-journey-intro').append(changeObjective);
 changeObjective.addEventListener('click',()=>{const open=journey.classList.toggle('is-choosing');changeObjective.setAttribute('aria-expanded',String(open));if(open)journey.querySelector('.hp-objective-link a').focus();});
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
 // Shared real-property elements persist from the map to the shortlist and preferred option.
 // Stage changes measure once, set the final layout, then animate transforms (FLIP).
 const compare=document.createElement('div');compare.className='hp-compare-object';compare.setAttribute('aria-label','Current properties to discuss with Aspire');scene.append(compare);
 const compareBar=document.createElement('div');compareBar.className='hp-compare-controls';
 const comparePrev=document.createElement('button'),compareNext=document.createElement('button'),compareCount=document.createElement('output');
 comparePrev.type=compareNext.type='button';comparePrev.textContent='←';compareNext.textContent='→';comparePrev.setAttribute('aria-label','Previous comparison property');compareNext.setAttribute('aria-label','Next comparison property');compareCount.setAttribute('aria-live','polite');compareBar.append(comparePrev,compareCount,compareNext);scene.append(compareBar);
 let compared=0,propertyObjects=[],mapReference=null;
 reduced.addEventListener('change',()=>{if(reduced.matches)propertyObjects.forEach(card=>card.getAnimations().forEach(animation=>animation.cancel()));});
 const mapLines=document.createElementNS('http://www.w3.org/2000/svg','svg');mapLines.classList.add('hp-map-connections');mapLines.setAttribute('aria-hidden','true');scene.append(mapLines);
 function setCompared(index){compared=Math.max(0,Math.min(propertyObjects.length-1,index));layoutProperties(2,true);}
 comparePrev.addEventListener('click',()=>setCompared(compared-1));compareNext.addEventListener('click',()=>setCompared(compared+1));
 const paragraph=(text,className='')=>{const p=document.createElement('p');p.textContent=text;p.className=className;return p;};
 function comparison(properties,key){
  compare.replaceChildren();compared=0;
  propertyObjects=properties.slice(0,3).map((item,i)=>{
   const card=document.createElement('article');card.className='hp-shared-property';card.dataset.propertyId=item.dataset.propertyId;card.dataset.order=String(i);
   const source=item.querySelector('img');if(source){const photo=source.cloneNode(true);photo.removeAttribute('id');photo.setAttribute('sizes','(max-width: 767px) 100vw, 75vw');photo.draggable=false;card.append(photo);}
   const body=document.createElement('div');body.className='hp-shared-property-copy';
   const name=document.createElement('h4');name.textContent=item.querySelector('h3')?.textContent??'';
   body.append(paragraph([...item.querySelectorAll('.hp-property-tags span')].map(part=>part.textContent).join(' · '),'hp-shared-type'),name,paragraph(item.querySelector('.hp-property-locality')?.textContent??'','hp-shared-locality'));
   const metric=item.querySelector('.hp-property-metric');if(metric){const clone=metric.cloneNode(true);clone.className='hp-shared-metric';body.append(clone);}
   const question=paragraph('Confirm the intended use, access and full commercial terms.','hp-shared-question');
   const relevance=paragraph(compatible(item,key)?'The listed transaction fits this search direction. Confirm suitability with Aspire.':'A broader market reference. Confirm the transaction and intended use.','hp-shared-fit');
   body.append(relevance,question);
   const link=item.querySelector('.hp-property-link')?.cloneNode(true);if(link){link.className='hp-shared-link';body.append(link);}
   const closer=document.createElement('button');closer.type='button';closer.className='hp-look-closer';closer.textContent='Look closer ↗';closer.setAttribute('aria-label','Validate '+name.textContent);closer.addEventListener('click',()=>{compared=i;stage(3,true);});body.append(closer);card.append(body);compare.append(card);return card;
  });
  compare.append(paragraph('Current opportunities for discussion. Suitability has not been determined.','hp-compare-disclaimer'));
  layoutProperties(progress[active],false);
 }
 function layoutProperties(index,animate){
  const first=propertyObjects.map(card=>card.getBoundingClientRect());
  propertyObjects.forEach(card=>card.getAnimations().forEach(animation=>animation.cancel()));
  propertyObjects.forEach((card,i)=>{
   let box,visible=true;
   if(index===0){box=[38,0,62,100];visible=i===compared;}
   else if(index===1){box=mobile.matches?[39,43,57,40]:[[51,18,22,40],[22,48,18,33],[77,55,18,33]][i];visible=!mobile.matches||i===compared;}
   else if(index===2){box=mobile.matches?[(i-compared)*100,0,100,91]:[i*33.55,0,32.9,94];visible=!mobile.matches||i===compared;}
   else if(index===3){box=i===compared?[0,0,100,100]:[105+(i-1)*25,0,30,100];visible=i===compared;}
   else if(index===4){box=[70,0,30,100];visible=i===compared;}
   else{box=[84,0,16,100];visible=i===compared;}
   const [x,y,w,h]=box;Object.assign(card.style,{left:x+'%',top:y+'%',width:w+'%',height:h+'%',opacity:visible?'1':'0'});
   const readable=(index===1&&visible)||(index===2&&visible);card.inert=!readable;card.setAttribute('aria-hidden',String(!readable));
  });
  drawMapLinks();
  const last=propertyObjects.map(card=>card.getBoundingClientRect());
  if(animate&&!reduced.matches){propertyObjects.forEach((card,i)=>{const a=first[i],b=last[i];if(!a.width||!b.width)return;card.animate([{transform:`translate(${a.x-b.x}px,${a.y-b.y}px) scale(${a.width/b.width},${a.height/b.height})`},{transform:'none'}],{duration:850,easing:'cubic-bezier(.22,.75,.2,1)'});});}
  compareBar.hidden=!mobile.matches||index!==2;comparePrev.disabled=compared===0;compareNext.disabled=compared===propertyObjects.length-1;compareCount.textContent=`${compared+1} / ${propertyObjects.length}`;
 }
 function drawMapLinks(){
  mapLines.replaceChildren();if(!mapReference)return;
  const w=scene.clientWidth,h=scene.clientHeight,scale=Math.max(w/mapReference.width,h/mapReference.height),ox=(w-mapReference.width*scale)/2,oy=(h-mapReference.height*scale)/2;
  mapLines.setAttribute('viewBox',`0 0 ${w} ${h}`);
  propertyObjects.forEach(card=>{
   const point=mapReference.points.find(point=>String(point.id)===card.dataset.propertyId);if(!point)return;
   const x=ox+point.x*scale,y=oy+point.y*scale,inside=x>=0&&x<=w&&y>=0&&y<=h;
   let note=card.querySelector('.hp-map-frame-note');if(!note){note=paragraph('','hp-map-frame-note');card.querySelector('.hp-shared-property-copy').append(note);}note.textContent=inside?'':'Outside this map view';
   if(!inside)return;
   const line=document.createElementNS('http://www.w3.org/2000/svg','line');line.setAttribute('x1',x);line.setAttribute('y1',y);line.setAttribute('x2',card.offsetLeft+card.offsetWidth/2);line.setAttribute('y2',card.offsetTop+card.offsetHeight*.7);mapLines.append(line);
  });
 }
 new ResizeObserver(drawMapLinks).observe(scene);
 mobile.addEventListener('change',()=>layoutProperties(progress[active],false));
 const termRail=document.createElement('div');termRail.className='hp-term-rail';termLinks.forEach(link=>termRail.append(link.closest('.hp-term-link')));scene.querySelector('.hp-terms-object').prepend(termRail);
 const termDisplay=document.createElement('p');termDisplay.className='hp-term-display';termDisplay.setAttribute('aria-hidden','true');scene.querySelector('.hp-terms-object').prepend(termDisplay);
 function selectTerm(index){scene.style.setProperty('--term-index',index);termDisplay.textContent=termLinks[index]?.textContent??'';termLinks.forEach((link,i)=>link.setAttribute('aria-current',String(i===index)));termCopy.forEach((copy,i)=>copy.hidden=i!==index);}
 termLinks.forEach((link,i)=>{link.setAttribute('role','button');link.addEventListener('click',event=>{event.preventDefault();selectTerm(i);});link.addEventListener('keydown',event=>{if(event.key===' '){event.preventDefault();link.click();}});});selectTerm(0);
 scene.querySelectorAll('.hp-property-question').forEach((question,i)=>{const title=question.querySelector('.hp-question-title'),detail=question.querySelector('.hp-question-detail');if(!title||!detail)return;const button=document.createElement('button');button.type='button';button.textContent=String(i+1).padStart(2,'0');button.dataset.label=title.textContent;button.setAttribute('aria-label',title.textContent);button.setAttribute('aria-expanded','false');detail.id=`hp-diligence-question-${i}`;button.setAttribute('aria-controls',detail.id);detail.hidden=true;title.replaceWith(button);button.addEventListener('click',()=>{const opened=button.getAttribute('aria-expanded')==='true';scene.querySelectorAll('.hp-property-question').forEach(other=>{other.classList.remove('is-open');other.querySelector('button')?.setAttribute('aria-expanded','false');const d=other.querySelector('.hp-question-detail');if(d)d.hidden=true;});button.setAttribute('aria-expanded',String(!opened));detail.hidden=opened;question.classList.toggle('is-open',!opened);});});
 const sceneObjects=[sceneMap,scene.querySelector('.hp-brief-object'),scene.querySelector('.hp-diligence-notes'),scene.querySelector('.hp-terms-object'),scene.querySelector('.hp-execute-object'),compare];
 function stage(index,focus=false){
  const previousStage=Number(journey.dataset.stage??0);const current=stages[active];index=Math.max(0,Math.min(current.length-1,index));progress[active]=index;journey.dataset.stage=String(index);
  current.forEach((node,i)=>{node.hidden=!reading&&i!==index;});
  const track=tracks[keys.indexOf(active)];
  controls.get(active).navigation.querySelectorAll('.hp-stage-link a').forEach((link,i)=>{if(i===index)link.setAttribute('aria-current','step');else link.removeAttribute('aria-current');});
  const c=controls.get(active);c.prev.disabled=index===0;c.next.disabled=index===current.length-1;c.count.textContent=`${String(index+1).padStart(2,'0')} / 06`;
  // Off-scene visual objects do not leave invisible links in the keyboard order.
  const visibleObjects=index===0?[1]:index===1?[0,1,5]:index===2?[5]:index===3?[2]:index===4?[3]:[4];
  sceneObjects.forEach((object,i)=>{if(!object)return;const visible=visibleObjects.includes(i);object.inert=!visible;object.setAttribute('aria-hidden',String(!visible));});
  layoutProperties(index,previousStage!==index);
  if(focus){
   c.navigation.querySelectorAll('.hp-stage-link a')[index]?.focus({preventScroll:true});experience.scrollIntoView({behavior:reduced.matches?'instant':'smooth',block:'start'});
  }
 }
 function selectJourney(key,explicit=true){
  if(!keys.includes(key))return;
  const changed=active!==key||!journey.dataset.intent;active=key;chosen=chosen||explicit;journey.dataset.intent=key;journey.classList.toggle('has-objective',chosen);journey.classList.remove('is-choosing');changeObjective.setAttribute('aria-expanded','false');
  tracks.forEach((track,i)=>track.hidden=keys[i]!==key);
  const selectedTrack=tracks[keys.indexOf(key)],artifact=selectedTrack.querySelector('.hp-artifact-document');
  if(artifact&&changed){const documentObject=scene.querySelector('.hp-brief-object');documentObject.replaceChildren(...[...artifact.children].map(child=>child.cloneNode(true)));const milestones=selectedTrack.querySelector('.hp-artifact-milestones');const destination=scene.querySelector('.hp-milestones');if(milestones&&destination){destination.replaceChildren(...milestones.textContent.split('→').map((label,i)=>{const step=document.createElement('span');step.textContent=label.trim();step.dataset.number=String(i+1).padStart(2,'0');return step;}));}}
  selectedTrack.querySelectorAll('.hp-artifact-topic').forEach((topic,i)=>{if(!termLinks[i]||!termCopy[i])return;termLinks[i].textContent=topic.querySelector('.hp-artifact-topic-label')?.textContent;termCopy[i].querySelector('p').textContent=topic.querySelector('.hp-artifact-topic-explanation')?.textContent;});
  journey.querySelectorAll('.hp-objective-link a').forEach(link=>link.setAttribute('aria-current',String(link.hash===`#journey-${key}`)));
  selectTerm(0);placeNavigation();stage(progress[key]);orderProperties(chosen?key:null);
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
  const data=event.detail;if(!data?.src?.startsWith('data:image/')||!Array.isArray(data.points))return;mapReference=data;
  const image=document.createElement('img');image.className='hp-map-preview';image.src=data.src;image.width=data.width;image.height=data.height;image.alt='Houston reference view from the interactive Aspire Atlas map';image.decoding='async';
  image.addEventListener('load',()=>sceneMap.classList.add('has-preview'),{once:true});sceneMap.prepend(image);
  const pins=(data.points||[]).filter(point=>Number.isFinite(point.x)&&Number.isFinite(point.y)).map(point=>{const pin=document.createElement('span');pin.className='hp-preview-pin';pin.setAttribute('aria-hidden','true');sceneMap.append(pin);return {point,pin};});
  const position=()=>{const w=sceneMap.clientWidth,h=sceneMap.clientHeight,scale=Math.max(w/data.width,h/data.height),x=(w-data.width*scale)/2,y=(h-data.height*scale)/2;pins.forEach(({point,pin})=>{pin.style.left=`${x+point.x*scale}px`;pin.style.top=`${y+point.y*scale}px`;});};new ResizeObserver(position).observe(sceneMap);position();drawMapLinks();
  const attribution=document.createElement('p');attribution.className='hp-preview-attribution';
  const osm=document.createElement('a');osm.href='https://www.openstreetmap.org/copyright';osm.textContent='© OpenStreetMap contributors';
  const proto=document.createElement('a');proto.href='https://protomaps.com/';proto.textContent='Protomaps';attribution.append(osm,' · ',proto);sceneMap.append(attribution);
 });
 // Swiping the scene changes stages; vertical gestures always remain native page scrolling.
 let startTouch=null;
 scene.addEventListener('touchstart',event=>{const t=event.touches[0];startTouch={x:t.clientX,y:t.clientY};},{passive:true});
 scene.addEventListener('touchend',event=>{if(!startTouch)return;const t=event.changedTouches[0],dx=t.clientX-startTouch.x,dy=t.clientY-startTouch.y;if(Math.abs(dx)>60&&Math.abs(dx)>Math.abs(dy)*1.5){if(mobile.matches&&progress[active]===2)setCompared(compared+(dx<0?1:-1));else stage(progress[active]+(dx<0?1:-1));}startTouch=null;},{passive:true});
 const initial=document.querySelector('[data-atlas]')?.dataset.journeyIntent;
 journey.classList.add('is-enhanced');selectJourney(keys.includes(initial)?initial:keys[0],Boolean(initial));
 const followHash=()=>{let hash;try{hash=decodeURIComponent(location.hash.slice(1));}catch{return false;}for(const key of keys){const index=stages[key].findIndex(item=>item.id===hash);if(hash===`journey-${key}`||index>=0){selectJourney(key);if(index>=0)stage(index);return true;}}return false;};
 if(followHash()){const align=()=>experience.scrollIntoView({block:'start'});if(document.readyState==='complete')align();else addEventListener('load',align,{once:true});}
 addEventListener('hashchange',followHash);addEventListener('popstate',followHash);
 }
 enhanceJourney();
 // A territory can be opened by pointer, focus or an explicit button. Its actual link stays a link.
 document.querySelectorAll('.hp-property-types').forEach(rail=>{
  const types=[...rail.querySelectorAll('.hp-property-type')];if(!types.length)return;rail.classList.add('is-enhanced');
  const buttons=[];
  const open=index=>{types.forEach((type,i)=>{type.classList.toggle('is-active',i===index);buttons[i]?.setAttribute('aria-expanded',String(i===index));});};
  types.forEach((type,i)=>{const button=document.createElement('button');button.type='button';button.className='hp-type-toggle';button.textContent=String(i+1).padStart(2,'0');button.setAttribute('aria-label','Show '+type.querySelector('h3').textContent+' property type');button.setAttribute('aria-expanded','false');type.append(button);buttons.push(button);button.addEventListener('click',()=>open(i));type.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse')open(i);});type.addEventListener('focusin',()=>open(i));});
 });
 // Native overflow remains the fallback. Buttons, arrow keys and mouse dragging add precision.
 document.querySelectorAll('[data-hp-rail]').forEach(rail=>{
  const track=rail.querySelector('[data-hp-rail-track]'),bar=rail.querySelector('[data-hp-rail-controls]');if(!track||!bar)return;
  const prev=bar.querySelector('[data-hp-rail-prev]'),next=bar.querySelector('[data-hp-rail-next]'),count=bar.querySelector('[data-hp-rail-count]');
  track.tabIndex=0;track.setAttribute('aria-label',rail.getAttribute('aria-label')||'Browse items');bar.hidden=false;
  const items=()=>[...track.querySelectorAll(':scope > [data-hp-rail-item]')];
  const current=()=>{const bounds=track.getBoundingClientRect(),padding=parseFloat(getComputedStyle(track).scrollPaddingLeft)||0;let best=0,distance=Infinity;items().forEach((item,i)=>{const d=Math.abs(item.getBoundingClientRect().left-bounds.left-padding);if(d<distance){best=i;distance=d;}});return best;};
  const move=direction=>{const all=items(),i=Math.max(0,Math.min(all.length-1,current()+direction));const item=all[i];if(!item)return;const padding=parseFloat(getComputedStyle(track).scrollPaddingLeft)||0;const delta=item.getBoundingClientRect().left-track.getBoundingClientRect().left-padding;track.scrollBy({left:delta,behavior:reduced.matches?'instant':'smooth'});};
  const update=()=>{const all=items(),i=current();bar.hidden=track.scrollWidth<=track.clientWidth+2;prev.disabled=track.scrollLeft<=2;next.disabled=track.scrollLeft>=track.scrollWidth-track.clientWidth-2;let label=String(i+1);if(rail.dataset.hpRail==='field'){const edge=track.getBoundingClientRect();const visible=all.map((item,n)=>({n,box:item.getBoundingClientRect()})).filter(({box})=>box.right>edge.left+40&&box.left<edge.right-40);if(visible.length>1)label=`${visible[0].n+1}–${visible[visible.length-1].n+1}`;}count.textContent=`${label} / ${all.length}`;all.forEach((item,n)=>item.dataset.current=String(n===i));};
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
