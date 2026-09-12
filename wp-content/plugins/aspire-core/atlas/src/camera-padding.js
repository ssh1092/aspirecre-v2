/** Keep transient DOM measurements safe for MapLibre without changing usable layouts. */
export function cameraInsets(measured, width, height) {
 const finite=value=>Number.isFinite(value)?Math.max(0,value):0;
 const dimensions={x:finite(width),y:finite(height)};
 const padding={top:finite(measured?.top),right:finite(measured?.right),bottom:finite(measured?.bottom),left:finite(measured?.left)};
 for(const [start,end,size] of [['left','right',dimensions.x],['top','bottom',dimensions.y]]){
  // A hidden/reparented panel can report zero bounds while its parent is offscreen.
  // Cap each measurement first so even extreme values cannot overflow the sum.
  padding[start]=Math.min(padding[start],size);padding[end]=Math.min(padding[end],size);
  const total=padding[start]+padding[end],available=Math.max(0,size-40);
  if(total>available){const scale=available/total;padding[start]*=scale;padding[end]*=scale;}
 }
 return padding;
}
