/* Progressive presentation only. All service, property and people content comes from WordPress. */
(() => {
 'use strict';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 function enhanceJourney(){
  const journey=document.querySelector('.hp-journey');
  if(!journey)return;
  const keys=['tenant','owner','investor','management'].filter(key=>journey.querySelector(`#journey-${key}`));
  if(!keys.length)return;
  const tracks=Object.fromEntries(keys.map(key=>[key,journey.querySelector(`#journey-${key}`)]));
  let active=keys[0],activeStage=0;
	const serviceIntents={'tenant-representation':'tenant','landlord-representation':'owner','investment-sales':'investor','investor-developer-services':'investor','property-management':'management','cre-consulting':'management'};
	const objectives=journey.querySelector('.hp-objectives');objectives.setAttribute('role','tablist');objectives.setAttribute('aria-label','Choose a client objective');
	keys.forEach(key=>{tracks[key].setAttribute('role','tabpanel');const nav=tracks[key].querySelector('.hp-stage-navigation');nav.setAttribute('role','tablist');nav.setAttribute('aria-label','Journey stages');tracks[key].querySelectorAll('.hp-stage-panel').forEach(panel=>panel.setAttribute('role','tabpanel'));});

  const setStage=(index,focus=false)=>{
   const track=tracks[active],stages=[...track.querySelectorAll('.hp-stage')];
   activeStage=Math.max(0,Math.min(stages.length-1,index));
   stages.forEach((stage,i)=>{
    const selected=i===activeStage,trigger=stage.querySelector('.hp-accordion-trigger');
    stage.hidden=!selected;
    stage.classList.toggle('is-active',selected);
    trigger.setAttribute('aria-expanded',String(selected));
    // Accordion headings remain visible on mobile even when their panels are closed.
    if(matchMedia('(max-width: 767px)').matches)stage.hidden=false;
    stage.querySelector('.hp-stage-panel').hidden=!selected;
    stage.querySelector('.hp-stage-prev').disabled=i===0;
    stage.querySelector('.hp-stage-next').disabled=i===stages.length-1;
   });
   const tabs=[...track.querySelectorAll('.hp-stage-link button')];
   tabs.forEach((tab,i)=>{tab.setAttribute('aria-selected',String(i===activeStage));tab.tabIndex=i===activeStage?0:-1;});
   if(focus)tabs[activeStage]?.focus();
  };
  const selectJourney=(key)=>{
   if(!keys.includes(key))return;
   active=key;activeStage=0;
   keys.forEach(item=>{
    const selected=item===key,button=journey.querySelector(`.hp-objective-button[data-journey="${item}"]`);
    tracks[item].hidden=!selected;
    button.setAttribute('aria-selected',String(selected));button.tabIndex=selected?0:-1;
   });
   setStage(0);
  };
  journey.querySelectorAll('.hp-objective-button').forEach((button,index)=>{
   button.addEventListener('click',()=>selectJourney(button.dataset.journey));
   button.addEventListener('keydown',event=>{
    if(!['ArrowRight','ArrowLeft','Home','End'].includes(event.key))return;
    event.preventDefault();let next=index+(event.key==='ArrowRight'?1:event.key==='ArrowLeft'?-1:0);
    if(event.key==='Home')next=0;if(event.key==='End')next=keys.length-1;
    next=(next+keys.length)%keys.length;selectJourney(keys[next]);journey.querySelectorAll('.hp-objective-button')[next].focus();
   });
  });
  keys.forEach(key=>{
   const track=tracks[key],tabs=[...track.querySelectorAll('.hp-stage-link button')];
   tabs.forEach((tab,index)=>{
    tab.addEventListener('click',()=>setStage(index));
    tab.addEventListener('keydown',event=>{if(!['ArrowRight','ArrowLeft','Home','End'].includes(event.key))return;event.preventDefault();let next=index+(event.key==='ArrowRight'?1:-1);if(event.key==='Home')next=0;if(event.key==='End')next=tabs.length-1;setStage(Math.max(0,Math.min(tabs.length-1,next)),true);});
   });
   track.querySelectorAll('.hp-accordion-trigger').forEach((button,index)=>button.addEventListener('click',()=>setStage(index)));
   track.querySelectorAll('.hp-stage-prev').forEach(button=>button.addEventListener('click',()=>setStage(activeStage-1)));
   track.querySelectorAll('.hp-stage-next').forEach(button=>button.addEventListener('click',()=>setStage(activeStage+1)));
  });
  const mobile=matchMedia('(max-width: 767px)');
  const refresh=()=>setStage(activeStage);mobile.addEventListener('change',refresh);
  document.addEventListener('aspire:intent',event=>selectJourney(event.detail?.intent));
  document.addEventListener('click',event=>{const link=event.target.closest('a[href*="#"]');if(!link)return;let hash;try{hash=new URL(link.href,location.href).hash.slice(1);}catch{return;}const key=serviceIntents[hash];if(key)selectJourney(key);});
  const initialIntent=document.querySelector('[data-journey-intent]')?.dataset.journeyIntent;
  const hashIntent=serviceIntents[location.hash.slice(1)];
  journey.classList.add('is-enhanced');selectJourney(keys.includes(initialIntent)?initialIntent:hashIntent||keys[0]);
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
