import {test} from 'node:test';
import assert from 'node:assert/strict';
import {atlasStyle} from '../src/style.js';
import {loadProperties} from '../src/properties.js';
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
