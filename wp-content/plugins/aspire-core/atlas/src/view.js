import * as maplibregl from 'maplibre-gl';
import {Protocol,PMTiles} from 'pmtiles';
import {atlasStyle} from './style.js';
import {corporateStyle,corporateMarkers} from './corporate-style.js';
import {cameraInsets} from './camera-padding.js';
import {connectJourneyIntent,captureMapPreview} from './presentation-bridge.js';
import {loadProperties,addPropertyLayers} from './properties.js';
import {createFindSpace} from './find-space.js';
import {createHero} from './hero.js';
import {createMapCards} from './map-cards.js';
import {isDiscovery,isGuided} from './invest.js';
import {AREAS} from './filters.js';
import 'maplibre-gl/dist/maplibre-gl.css';
import './view.css';
import './mobile.css';
import './corporate.css';
import './hero.css';
import {createMobileSheet} from './mobile.js';
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
 let map,failed=false;
 const discovery=createFindSpace(root,reduced);
 if(root.dataset.corporateHero==='true'){connectJourneyIntent(root);createHero(root,discovery);}
 const mobile=createMobileSheet(root);discovery.present(mobile.sync);
 const fallbackLink=root.querySelector('.atlas-fallback-link');
 root.querySelectorAll('.atlas-fallback-link,.atlas-properties-nav').forEach(link=>link.addEventListener('click',e=>{e.preventDefault();const list=root.querySelector('.atlas-fallback-properties');list.hidden=!list.hidden;fallbackLink.setAttribute('aria-expanded',String(!list.hidden));if(!list.hidden)list.querySelector('a')?.focus();}));
 const fail=message=>{failed=true;root.classList.add('atlas-map-failed');root.dataset.mapState='failed';mapStatus.textContent=message;};
 const controller=new AbortController();
 const timeout=setTimeout(()=>controller.abort(),15000);
 const dataPromise=loadProperties(root.dataset.endpoint,controller.signal).then(data=>{
  root.dataset.propertyCount=String(data.features.length);
  count.textContent=`${data.features.length} ASPIRE OPPORTUNITIES`;
  dataStatus.textContent=data.features.length?'':'No opportunities are available on the map yet.';
  data.features.forEach(f=>{const o=document.createElement('option');o.value=String(f.id);o.textContent=f.properties.displayTitle||f.properties.title;select.append(o);});
  selection.hidden=!data.features.length;
  discovery.setData(data);
  return data;
 }).catch(()=>{discovery.setData(null);root.classList.add('atlas-data-failed');count.textContent='ASPIRE OPPORTUNITIES';dataStatus.textContent='Property data could not load. Use View Properties or reload to try again.';return null;}).finally(()=>clearTimeout(timeout));
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
  const baseStyle=atlasStyle(root.dataset.tiles,root.dataset.glyphs),mapElement=root.querySelector('.atlas-map');
  const safePadding=measured=>cameraInsets(measured,mapElement.clientWidth,mapElement.clientHeight);
  const corporate=root.dataset.corporateHero==='true';
  map=new maplibregl.Map({padding:safePadding({top:0,right:0,bottom:0,left:root.clientWidth>900?root.clientWidth*.32:0}),container:mapElement,style:corporate?corporateStyle(baseStyle):baseStyle,center:[-95.45,29.82],zoom:9.4,bearing:reduced||corporate?0:-7,pitch:reduced||corporate?0:38,minZoom:8,maxZoom:16,maxBounds:corporate?[[-97,28.4],[-93.8,31.3]]:[[-95.95,29.45],[-95.05,30.25]],attributionControl:false,cooperativeGestures:!mobile.active,renderWorldCopies:false});
  const measuredPadding=()=>{
   if(mobile.active)return mobile.padding();
   if(discovery.state.propertyFocusOpen){
    const bounds=root.getBoundingClientRect(),panel=root.querySelector('.atlas-dossier').getBoundingClientRect(),nav=root.querySelector('.atlas-nav')?.getBoundingClientRect();
    const top=Math.ceil((nav?.bottom??bounds.top)-bounds.top+25);
    return root.clientWidth<=700?{top,bottom:Math.ceil(bounds.bottom-panel.top+25),left:24,right:40}:{top,bottom:40,left:24,right:Math.ceil(panel.width+70)};
   }
   if(discovery.state.briefOpen){
    const bounds=root.getBoundingClientRect(),panel=root.querySelector('.atlas-cre-brief').getBoundingClientRect();
    return root.clientWidth<=700?{top:160,bottom:Math.max(40,Math.ceil(bounds.bottom-panel.top+20)),left:24,right:40}:{top:130,bottom:40,left:24,right:Math.ceil(panel.width+40)};
   }
   if(isGuided(discovery.state.mode)){
    const bounds=root.getBoundingClientRect(),panel=root.querySelector('.atlas-guided').getBoundingClientRect();
    return root.clientWidth<=700?{top:Math.ceil(panel.bottom-bounds.top+24),bottom:40,left:24,right:40}:{top:130,bottom:40,left:Math.ceil(panel.right-bounds.left+24),right:50};
   }
   if(discovery.state.mode==='explore')return corporate?{top:160,right:95,bottom:95,left:root.querySelector('.atlas-opening').getBoundingClientRect().right-root.getBoundingClientRect().left+38}:{top:0,right:0,bottom:0,left:root.clientWidth>900?root.clientWidth*.32:0};
   const bounds=root.getBoundingClientRect(),rail=root.querySelector('.atlas-filter-rail').getBoundingClientRect(),results=root.querySelector('.atlas-results').getBoundingClientRect();
   return {top:Math.ceil(rail.bottom-bounds.top+30),bottom:Math.ceil(bounds.bottom-results.top+25),left:45,right:65};
  };
  // All setPadding/easeTo paths share this boundary, including mobile sheet transitions.
  const cameraPadding=()=>safePadding(measuredPadding());
  const syncComposition=()=>{map.resize();const padding=cameraPadding();if(Object.keys(padding).some(key=>padding[key]!==map.getPadding()[key]))map.setPadding(padding);};
  mobile.connect(()=>{if(map)syncComposition();});
  root.addEventListener('atlas-mobile-change',()=>{if(mobile.active)map.cooperativeGestures.disable();else map.cooperativeGestures.enable();});
  const composition=new ResizeObserver(()=>{if(!map.isMoving())syncComposition();});composition.observe(root);
  map.on('remove',()=>composition.disconnect());
  map.addControl(new maplibregl.NavigationControl({visualizePitch:true}),'top-right');
  map.getCanvas().setAttribute('aria-label','Houston property map. Use arrow keys to pan, plus or minus to zoom, or the property selector for keyboard selection.');
  const watch=setTimeout(()=>{if(!root.dataset.mapState)fail('Houston basemap is taking too long to load. View Properties or reload to try again.');},20000);
  map.on('error',event=>{if(event.sourceId==='houston' || !map.isStyleLoaded())fail('Houston basemap could not load. View Properties or reload to try again.');});
  map.on('load',async()=>{
   clearTimeout(watch);
   if(!failed){root.dataset.mapState='ready';mapStatus.textContent='';}
   const data=await dataPromise;
   if(!data)return;
   addPropertyLayers(map,data);
   if(root.dataset.corporateHero==='true')corporateMarkers(map);
   if(corporate)captureMapPreview(root,map,data.features);
   root.dataset.markerCount=String(data.features.length);
   const popup=new maplibregl.Popup({closeButton:false,closeOnClick:false,offset:16});
   const previews=corporate?createMapCards(root,map,data.features,discovery):null;
   const showLabel=f=>{
    const label=document.createElement('div');label.className='atlas-map-label';
    if(corporate){const title=document.createElement('strong');title.textContent=f.properties.displayTitle||f.properties.title;const context=document.createElement('span');context.textContent=[f.properties.propertyType?.label,f.properties.location?.city].filter(Boolean).join(' · ');label.append(title,context);}
    else label.textContent=f.properties.displayTitle||f.properties.title;
    popup.setLngLat(f.geometry.coordinates).setDOMContent(label).addTo(map);
   };
   let lastIds='';
   discovery.connect((state,visible,reason)=>{
    previews?.sync(state);
    const finding=isDiscovery(state.mode),guided=isGuided(state.mode)&&!state.briefOpen;
    if(reason==='brief-open'){const c=map.getCenter();state.cameraBeforeBrief={center:[c.lng,c.lat],zoom:map.getZoom(),bearing:map.getBearing(),pitch:map.getPitch()};}
    if(reason==='focus-open' && !state.cameraBeforeFocus){
     const center=map.getCenter();
     state.cameraBeforeFocus={center:[center.lng,center.lat],zoom:map.getZoom(),bearing:map.getBearing(),pitch:map.getPitch()};
    }
    // The original tight camera constraint forces a high minimum zoom on wide screens.
    // A larger navigation envelope lets Houston listings fit in the shorter discovery viewport.
    // This changes no tile coverage or cartography; Explore restores its original constraint.
    if(['enter','explore','ready','brief-open'].includes(reason))map.setMaxBounds(corporate||finding||guided||state.briefOpen?[[-97,28.4],[-93.8,31.3]]:[[-95.95,29.45],[-95.05,30.25]]);
    map.getCanvas().setAttribute('aria-label',state.briefOpen&&!state.propertyFocusOpen?'Houston map provides location and matching property context for your CRE Brief. Matching property buttons are available in the brief review.':guided?'Houston map provides location context. Enter a location or choose an area in the guided panel.':state.propertyFocusOpen?'Houston property map. Selected property details are in Property Focus. Use arrow keys to pan and plus or minus to zoom.':finding?'Houston property map. Use arrow keys to pan and plus or minus to zoom. Matching property buttons below provide keyboard selection.':'Houston property map. Use arrow keys to pan, plus or minus to zoom, or the property selector for keyboard selection.');
    const selected=visible.find(f=>f.id===state.selectedPropertyId);
    const hovered=visible.find(f=>f.id===state.hoveredPropertyId);
    const ids=visible.map(f=>f.id),signature=ids.join(',');
    if(signature!==lastIds || reason==='ready'){
     const filter=['in',['id'],['literal',ids]];
     map.setFilter('atlas-properties',filter);map.setFilter('atlas-property-ring',filter);
     root.dataset.markerCount=String(ids.length);lastIds=signature;
    }
    data.features.forEach(f=>map.setFeatureState({source:'atlas-properties',id:f.id},{selected:f.id===state.selectedPropertyId,hover:f.id===state.hoveredPropertyId,dimmed:guided || (state.propertyFocusOpen || finding && !!hovered) && f.id!==state.selectedPropertyId && f.id!==hovered?.id}));
    root.dataset.selectedCoordinateStatus=selected?.properties.coordinateStatus??'';
    select.value=selected?String(selected.id):'';
    selectedLabel.textContent=selected?selected.properties.title+' selected.':'';
    if(corporate&&state.mode==='explore'&&!state.briefOpen&&!state.propertyFocusOpen)popup.remove();else if(hovered || selected)showLabel(state.propertyFocusOpen?selected: hovered??selected);else popup.remove();
    map.getCanvas().style.cursor=reason==='hover' && hovered?'pointer':'';
    const duration=reduced?0:700;
    if(corporate&&['ready','hero-intent'].includes(reason)&&state.mode==='explore'&&!state.briefOpen&&data.features.length){
     syncComposition();const points=data.features.map(f=>f.geometry.coordinates),bounds=points.reduce((box,p)=>box.extend(p),new maplibregl.LngLatBounds(points[0],points[0]));map.fitBounds(bounds,{padding:0,maxZoom:10.2,duration:reason==='ready'||reduced?0:450});
    }else if(reason==='preview'&&selected){
     const point=map.project(selected.geometry.coordinates),padding=cameraPadding();
     if(mobile.active||point.x<padding.left+35||point.x>root.clientWidth-45||point.y<130||point.y>root.clientHeight-65)map.easeTo({center:selected.geometry.coordinates,padding,duration:reduced?0:450});
    }else if(reason==='brief-close'){
     map.resize();if(state.cameraBeforeBrief)map.easeTo({...state.cameraBeforeBrief,padding:cameraPadding(),duration});state.cameraBeforeBrief=null;
    }else if(state.briefOpen&&['brief-open','brief-area','brief-review','brief-step','ready'].includes(reason)){
     const preset=AREAS[state.brief.data.location.areaPreset];
     const points=state.brief.screen==='review'&&visible.length?visible.map(f=>f.geometry.coordinates):preset?.bounds;
     syncComposition();
     if(points?.length){const bounds=points.reduce((box,p)=>box.extend(p),new maplibregl.LngLatBounds(points[0],points[0]));map.fitBounds(bounds,{padding:0,maxZoom:11.5,duration,linear:true});}
     else map.easeTo({center:[-95.45,29.82],zoom:9.4,padding:cameraPadding(),duration});
    }else if(reason==='focus-open' && selected){
     map.resize();
     map.easeTo({center:selected.geometry.coordinates,zoom:14,padding:cameraPadding(),duration});
    }else if(reason==='focus-close'){
     map.resize();
     if(state.cameraBeforeFocus)map.easeTo({...state.cameraBeforeFocus,padding:cameraPadding(),duration});
     state.cameraBeforeFocus=null;
    }else if(reason==='explore'){
     map.easeTo({center:[-95.45,29.82],zoom:9.4,bearing:reduced||corporate?0:-7,pitch:reduced||corporate?0:38,padding:cameraPadding(),duration});
    }else if(['card','keyboard'].includes(reason) && selected){
     map.easeTo({center:selected.geometry.coordinates,zoom:finding?13.3:map.getZoom(),padding:cameraPadding(),duration});
    }else if(guided && ['enter','workflow-area','ready'].includes(reason)){
     const flow=state.mode==='manage-asset'?state.management:state.ownerDisposition,preset=AREAS[flow.areaPreset];
     syncComposition();
     if(preset)map.fitBounds(preset.bounds,{padding:0,maxZoom:11.5,duration,linear:true,bearing:reduced||corporate?0:-7});
     else map.easeTo({center:[-95.45,29.82],zoom:9.4,padding:cameraPadding(),duration});
    }else if(finding && ['enter','area','filter','reset','ready','data'].includes(reason)){
     const area=AREAS[state.filters.area];
     // Area choices retain their whole approximate region. Other changes fit the matching set.
     const points=area?area.bounds:visible.map(f=>f.geometry.coordinates);
     if(points.length){
      const bounds=points.reduce((box,point)=>box.extend(point),new maplibregl.LngLatBounds(points[0],points[0]));
      // MapLibre adds fit padding to persistent edge padding: apply the shell inset only once.
      syncComposition();
      map.fitBounds(bounds,{padding:0,maxZoom:points.length===1?12.5:11.5,duration,linear:true,bearing:reduced||corporate?0:-7});
     }
    }
   });
   const selectionOrigin=()=>corporate&&discovery.state.mode==='explore'&&!discovery.state.briefOpen&&!discovery.state.propertyFocusOpen?'preview':null;
   select.addEventListener('change',()=>discovery.select(select.value?Number(select.value):null,selectionOrigin()||'keyboard'));
   map.on('mousemove','atlas-properties',e=>{
    const id=e.features?.[0]?.id;
    if(id!==undefined)discovery.hover(id);
   });
   map.on('mouseleave','atlas-properties',()=>discovery.hover(null));
   map.on('click','atlas-properties',e=>discovery.select(e.features?.[0]?.id??null,selectionOrigin()||'marker'));

  });
  map.getCanvas().addEventListener('webglcontextlost',()=>fail('The map graphics connection was lost. View Properties or reload to try again.'));
 } catch {fail('Houston basemap is unavailable. You can still view Properties.');}
}
document.querySelectorAll('[data-atlas]').forEach(root=>start(root));
