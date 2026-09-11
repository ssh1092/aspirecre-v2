import * as maplibregl from 'maplibre-gl';
import {Protocol,PMTiles} from 'pmtiles';
import {atlasStyle} from './style.js';
import {loadProperties,addPropertyLayers} from './properties.js';
import 'maplibre-gl/dist/maplibre-gl.css';
import './view.css';
const protocol = new Protocol();
maplibregl.addProtocol('pmtiles',protocol.tile);
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
async function start(root) {
 const mapStatus=root.querySelector('.atlas-map-status');
 const dataStatus=root.querySelector('.atlas-data-status');
 const selection=root.querySelector('.atlas-selection');
 const select=selection.querySelector('select');
 const selectedLabel=selection.querySelector('.atlas-selected-label');
 const count=root.querySelector('.atlas-count');
 let map,selectedId=null,hoverId=null,failed=false;
 const fallbackLink=root.querySelector('.atlas-fallback-link');
 fallbackLink.addEventListener('click',e=>{e.preventDefault();const list=root.querySelector('.atlas-fallback-properties');list.hidden=!list.hidden;fallbackLink.setAttribute('aria-expanded',String(!list.hidden));});
 const fail=message=>{failed=true;root.classList.add('atlas-map-failed');root.dataset.mapState='failed';mapStatus.textContent=message;};
 root.querySelectorAll('.atlas-intent').forEach(button=>button.addEventListener('click',()=>{
  root.querySelectorAll('.atlas-intent').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
  root.querySelector('.atlas-intent-status').textContent=button.querySelector('strong').textContent+' selected. This workflow is coming in a later phase; you can explore the map now.';
 }));
 root.querySelector('.atlas-brief')?.addEventListener('click',()=>{root.querySelector('.atlas-intent-status').textContent='Build My Brief is coming in a later phase.';});
 const controller=new AbortController();
 const timeout=setTimeout(()=>controller.abort(),15000);
 const dataPromise=loadProperties(root.dataset.endpoint,controller.signal).then(data=>{
  root.dataset.propertyCount=String(data.features.length);
  count.textContent=`${data.features.length} mapped ${data.features.length===1?'property':'properties'}`;
  dataStatus.textContent=data.features.length?'Available opportunities · Prototype locations':'No mapped properties are available yet.';
  data.features.forEach(f=>{const o=document.createElement('option');o.value=String(f.id);o.textContent=f.properties.title+(f.properties.coordinateStatus==='provisional'?' · provisional location':'');select.append(o);});
  selection.hidden=!data.features.length;
  return data;
 }).catch(()=>{count.textContent='Mapped property count unavailable';dataStatus.textContent='Property data could not load. Use View Properties or reload to try again.';return null;}).finally(()=>clearTimeout(timeout));
 let gl;
 try { gl=document.createElement('canvas').getContext('webgl2'); } catch {}
 if (!gl) {fail('Interactive maps are not supported in this browser. You can still view Properties.');return;}
 gl.getExtension('WEBGL_lose_context')?.loseContext();
 maplibregl.setWorkerUrl(root.dataset.worker);
 try {
  const archive=new PMTiles(root.dataset.tiles);protocol.add(archive);
  // Validate the local archive before creating WebGL. Range-capable static serving is required.
  const header=await Promise.race([archive.getHeader(),new Promise((_,reject)=>setTimeout(()=>reject(Error('Basemap timeout')),15000))]);
  if(header.tileType!==1) throw Error('Expected vector archive');
  map=new maplibregl.Map({container:root.querySelector('.atlas-map'),style:atlasStyle(root.dataset.tiles,root.dataset.glyphs),center:[-95.45,29.82],zoom:9.4,bearing:reduced?0:-7,pitch:reduced?0:38,minZoom:8,maxZoom:16,maxBounds:[[-95.95,29.45],[-95.05,30.25]],attributionControl:false,cooperativeGestures:true,renderWorldCopies:false});
  map.addControl(new maplibregl.NavigationControl({visualizePitch:true}),'top-right');
  map.getCanvas().setAttribute('aria-label','Houston property map. Use arrow keys to pan, plus or minus to zoom, or the property selector for keyboard selection.');
  const watch=setTimeout(()=>{if(!root.dataset.mapState)fail('Houston basemap is taking too long to load. View Properties or reload to try again.');},20000);
  map.on('error',event=>{if(event.sourceId==='houston' || !map.isStyleLoaded())fail('Houston basemap could not load. View Properties or reload to try again.');});
  map.on('load',async()=>{
   clearTimeout(watch);
   if(!failed){root.dataset.mapState='ready';mapStatus.textContent='Houston basemap · Ready';}
   const data=await dataPromise;
   if(!data)return;
   addPropertyLayers(map,data);
   root.dataset.markerCount=String(data.features.length);
   const popup=new maplibregl.Popup({closeButton:false,closeOnClick:false,offset:16});
   const showLabel=f=>{
    const label=document.createElement('div');label.className='atlas-map-label';label.textContent=f.properties.title+(f.properties.coordinateStatus==='provisional'?' · provisional location':'');
    popup.setLngLat(f.geometry.coordinates).setDOMContent(label).addTo(map);
   };
   const choose=f=>{
    if(selectedId!==null)map.setFeatureState({source:'atlas-properties',id:selectedId},{selected:false});
    selectedId=f?.id??null;select.value=f?String(f.id):'';
    root.dataset.selectedProperty=f?String(f.id):'';
    if(!f){selectedLabel.textContent='';popup.remove();return;}
    map.setFeatureState({source:'atlas-properties',id:f.id},{selected:true});showLabel(f);
    selectedLabel.textContent=f.properties.title+(f.properties.coordinateStatus==='provisional'?' — provisional location; visual verification required.':' — prototype location.')+' Property Focus is coming in a later phase.';
   };
   select.addEventListener('change',()=>{const f=data.features.find(f=>String(f.id)===select.value);choose(f);if(f)map.easeTo({center:f.geometry.coordinates,duration:reduced?0:500});});
   map.on('mousemove','atlas-properties',e=>{
    const f=data.features.find(f=>f.id===e.features?.[0]?.id);if(!f)return;
    if(hoverId!==null)map.setFeatureState({source:'atlas-properties',id:hoverId},{hover:false});
    hoverId=f.id;root.dataset.hoveredProperty=String(f.id);map.setFeatureState({source:'atlas-properties',id:f.id},{hover:true});map.getCanvas().style.cursor='pointer';showLabel(f);
   });
   map.on('mouseleave','atlas-properties',()=>{
    if(hoverId!==null)map.setFeatureState({source:'atlas-properties',id:hoverId},{hover:false});hoverId=null;root.dataset.hoveredProperty='';map.getCanvas().style.cursor='';
    const f=data.features.find(f=>f.id===selectedId);if(f)showLabel(f);else popup.remove();
   });
   map.on('click','atlas-properties',e=>choose(data.features.find(f=>f.id===e.features?.[0]?.id)));
  });
  map.getCanvas().addEventListener('webglcontextlost',()=>fail('The map graphics connection was lost. View Properties or reload to try again.'));
 } catch {fail('Houston basemap is unavailable. You can still view Properties.');}
}
document.querySelectorAll('[data-atlas]').forEach(root=>start(root));
