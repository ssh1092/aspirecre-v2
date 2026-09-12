// Reuse Atlas's local map dependency, worker, style and marker source/layers; no Atlas app runtime.
import * as maplibregl from 'maplibre-gl';
import {Protocol,PMTiles} from 'pmtiles';
import {atlasStyle} from '../../atlas/src/style.js';
import {addPropertyLayers} from '../../atlas/src/properties.js';
import {cardData} from './data.js';
import 'maplibre-gl/dist/maplibre-gl.css';
const protocol=new Protocol();maplibregl.addProtocol('pmtiles',protocol.tile);
export async function createDirectoryMap(root,features,actions){
 const status=root.querySelector('.directory-map-status'),panel=root.querySelector('.directory-map-panel'),preview=root.querySelector('.directory-map-preview'),reduced=matchMedia('(prefers-reduced-motion: reduce)');
 const archive=new PMTiles(root.dataset.tiles);protocol.add(archive);await archive.getHeader();maplibregl.setWorkerUrl(root.dataset.worker);
 const map=new maplibregl.Map({container:root.querySelector('.directory-map'),style:atlasStyle(root.dataset.tiles,root.dataset.glyphs),center:[-95.45,29.82],zoom:9.4,minZoom:7,maxZoom:17,bearing:-7,pitch:0,attributionControl:false,cooperativeGestures:root.dataset.mobile!=='true',renderWorldCopies:false});
 map.addControl(new maplibregl.NavigationControl({showCompass:false}),'top-right');map.getCanvas().setAttribute('aria-label','Houston property locations. Use arrow keys to pan and plus or minus to zoom. Use Locate a property or the property cards to select a listing.');
 map.on('error',e=>{if(e.sourceId==='houston'||!map.isStyleLoaded())status.textContent='Houston basemap is unavailable. Browse the property list to continue.';});
 await new Promise((resolve,reject)=>{const timeout=setTimeout(()=>reject(Error('Map timeout')),20000);map.once('load',()=>{clearTimeout(timeout);resolve();});});
 status.textContent='';addPropertyLayers(map,{type:'FeatureCollection',features});
 const popup=new maplibregl.Popup({closeButton:false,closeOnClick:false,offset:14});let lastSignature='',lastSelected=null;
 const resize=new ResizeObserver(()=>map.resize());resize.observe(panel);map.on('remove',()=>resize.disconnect());
 map.on('mousemove','atlas-properties',e=>actions.hover(e.features?.[0]?.id??null));map.on('mouseleave','atlas-properties',()=>actions.hover(null));map.on('click','atlas-properties',e=>actions.select(e.features?.[0]?.id??null,'map'));
 function showPreview(f){preview.replaceChildren();preview.hidden=!f;if(!f)return;const data=cardData(f,location.origin);if(data.image){const img=document.createElement('img');img.className='directory-preview-image';img.src=data.image.url;img.alt='';preview.append(img);}for(const [tag,text] of [['strong',data.title],['p',data.eyebrow],['p',data.primary]]){const el=document.createElement(tag);el.textContent=text;preview.append(el);}if(data.permalink){const link=document.createElement('a');link.href=data.permalink;link.textContent='VIEW PROPERTY →';preview.append(link);}}
 return{resize:()=>{map.resize();if(root.dataset.mobile==='true')map.cooperativeGestures.disable();else map.cooperativeGestures.enable();},update(visible,selected,hovered,reason){
  const ids=visible.map(f=>f.id),signature=ids.join(','),chosen=visible.find(f=>f.id===selected),highlight=visible.find(f=>f.id===hovered);
  if(signature!==lastSignature||reason==='ready'){const expression=['in',['id'],['literal',ids]];map.setFilter('atlas-properties',expression);map.setFilter('atlas-property-ring',expression);lastSignature=signature;}root.dataset.markerCount=String(ids.length);
  for(const f of features)map.setFeatureState({source:'atlas-properties',id:f.id},{selected:f.id===selected,hover:f.id===hovered,dimmed:false});
  if(highlight){const label=document.createElement('span');label.textContent=highlight.properties.displayTitle||highlight.properties.title;popup.setLngLat(highlight.geometry.coordinates).setDOMContent(label).addTo(map);}else popup.remove();
  map.getCanvas().style.cursor=highlight?'pointer':'';
  if(lastSelected!==selected){showPreview(chosen);lastSelected=selected;}
  if(reason==='select'&&chosen){map.easeTo({center:chosen.geometry.coordinates,zoom:13,padding:{top:85,bottom:170,left:35,right:50},duration:reduced.matches?0:600});}
  else if(['ready','filter'].includes(reason)&&visible.length){map.resize();const points=visible.map(f=>f.geometry.coordinates),bounds=points.reduce((b,p)=>b.extend(p),new maplibregl.LngLatBounds(points[0],points[0]));map.setPadding({top:110,bottom:70,left:55,right:65});map.fitBounds(bounds,{padding:0,maxZoom:visible.length===1?12:11.5,duration:reduced.matches?0:600});}
 }};
}
