import test from 'node:test';
import assert from 'node:assert/strict';
import {heroIntents,prioritizeProperties,placePropertyCards} from '../src/hero-data.js';
import {prefillBrief,prefillHeroBrief} from '../src/brief-data.js';
import {defaultFilters,propertyMetric} from '../src/filters.js';
import {newOwner,newManagement} from '../src/workflow-data.js';

const inventory=['for-sale','for-lease','for-sale-or-lease','for-lease'].map((slug,id)=>({id:id+1,properties:{transactionType:{slug}}}));
test('Hero transaction ordering retains every real opportunity and respects selection',()=>{
 const before=structuredClone(inventory);
 assert.deepEqual(prioritizeProperties(inventory,'find-space').map(f=>f.id),[2,3,4,1]);
 assert.deepEqual(prioritizeProperties(inventory,'invest').map(f=>f.id),[1,3,2,4]);
 assert.deepEqual(prioritizeProperties(inventory,'find-space',1).map(f=>f.id),[1,2,3,4]);
 for(const intent of ['owner-disposition','manage-asset'])assert.deepEqual(prioritizeProperties(inventory,intent),inventory);
 assert.deepEqual(inventory,before);
});
test('Every hero objective seeds the existing brief and preserves words without guessing facts',()=>{
 const state={mode:'explore',filters:defaultFilters(),ownerDisposition:newOwner(),management:newManagement()},before=structuredClone(state);
 for(const content of Object.values(heroIntents)){
  const seed=prefillHeroBrief(state,{goal:content.goal,need:content.placeholder});
  assert.equal(seed.goal,content.goal);assert.equal(seed.location.text,content.placeholder);
  assert.deepEqual(Object.keys(seed),Object.keys(prefillBrief(state)));
  assert.deepEqual(seed.propertyTypes,[]);assert.equal(seed.size,'');
 }
 assert.deepEqual(state,before);
 assert.equal(prefillHeroBrief(state,{goal:'lease_space',need:'x'.repeat(250)}).location.text.length,240);
});
test('Collision layout is deterministic, stays within the map and keeps geographic anchors clear',()=>{
 const bounds={left:600,top:130,right:1400,bottom:850};
 const items=[{id:1,point:{x:1160,y:300},height:240},{id:2,point:{x:780,y:480},height:210},{id:3,point:{x:900,y:650},height:245},{id:4,point:{x:790,y:675},height:210}];
 const result=placePropertyCards(items,bounds),boxes=result.filter(i=>i.box);
 assert.ok(boxes.length>=3);assert.deepEqual(result,placePropertyCards(items,bounds));
 for(const {point,box:b} of boxes){
  assert.ok(b.x>=bounds.left&&b.x+b.width<=bounds.right&&b.y>=bounds.top&&b.y+b.height<=bounds.bottom);
  assert.ok(!(point.x>=b.x&&point.x<=b.x+b.width&&point.y>=b.y&&point.y<=b.y+b.height));
 }
 for(let a=0;a<boxes.length;a++)for(let b=a+1;b<boxes.length;b++){
  const x=boxes[a].box,y=boxes[b].box;
  assert.ok(x.x+x.width<=y.x||y.x+y.width<=x.x||x.y+x.height<=y.y||y.y+y.height<=x.y);
 }
});
test('Impossible collisions and offscreen points retain a marker instead of stacking cards',()=>{
 const result=placePropertyCards([{id:1,point:{x:50,y:50},height:230},{id:2,point:{x:NaN,y:40},height:230}],{left:0,top:0,right:120,bottom:100});
 assert.ok(result.every(p=>p.box===null));
});
test('Map-card metric does not read pricing, including suppressed FM1093 values',()=>{
 assert.equal(propertyMetric({metrics:{lotAcres:2.21},pricing:{salePrice:2232000,priceDisplay:'Protected'}}),'2.21 acres');
});
