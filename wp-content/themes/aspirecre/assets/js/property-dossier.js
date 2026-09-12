/* Single-property interactions; no Atlas application and no additional data request. */
(() => {
 const dossier=document.querySelector('.property-dossier');if(!dossier)return;
 const tabs=dossier.querySelector('[data-property-tabs]');if(tabs)window.AspirePropertyTabs(tabs);
 const dialog=dossier.querySelector('.dossier-viewer');
 if(dialog){
  const photos=JSON.parse(dialog.querySelector('[data-gallery-data]').textContent);
  const image=dialog.querySelector('[data-gallery-image]'),count=dialog.querySelector('[data-gallery-count]'),caption=dialog.querySelector('[data-gallery-caption]');
  const close=dialog.querySelector('[data-gallery-close]');let current=0,opener=null;
  const stage=dossier.querySelector('[data-stage-image]'),stageCount=dossier.querySelector('[data-stage-count]');
  function stageShow(index){current=(index+photos.length)%photos.length;const photo=photos[current];if(stage){stage.srcset=photo.srcset;stage.src=photo.src;stage.alt=photo.alt;}if(stageCount)stageCount.textContent=`${current+1} / ${photos.length}`;}
  dossier.querySelector('[data-stage-prev]')?.addEventListener('click',()=>stageShow(current-1));
  dossier.querySelector('[data-stage-next]')?.addEventListener('click',()=>stageShow(current+1));
  function show(index){current=(index+photos.length)%photos.length;const photo=photos[current];image.sizes='90vw';image.srcset=photo.srcset;image.src=photo.src;image.alt=photo.alt;caption.textContent=photo.alt;count.textContent=`${current+1} / ${photos.length}`;}
  dossier.querySelectorAll('[data-gallery-open]').forEach(link=>link.addEventListener('click',event=>{
   if(typeof dialog.showModal!=='function')return;event.preventDefault();opener=link;show(link.dataset.galleryOpen==='current'?current:Number(link.dataset.galleryOpen)||0);dialog.showModal();document.documentElement.classList.add('dossier-gallery-locked');close.focus();
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
  dialog.addEventListener('close',()=>{document.documentElement.classList.remove('dossier-gallery-locked');stageShow(current);image.removeAttribute('src');image.removeAttribute('srcset');opener?.focus({preventScroll:true});});
 }
})();
