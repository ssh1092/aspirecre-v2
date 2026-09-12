const intents={'find-space':'tenant',invest:'investor','owner-disposition':'owner','manage-asset':'management'};
export const journeyIntentFor=intent=>intents[intent]??null;

export function connectJourneyIntent(root){
 root.addEventListener('click',event=>{
  const button=event.target.closest('.atlas-intent[data-intent]');
  if(!button||!root.contains(button))return;
  const intent=journeyIntentFor(button.dataset.intent);if(!intent)return;
  root.dataset.journeyIntent=intent;
  root.dispatchEvent(new CustomEvent('aspire:intent',{bubbles:true,detail:{intent}}));
 });
}

// One static reference image from the live map, not a second map or a runtime feed.
// Read synchronously during render; preserveDrawingBuffer stays disabled.
export function captureMapPreview(root,map,features){
 map.once('render',()=>{
  try{
   const canvas=map.getCanvas(),width=canvas.clientWidth,height=canvas.clientHeight;
   if(!width||!height)return;
   const src=canvas.toDataURL('image/webp',.8);
   if(!src.startsWith('data:image/'))return;
   const points=features.map(feature=>{const point=map.project(feature.geometry.coordinates);return{id:feature.id,x:point.x,y:point.y};}).filter(point=>Number.isFinite(point.x)&&Number.isFinite(point.y));
   root.dispatchEvent(new CustomEvent('aspire:map-preview',{bubbles:true,detail:{src,width,height,points}}));
  }catch{/* The journey retains its static geographic fallback if capture is unavailable. */}
 });
 map.triggerRepaint();
}
