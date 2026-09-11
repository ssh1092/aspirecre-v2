import {test} from 'node:test';
import assert from 'node:assert/strict';
import {atlasStyle} from '../src/style.js';
import {loadProperties,addPropertyLayers} from '../src/properties.js';
import {validateStyleMin} from '@maplibre/maplibre-gl-style-spec';
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
import {newOwner,newManagement,workflowValid,toggleNeed,prepareBrief} from '../src/workflow-data.js';
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
 const prepared=prepareBrief(owner,'owner-disposition');assert.equal(prepared.status,'prepared');assert.equal(prepared.details.locationText,'Un-geocoded address');assert.ok(!('coordinates' in prepared.details));
 assert.notStrictEqual(newOwner(),newOwner());assert.deepEqual(newManagement().needs,[]);
});
test('management multi-select handles Not sure exclusively and prepared data is detached',()=>{
 let needs=toggleNeed([],'Tenant relations');needs=toggleNeed(needs,'Financial oversight');assert.equal(needs.length,2);
 needs=toggleNeed(needs,'Not sure yet');assert.deepEqual(needs,['Not sure yet']);needs=toggleNeed(needs,'Day-to-day management');assert.deepEqual(needs,['Day-to-day management']);
 const management={...newManagement(),step:4,needs};assert.equal(workflowValid(management,true),true);
 const prepared=prepareBrief(management,'manage-asset');management.needs.push('Leasing coordination');assert.deepEqual(prepared.details.needs,['Day-to-day management']);
});
