// Atlas-only presentation. Shared cartography and Property/Directory maps keep their defaults.
export function corporateStyle(base) {
 const style=structuredClone(base);
 const colors={
  background:['background-color','#252f2d'],land:['fill-color','#2b3632'],
  parks:['fill-color','#33433a'],water:['fill-color','#263c3e'],
  waterways:['line-color','#415658'],buildings:['fill-color','#36413b'],
  'minor-roads':['line-color','#414d46'],'major-roads':['line-color','#626c60'],
  freeways:['line-color','#92998a'],
 };
 for(const layer of style.layers){
  if(colors[layer.id]){const[key,value]=colors[layer.id];layer.paint[key]=value;}
  if(layer.id==='buildings')layer.paint['fill-outline-color']='#49544b';
  if(layer.type==='symbol'){
   layer.paint['text-halo-color']='#2b3632';
   layer.paint['text-color']=layer.id==='localities'?'#d4d7cb':layer.id==='route-numbers'?'#d8dacd':'#b9c1b2';
  }
 }
 return style;
}

export function corporateMarkers(map) {
 map.setPaintProperty('atlas-property-ring','circle-stroke-color','#a4bfae');
 map.setPaintProperty('atlas-property-ring','circle-stroke-width',1.5);
 map.setPaintProperty('atlas-properties','circle-color',['case',['any',['boolean',['feature-state','selected'],false],['boolean',['feature-state','hover'],false]],'#91b9a4','#cbd2bd']);
 map.setPaintProperty('atlas-properties','circle-stroke-color','#31473c');
}
