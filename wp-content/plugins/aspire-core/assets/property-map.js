/* Lazy single-property map; shared by public and authenticated admin views. */
(() => {
 const root=document.querySelector('[data-property-map]');if(!root)return;let started=false;
 async function load(){
  if(started)return;started=true;
  const status=root.querySelector('.directory-map-status');status.textContent='Loading property location…';
  try{
   await new Promise((resolve,reject)=>{const css=document.createElement('link');css.rel='stylesheet';css.href=root.dataset.mapStyle;css.onload=resolve;css.onerror=reject;document.head.append(css);});
   const feature=JSON.parse(root.dataset.feature),module=await import(root.dataset.mapModule);
   const map=await module.createDirectoryMap(root,[feature],{hover:()=>{},select:()=>{}});
   map.update([feature],feature.id,null,'ready');map.update([feature],feature.id,null,'select');
   root.querySelector('canvas').setAttribute('aria-label',`Location of ${feature.properties.displayTitle}. Use arrow keys to pan and plus or minus to zoom.`);
  }catch{status.textContent='The map is unavailable. The property address is shown above.';}
 }

 const visible=()=>{if(root.getClientRects().length){load();window.dispatchEvent(new Event('resize'));}};
 document.querySelector('[data-property-tabs]')?.addEventListener('propertymodechange',visible);
 visible();
})();
