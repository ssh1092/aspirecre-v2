/* Single-property interactions; no Atlas application and no additional data request. */
(() => {
 const dossier=document.querySelector('.property-dossier');if(!dossier)return;
 dossier.querySelectorAll('a[href^="#property-"]').forEach(link=>link.addEventListener('click',event=>{
  const target=document.getElementById(link.hash.slice(1));if(!target)return;event.preventDefault();history.replaceState(null,'',link.hash);target.tabIndex=-1;target.focus({preventScroll:true});target.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'});
 }));
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
