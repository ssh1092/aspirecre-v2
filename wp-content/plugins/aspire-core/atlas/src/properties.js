export async function loadProperties(url, signal) {
 const response = await fetch(url,{signal,headers:{Accept:'application/json'}});
 if (!response.ok) throw new Error('Property service unavailable');
 const data = await response.json();
 if (data?.type !== 'FeatureCollection' || !Array.isArray(data.features)) throw new Error('Invalid property response');
 return {...data,features:data.features.filter(f=>f?.type==='Feature' && f.geometry?.type==='Point' && f.geometry.coordinates?.length===2 && f.geometry.coordinates.every(Number.isFinite) && Math.abs(f.geometry.coordinates[0])<=180 && Math.abs(f.geometry.coordinates[1])<=90 && Number.isInteger(f.id) && typeof f.properties?.title==='string')};
}
export function addPropertyLayers(map, data) {
 // A single GeoJSON source can gain cluster options later without changing the API.
 map.addSource('atlas-properties',{type:'geojson',data});
 map.addLayer({id:'atlas-property-ring',type:'circle',source:'atlas-properties',paint:{'circle-radius':13,'circle-color':'transparent','circle-stroke-width':2,'circle-stroke-color':'#22B7A8','circle-stroke-opacity':['case',['any',['boolean',['feature-state','hover'],false],['boolean',['feature-state','selected'],false]],1,0]}});
 map.addLayer({id:'atlas-properties',type:'circle',source:'atlas-properties',paint:{'circle-radius':['case',['boolean',['feature-state','hover'],false],8,6],'circle-opacity':['case',['boolean',['feature-state','dimmed'],false],0.38,1],'circle-stroke-opacity':['case',['boolean',['feature-state','dimmed'],false],0.38,1],'circle-color':'#22B7A8','circle-stroke-width':2,'circle-stroke-color':'#07110F'}});
}
