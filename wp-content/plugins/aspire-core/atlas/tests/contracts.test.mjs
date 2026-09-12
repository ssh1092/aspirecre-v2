import {test} from 'node:test';
import assert from 'node:assert/strict';
import {atlasStyle} from '../src/style.js';
import {corporateStyle,corporateMarkers} from '../src/corporate-style.js';
import {cameraInsets} from '../src/camera-padding.js';
import {journeyIntentFor,connectJourneyIntent,captureMapPreview} from '../src/presentation-bridge.js';
import {loadProperties,addPropertyLayers} from '../src/properties.js';
import {validateStyleMin} from '@maplibre/maplibre-gl-style-spec';
test('camera padding preserves normal desktop and mobile composition without mutating measurements',()=>{
 for(const [measured,width,height] of [[{top:130,right:580,bottom:40,left:24},1440,940],[{top:82,right:60,bottom:612,left:16},390,796]]){
  const before={...measured};assert.deepEqual(cameraInsets(measured,width,height),measured);assert.deepEqual(measured,before);
 }
});
test('camera padding survives scrolled roots, hidden panels and zero-size reparenting',()=>{
 const cases=[
  [{top:1230,right:65,bottom:-235,left:45},1440,940],
  [{top:-270,right:65,bottom:1265,left:45},1024,810],
  [{top:82,right:60,bottom:-122,left:16},390,0],
  [{top:NaN,right:Infinity,bottom:undefined,left:-Infinity},0,0],
  [{top:1e308,right:1e308,bottom:1e308,left:1e308},320,240],
 ];
 for(const [measured,width,height] of cases){
  const padding=cameraInsets(measured,width,height);
  assert.ok(Object.values(padding).every(value=>Number.isFinite(value)&&value>=0));
  assert.ok(padding.left+padding.right<=Math.max(0,width-40)+1e-9);
  assert.ok(padding.top+padding.bottom<=Math.max(0,height-40)+1e-9);
 }
});
test('camera padding recovers across desktop/mobile viewport transitions without retaining stale geometry',()=>{
 const desktop={top:130,right:580,bottom:40,left:24};
 const narrow=cameraInsets(desktop,320,240);
 assert.ok(narrow.left+narrow.right<=280);assert.equal(narrow.top+narrow.bottom,170);
 assert.deepEqual(cameraInsets({top:180,right:60,bottom:-220,left:16},0,0),{top:0,right:0,bottom:0,left:0});
 assert.deepEqual(cameraInsets(desktop,1440,940),desktop);
});
test('real-estate map preserves shared defaults and uses inspected local vector layers',()=>{
 const base=atlasStyle('http://localhost/houston.pmtiles','http://localhost/fonts/{fontstack}/{range}.pbf');
 const before=JSON.stringify(base),corporate=corporateStyle(base);
 assert.deepEqual(validateStyleMin(corporate),[]);
 assert.equal(JSON.stringify(base),before);
 assert.deepEqual(corporate.sources,base.sources);
 for(const layer of base.layers){const adapted=corporate.layers.find(item=>item.id===layer.id);assert.equal(adapted.type,layer.type);assert.equal(adapted['source-layer'],layer['source-layer']);}
 const schema=new Set(['earth','landuse','water','roads','buildings','places']);
 assert.ok(corporate.layers.filter(layer=>layer.source).every(layer=>schema.has(layer['source-layer'])));
 assert.ok(corporate.layers.find(layer=>layer.id==='street-labels').minzoom>=14);
 assert.ok(corporate.layers.find(layer=>layer.id==='buildings').minzoom>=12);
 assert.notEqual(corporate.layers.find(l=>l.id==='background').paint['background-color'],base.layers.find(l=>l.id==='background').paint['background-color']);
 assert.equal(base.layers.find(l=>l.id==='water').paint['fill-color'],'#071516');
});
test('corporate marker palette preserves hit targets and selected/hover behavior',()=>{
 const paints=[];corporateMarkers({setPaintProperty:(...args)=>paints.push(args)});
 assert.ok(paints.every(([layer])=>['atlas-properties','atlas-property-ring'].includes(layer)));
 assert.ok(!paints.some(([,key])=>['circle-opacity','circle-stroke-opacity'].includes(key)));
 const radius=paints.find(([layer,key])=>layer==='atlas-properties'&&key==='circle-radius')[2];
 assert.ok(JSON.stringify(radius).includes('selected'));assert.ok(JSON.stringify(radius).includes('hover'));assert.ok(radius.at(-1)>=6);
 const color=paints.find(([layer,key])=>layer==='atlas-properties'&&key==='circle-color')[2];
 assert.ok(JSON.stringify(color).includes('selected'));assert.ok(JSON.stringify(color).includes('hover'));
});
test('Atlas presentation intent bridge maps the four existing objectives without handling business state',()=>{
 assert.deepEqual(['find-space','invest','owner-disposition','manage-asset','unknown'].map(journeyIntentFor),['tenant','investor','owner','management',null]);
 const events=[];let click;
 const root={dataset:{},contains:()=>true,addEventListener:(name,handler)=>{assert.equal(name,'click');click=handler;},dispatchEvent:event=>events.push(event)};
 connectJourneyIntent(root);click({target:{closest:()=>({dataset:{intent:'invest'}})}});
 assert.equal(root.dataset.journeyIntent,'investor');assert.equal(events.length,1);assert.equal(events[0].type,'aspire:intent');assert.equal(events[0].bubbles,true);assert.deepEqual(events[0].detail,{intent:'investor'});
 click({target:{closest:()=>null}});assert.equal(events.length,1);
});
test('Atlas static map reference captures once from the existing render and fails gracefully',()=>{
 let render;const events=[];const root={dispatchEvent:event=>events.push(event)};
 const map={once:(name,handler)=>{assert.equal(name,'render');render=handler;},triggerRepaint:()=>render(),getCanvas:()=>({clientWidth:1200,clientHeight:900,toDataURL:()=> 'data:image/webp;base64,real-canvas'}),project:coordinates=>({x:coordinates[0],y:coordinates[1]})};
 captureMapPreview(root,map,[{id:120,geometry:{coordinates:[450,260]}}]);
 assert.equal(events.length,1);assert.equal(events[0].type,'aspire:map-preview');assert.deepEqual(events[0].detail.points,[{id:120,x:450,y:260}]);assert.equal(events[0].detail.width,1200);
 map.getCanvas=()=>({clientWidth:1200,clientHeight:900,toDataURL:()=>{throw Error('Canvas unavailable');}});
 assert.doesNotThrow(()=>captureMapPreview(root,map,[]));assert.equal(events.length,1);
});
test('custom map style validates against MapLibre style schema',()=>{
 const style=atlasStyle('http://localhost/houston.pmtiles','http://localhost/fonts/{fontstack}/{range}.pbf');
 assert.deepEqual(validateStyleMin(style),[]);
 assert.equal(style.layers.find(l=>l.id==='water').paint['fill-color'],'#071516');
 assert.ok(!style.layers.some(l=>l['source-layer']==='pois'));
});
test('property fetch rejects HTTP/schema errors and removes unusable geometry',async()=>{
 const original=globalThis.fetch;
 try{
  globalThis.fetch=async()=>({ok:false});await assert.rejects(loadProperties('/properties'));
  globalThis.fetch=async()=>({ok:true,json:async()=>({})});await assert.rejects(loadProperties('/properties'));
  const feature={type:'Feature',id:1,geometry:{type:'Point',coordinates:[-95.4,29.8]},properties:{title:'Property'}};
  globalThis.fetch=async()=>({ok:true,json:async()=>({type:'FeatureCollection',features:[feature,{...feature,id:2,geometry:{type:'Point',coordinates:[200,29]}}]})});
  const data=await loadProperties('/properties');assert.deepEqual(data.features,[feature]);
 }finally{globalThis.fetch=original;}
});

import {AREAS,defaultFilters,filterProperties,usableSf,propertyMetric} from '../src/filters.js';
const record=(id,type,transaction,metrics,coordinates=[-95.65,29.83])=>({type:'Feature',id,geometry:{type:'Point',coordinates},properties:{title:`Property ${id}`,propertyType:{slug:type},transactionType:{slug:transaction},metrics}});
const inventory=[
 record(1,'retail','for-lease',{availableSf:4361},[-95.16,30]),
 record(2,'industrial-flex','for-lease',{availableSf:11273,buildingSf:37309}),
 record(3,'office','for-sale',{availableSf:null,buildingSf:42716},[-95.642,29.7076]),
 record(4,'land','for-sale',{availableSf:null,buildingSf:null,lotAcres:2.21},[-95.7195,29.6773]),
];
const ids=(filters,data=inventory)=>filterProperties(data,{...defaultFilters(),...filters}).map(f=>f.id);
test('default lease, all transactions, exact dual listing and combined filters',()=>{
 assert.deepEqual(ids({}),[1,2]);assert.deepEqual(ids({transactionType:''}),[1,2,3,4]);
 assert.deepEqual(ids({propertyType:'industrial-flex',size:'large'}),[2]);
 assert.deepEqual(ids({propertyType:'office'}),[]);
 const dual=record(5,'office','for-sale-or-lease',{availableSf:6000});
 assert.deepEqual(ids({},[dual]),[5]);assert.deepEqual(ids({transactionType:'for-sale-or-lease'},[...inventory,dual]),[5]);
});
test('SF ranges have nonoverlapping boundaries; available SF wins and land acreage never matches',()=>{
 const sizes=[4999,5000,9999,10000,24999,25000,49999,50000].map((n,i)=>record(i,'office','for-lease',{availableSf:n}));
 for(const [size,expected] of [['small',[0]],['medium',[1,2]],['large',[3,4]],['larger',[5,6]],['largest',[7]]])assert.deepEqual(ids({size},sizes),expected);
 assert.deepEqual(ids({size:'larger',transactionType:''}),[3]);
 assert.equal(usableSf(inventory[1].properties),11273);
 assert.equal(usableSf(record(9,'land','for-lease',{buildingSf:12000,lotAcres:3}).properties),null);
 for(const v of [0,-1,Infinity,NaN,'5000'])assert.equal(usableSf(record(9,'office','for-lease',{availableSf:v,buildingSf:8000}).properties),null);
 assert.equal(propertyMetric(inventory[3].properties),'2.21 acres');
 assert.equal(propertyMetric(inventory[2].properties),'42,716 SF building');
});
test('approximate area boxes filter independently and combine with type and transaction',()=>{
 assert.deepEqual(ids({area:'west'}),[2]);assert.deepEqual(ids({area:'humble'}),[1]);
 assert.deepEqual(ids({area:'north'}),[1]);assert.deepEqual(ids({area:'southwest',transactionType:''}),[3,4]);
 assert.deepEqual(ids({area:'katy',transactionType:''}),[4]);
 assert.deepEqual(ids({area:'west',propertyType:'retail'}),[]);
 assert.equal(Object.keys(AREAS).length,5);
 const original=JSON.stringify(inventory);ids({area:'katy'});assert.equal(JSON.stringify(inventory),original);
});
test('property marker layers retain hit targets and support hover, selected and dimmed states',()=>{
 const layers=[],map={addSource(){},addLayer(layer){layers.push(layer);}};
 addPropertyLayers(map,{type:'FeatureCollection',features:inventory});
 const paint=layers.find(x=>x.id==='atlas-properties').paint;
 assert.ok(JSON.stringify(paint['circle-radius']).includes('hover'));
 assert.ok(JSON.stringify(paint['circle-opacity']).includes('dimmed'));
 assert.ok(JSON.stringify(layers[0].paint).includes('selected'));
});

import {focusData,localUrl} from '../src/focus-data.js';
const origin='http://localhost:8080';
test('dossier uses real nullable metrics, exact parking highlight and bounded local links',()=>{
 const p={...inventory[2].properties,title:'Presidio',propertyHighlights:['Class B','160 parking spaces','Built in 2014'],image:{url:origin+'/wp-content/uploads/presidio.webp',alt:''},permalink:origin+'/properties/presidio/',location:{city:'Houston',state:'TX',postalCode:'77083'}};
 const data=focusData({properties:p},origin);
 assert.deepEqual(data.metrics,[{label:'BUILDING',value:'42,716 SF'},{label:'PARKING SPACES',value:'160'}]);
 assert.deepEqual(data.highlights,['Class B','Built in 2014']);assert.equal(data.location,'Houston, TX 77083');
 assert.equal(data.image.alt,'Presidio');assert.equal(data.permalink,p.permalink);
 assert.equal(localUrl('javascript:alert(1)',origin),null);assert.equal(localUrl('https://external.example/a.jpg',origin),null);
 assert.equal(localUrl(undefined,origin),null);
});
test('dossier omits missing data and suppressed pricing, and rejects unavailable properties',()=>{
 const land=focusData(inventory[3],origin);
 assert.deepEqual(land.metrics,[{label:'SITE',value:'2.21 AC'}]);assert.equal(land.image,null);assert.equal(land.permalink,null);assert.deepEqual(land.highlights,[]);
 assert.equal(focusData(null,origin),null);assert.equal(focusData({properties:{title:''}},origin),null);
 const empty=focusData({properties:{title:'Incomplete',metrics:{availableSf:NaN,buildingSf:Infinity,lotAcres:-1},pricing:{salePrice:0,priceDisplay:null},propertyHighlights:[null,undefined,'',23]}},origin);
 assert.deepEqual(empty.metrics,[]);assert.deepEqual(empty.highlights,[]);
 const lease=focusData({properties:{title:'Lease',metrics:{availableSf:11273,buildingSf:37309},pricing:{leaseRateDisplay:'$9.75–$11.50/SF/YR'},propertyHighlights:['a','b','c','d','e','f']}},origin);
 assert.deepEqual(lease.metrics.map(m=>m.label),['AVAILABLE','LEASE RATE','BUILDING']);assert.equal(lease.highlights.length,5);
});

import {defaultInvestFilters,filterInvest,investMetric} from '../src/invest.js';
import {newOwner,newManagement,workflowValid,toggleNeed} from '../src/workflow-data.js';
test('Invest uses sale intent, building SF vs acres, and only reliable numeric prices',()=>{
 const result=f=>filterInvest(inventory,{...defaultInvestFilters(),...f}).map(p=>p.id);
 assert.deepEqual(result({}),[3,4]);assert.deepEqual(result({price:'under1'}),[]);
 assert.deepEqual(result({size:'larger'}),[3]);assert.deepEqual(result({propertyType:'land',size:'acMedium'}),[4]);
 assert.deepEqual(result({propertyType:'land',size:'larger'}),[]);
 assert.deepEqual(result({area:'katy'}),[4]);assert.equal(investMetric(inventory[3].properties),'2.21 AC');
 const prices=[999999,1000000,2499999,2500000,4999999,5000000,null,NaN,'800000',0].map((salePrice,id)=>({...inventory[2],id,properties:{...inventory[2].properties,pricing:{salePrice}}}));
 for(const [price,expected] of [['under1',[0]],['one',[1,2]],['two',[3,4]],['five',[5]]])assert.deepEqual(filterInvest(prices,{...defaultInvestFilters(),price}).map(f=>f.id),expected);
});
test('guided answers validate per step and land/building sizes stay separate',()=>{
 const owner=newOwner();assert.equal(workflowValid(owner),false);owner.locationText='Un-geocoded address';assert.equal(workflowValid(owner),true);
 owner.step=2;owner.propertyType='Land';assert.equal(workflowValid(owner),true);
 owner.step=3;owner.sizeRange='Under 5,000 SF';assert.equal(workflowValid(owner),false);owner.sizeRange='2–5 acres';assert.equal(workflowValid(owner),true);
 owner.step=4;owner.intent='Both';assert.equal(workflowValid(owner),true);
 assert.notStrictEqual(newOwner(),newOwner());assert.deepEqual(newManagement().needs,[]);
});
test('management multi-select handles Not sure exclusively',()=>{
 let needs=toggleNeed([],'Tenant relations');needs=toggleNeed(needs,'Financial oversight');assert.equal(needs.length,2);
 needs=toggleNeed(needs,'Not sure yet');assert.deepEqual(needs,['Not sure yet']);needs=toggleNeed(needs,'Day-to-day management');assert.deepEqual(needs,['Day-to-day management']);
 const management={...newManagement(),step:4,needs};assert.equal(workflowValid(management,true),true);
});

import {schema,prefillBrief,normalizeBrief,matchBrief,stepsFor,stepComplete,summaryRows,priorityOptions,toggleChoice} from '../src/brief-data.js';
const atlasState=(mode,filters={})=>({mode,filters:{...defaultFilters(),...filters},ownerDisposition:{...newOwner(),locationText:'Owner address',areaPreset:'west',propertyType:'Industrial / Flex',sizeRange:'5,000–25,000 SF',intent:'Both'},management:{...newManagement(),locationText:'Management address',propertyType:'Mixed Use',sizeRange:'25,000–100,000 SF',needs:['Financial oversight']}});
const leaseBrief=()=>({...prefillBrief(atlasState('find-space',{propertyType:'industrial-flex',size:'large',area:'west'})),timing:'3_6_months',priorities:['loading','parking']});
test('Brief prefill preserves all four journeys without coordinates or mutating source state',()=>{
 const state=atlasState('find-space',{propertyType:'industrial-flex',transactionType:'for-sale-or-lease',size:'largest',area:'west'}),b=prefillBrief(state);
 assert.equal(b.goal,'lease_space');assert.equal(b.transaction,'for-sale-or-lease');assert.equal(b.size,'largest');assert.equal(schema.sizes[b.size].label,'50,000+ SF');assert.equal(b.location.areaPreset,'west');
 assert.deepEqual(stepsFor(b).filter(s=>!stepComplete(b,s)),['timing','priorities']);b.propertyTypes.push('retail');assert.equal(state.filters.propertyType,'industrial-flex');
 const invest=prefillBrief(atlasState('invest',{propertyType:'land',size:'acMedium',price:'two',area:'katy'}));assert.equal(invest.goal,'invest');assert.equal(invest.budget,'two');assert.equal(invest.transaction,'for-sale');assert.equal(invest.size,'acMedium');
 const owner=prefillBrief(atlasState('owner-disposition'));assert.equal(owner.goal,'lease_or_sell');assert.equal(owner.ownerIntent,'both');assert.equal(owner.size,'owner_medium');assert.equal(owner.location.text,'Owner address');assert.deepEqual(Object.keys(owner.location),['text','areaPreset']);
 const management=prefillBrief(atlasState('manage-asset'));assert.equal(management.size,'owner_large');assert.deepEqual(management.managementNeeds,['financial']);assert.deepEqual(stepsFor(management).filter(s=>!stepComplete(management,s)),['timing']);
});
test('Brief conditional normalization clears irrelevant values and separates SF from acreage',()=>{
 const b=leaseBrief();b.propertyTypes=['land'];assert.equal(normalizeBrief(b).size,'');
 b.goal='manage_asset';b.budget='five';b.ownerIntent='sell';const m=normalizeBrief(b);assert.deepEqual(m.priorities,[]);assert.equal(m.budget,'any');assert.equal(m.ownerIntent,null);assert.equal(m.transaction,'');
 assert.ok(!priorityOptions({...b,goal:'lease_space',propertyTypes:['office']}).includes('loading'));
 assert.deepEqual(toggleChoice(['parking'],'unsure'),['unsure']);assert.deepEqual(toggleChoice(['unsure'],'parking'),['parking']);
 assert.ok(summaryRows(leaseBrief()).every(row=>typeof row[2]==='string'&&!row[2].includes('lease_space')));
});
test('Brief matching requires every chosen criterion, ignores text/timing/priorities, and ranks exact transactions first',()=>{
 const b=leaseBrief();assert.deepEqual(matchBrief(inventory,b).map(f=>f.id),[2]);b.location.text='An address without geocoding';b.timing='immediately';assert.deepEqual(matchBrief(inventory,b).map(f=>f.id),[2]);
 b.location.areaPreset='humble';assert.deepEqual(matchBrief(inventory,b),[]);b.location.areaPreset='west';
 const dual={...inventory[1],id:0,properties:{...inventory[1].properties,transactionType:{slug:'for-sale-or-lease'}}};assert.deepEqual(matchBrief([dual,inventory[1]],b).map(f=>f.id),[2,0]);
 b.transaction='for-sale-or-lease';assert.deepEqual(matchBrief([dual,inventory[1]],b).map(f=>f.id),[0]);
 assert.deepEqual(matchBrief(inventory,prefillBrief(atlasState('owner-disposition'))),[]);
});
test('Brief investment matching respects half-open price/SF/acre ranges and missing suppressed prices',()=>{
 const b=prefillBrief(atlasState('invest',{propertyType:'land',size:'acMedium',area:'katy'}));assert.deepEqual(matchBrief(inventory,b).map(f=>f.id),[4]);b.budget='two';assert.deepEqual(matchBrief(inventory,b),[]);
 const prices=[2499999,2500000,4999999,5000000,null,NaN,'3000000',0].map((salePrice,id)=>({...inventory[3],id,properties:{...inventory[3].properties,pricing:{salePrice}}}));assert.deepEqual(matchBrief(prices,b).map(f=>f.id),[1,2]);
 const sf={...leaseBrief(),size:'medium'};const sizes=[4999,5000,9999,10000,null].map((availableSf,id)=>({...inventory[1],id,properties:{...inventory[1].properties,metrics:{availableSf}}}));assert.deepEqual(matchBrief(sizes,sf).map(f=>f.id),[1,2]);
});
