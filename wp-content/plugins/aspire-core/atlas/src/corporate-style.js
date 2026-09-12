// Reusable presentation adapter for the inspected Protomaps v4 schema. Opt-in only:
// Directory and Property maps continue to use the unchanged shared base style.
export function corporateStyle(base) {
 const style=structuredClone(base);
 const colors={
  background:['background-color','#eceae3'],land:['fill-color','#eeece5'],
  parks:['fill-color','#d4dfc8'],water:['fill-color','#bfd6df'],
  waterways:['line-color','#a5c7d4'],buildings:['fill-color','#d4d2cb'],
  'minor-roads':['line-color','#faf9f4'],'major-roads':['line-color','#ffffff'],
  freeways:['line-color','#f7f4e9'],
 };
 for(const layer of style.layers){
  if(colors[layer.id]){const[key,value]=colors[layer.id];layer.paint[key]=value;}
  if(layer.id==='parks'){
   layer.paint['fill-opacity']=.85;
   layer.filter=['in',['get','kind'],['literal',['park','forest','nature_reserve','wood','grass','grassland','meadow','golf_course','garden','recreation_ground']]];
  }
  if(layer.id==='buildings'){layer.minzoom=12;layer.paint['fill-outline-color']='#c0c0b9';layer.paint['fill-opacity']=['interpolate',['linear'],['zoom'],12,.35,15,.85];}
  if(layer.id==='minor-roads')layer.paint['line-width']=['interpolate',['linear'],['zoom'],10,.4,12,1.1,15,4];
  if(layer.id==='major-roads')layer.paint['line-width']=['interpolate',['linear'],['zoom'],8,.7,11,1.8,15,6];
  if(layer.id==='freeways')layer.paint['line-width']=['interpolate',['linear'],['zoom'],7,1.2,10,2.4,13,4,15,7];
  if(layer.type==='symbol'){
   layer.paint['text-halo-color']='#f7f5ef';layer.paint['text-halo-width']=1.8;
   layer.paint['text-color']=layer.id==='localities'?'#454b43':layer.id==='route-numbers'?'#62675f':'#687066';
  }
  if(layer.id==='localities'){
   layer.layout['text-size']=['interpolate',['linear'],['zoom'],7,['case',['==',['get','name'],'Houston'],19,11],11,['case',['==',['get','name'],'Houston'],24,14],15,17];
   layer.layout['text-letter-spacing']=.035;
  }
 }
 // Real roads/kinds verified in local z9, z12 and z15 vector tiles. Casings clarify
 // arterials/highways without adding a second mapping stack or imaginary features.
 const roadIndex=style.layers.findIndex(layer=>layer.id==='minor-roads');
 const roads=style.layers.find(layer=>layer.id==='major-roads');
 style.layers.splice(roadIndex,0,
  {...structuredClone(roads),id:'arterial-casing',paint:{'line-color':'#d6d2c6','line-width':['interpolate',['linear'],['zoom'],8,1.3,11,2.8,15,8]}},
  {...structuredClone(roads),id:'highway-casing',filter:['==',['get','kind'],'highway'],paint:{'line-color':'#c4baa3','line-width':['interpolate',['linear'],['zoom'],7,1.9,10,3.5,13,5.6,15,9.2]}}
 );
 const localityIndex=style.layers.findIndex(layer=>layer.id==='localities');
 const locality=style.layers[localityIndex];
 style.layers.splice(localityIndex,0,
  {...structuredClone(locality),id:'neighbourhoods',minzoom:12,filter:['in',['get','kind'],['literal',['macrohood','neighbourhood']]],paint:{'text-color':'#72766c','text-halo-color':'#f7f5ef','text-halo-width':1.5},layout:{...locality.layout,'text-size':11,'text-letter-spacing':.03}},
  {...structuredClone(style.layers.find(layer=>layer.id==='road-labels')),id:'street-labels',minzoom:14,filter:['==',['get','kind'],'minor_road'],layout:{...style.layers.find(layer=>layer.id==='road-labels').layout,'text-size':10,'symbol-spacing':280}}
 );
 return style;
}

export function corporateMarkers(map) {
 map.setPaintProperty('atlas-property-ring','circle-radius',15);
 map.setPaintProperty('atlas-property-ring','circle-stroke-color','#42664a');
 map.setPaintProperty('atlas-property-ring','circle-stroke-width',1.25);
 map.setPaintProperty('atlas-properties','circle-radius',['case',['boolean',['feature-state','selected'],false],10,['boolean',['feature-state','hover'],false],9,7]);
 map.setPaintProperty('atlas-properties','circle-color',['case',['any',['boolean',['feature-state','selected'],false],['boolean',['feature-state','hover'],false]],'#214e38','#426c4c']);
 map.setPaintProperty('atlas-properties','circle-stroke-color','#faf9f3');
 map.setPaintProperty('atlas-properties','circle-stroke-width',2.5);
}
