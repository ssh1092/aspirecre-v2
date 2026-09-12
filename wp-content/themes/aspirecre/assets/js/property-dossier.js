/* Single-property interactions; no Atlas application and no additional data request. */
(() => {
 const dossier=document.querySelector('.property-dossier');if(!dossier)return;
 dossier.querySelectorAll('a[href^="#property-"]').forEach(link=>link.addEventListener('click',event=>{
  const target=document.getElementById(link.hash.slice(1));if(!target)return;event.preventDefault();history.replaceState(null,'',link.hash);target.tabIndex=-1;target.focus({preventScroll:true});target.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'});
 }));
 const dialog=dossier.querySelector('.dossier-viewer');
 if(dialog){
  const photos=JSON.parse(dialog.querySelector('[data-gallery-data]').textContent);
  const image=dialog.querySelector('[data-gallery-image]'),count=dialog.querySelector('[data-gallery-count]'),caption=dialog.querySelector('[data-gallery-caption]');
  const close=dialog.querySelector('[data-gallery-close]');let current=0,opener=null;
  function show(index){current=(index+photos.length)%photos.length;const photo=photos[current];image.sizes='90vw';image.srcset=photo.srcset;image.src=photo.src;image.alt=photo.alt;caption.textContent=photo.alt;count.textContent=`${current+1} / ${photos.length}`;}
  dossier.querySelectorAll('[data-gallery-open]').forEach(link=>link.addEventListener('click',event=>{
   if(typeof dialog.showModal!=='function')return;event.preventDefault();opener=link;show(Number(link.dataset.galleryOpen)||0);dialog.showModal();document.documentElement.classList.add('dossier-gallery-locked');close.focus();
  }));
  close.addEventListener('click',()=>dialog.close());
  dialog.querySelector('[data-gallery-prev]').addEventListener('click',()=>show(current-1));
  dialog.querySelector('[data-gallery-next]').addEventListener('click',()=>show(current+1));
  dialog.addEventListener('keydown',event=>{
   if(event.key==='ArrowRight'){event.preventDefault();show(current+1);}
   if(event.key==='ArrowLeft'){event.preventDefault();show(current-1);}
   if(event.key==='Escape'){event.preventDefault();dialog.close();}
   if(event.key==='Tab'){const buttons=[...dialog.querySelectorAll('button')];const first=buttons[0],last=buttons[buttons.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}
  });
  dialog.addEventListener('close',()=>{document.documentElement.classList.remove('dossier-gallery-locked');image.removeAttribute('src');image.removeAttribute('srcset');opener?.focus({preventScroll:true});});
 }
 const root=dossier.querySelector('[data-property-map]');if(!root)return;
 async function load(){
  const status=root.querySelector('.directory-map-status');status.textContent='Loading property location…';
  try{
   await new Promise((resolve,reject)=>{const css=document.createElement('link');css.rel='stylesheet';css.href=root.dataset.mapStyle;css.onload=resolve;css.onerror=reject;document.head.append(css);});
   const feature=JSON.parse(root.dataset.feature),module=await import(root.dataset.mapModule);
   const map=await module.createDirectoryMap(root,[feature],{hover:()=>{},select:()=>{}});
   map.update([feature],feature.id,null,'ready');map.update([feature],feature.id,null,'select');
   root.querySelector('canvas').setAttribute('aria-label',`Location of ${feature.properties.displayTitle}. Use arrow keys to pan and plus or minus to zoom.`);
  }catch{status.textContent='The map is unavailable. The property address is shown above.';}
 }
 if('IntersectionObserver' in window){const observer=new IntersectionObserver(entries=>{if(entries.some(entry=>entry.isIntersecting)){observer.disconnect();load();}},{rootMargin:'150px'});observer.observe(root);}else load();
})();
