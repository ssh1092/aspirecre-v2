import schema from '../brief-schema.json' with {type:'json'};
import {usableSf} from './filters.js';
export {schema};
export const isMatching=b=>['lease_space','buy_property','invest'].includes(b.goal);
export const isInvestment=b=>['buy_property','invest'].includes(b.goal);
export const isOwner=b=>['lease_property','sell_property','lease_or_sell'].includes(b.goal);
export const isLand=b=>b.propertyTypes.length===1&&b.propertyTypes[0]==='land';
export const priorityOptions=b=>(isOwner(b)?schema.priorityGroups.owner:schema.priorityGroups.seeker).filter(k=>!b.propertyTypes.length||!b.propertyTypes.every(t=>schema.irrelevantPriorities[t]?.includes(k)));
const lookup=(table,label)=>Object.keys(table).find(k=>(table[k].label??table[k])===label)??'';
export function prefillBrief(state){
 const guided=['owner-disposition','manage-asset'].includes(state.mode),management=state.mode==='manage-asset',f=state.filters,flow=management?state.management:state.ownerDisposition;
 const goal=management?'manage_asset':guided?({'Lease it':'lease_property','Sell it':'sell_property',Both:'lease_or_sell','Not sure yet':'lease_or_sell'}[flow.intent]):state.mode==='invest'?'invest':'lease_space';
 const b={goal,propertyTypes:guided?[lookup(schema.propertyTypes,flow.propertyType)]:f.propertyType?[f.propertyType]:[],location:{text:guided?flow.locationText:'',areaPreset:guided?flow.areaPreset:f.area},size:guided?lookup(schema.sizes,flow.sizeRange):f.size||'',budget:state.mode==='invest'?f.price||'any':'any',transaction:guided?'':f.transactionType,timing:'',priorities:[],managementNeeds:management?flow.needs.map(n=>lookup(schema.managementNeeds,n)):[],ownerIntent:guided&&!management?lookup(schema.ownerIntents,flow.intent):null};
 if(isLand(b)&&schema.sizes[b.size]?.unit==='sf')b.size='';
 return normalizeBrief(b);
}
export function normalizeBrief(b){
 b=structuredClone(b);
 if(b.goal==='lease_space')b.propertyTypes=b.propertyTypes.filter(t=>t!=='mixed-use');
 if(!isInvestment(b))b.budget='any';
 if(!isOwner(b))b.ownerIntent=null;
 else b.ownerIntent=b.goal==='lease_property'?'lease':b.goal==='sell_property'?'sell':b.ownerIntent==='unsure'?'unsure':'both';
 if(b.goal!=='manage_asset')b.managementNeeds=[];
 if(b.goal==='manage_asset')b.priorities=[];
 else b.priorities=b.priorities.filter(p=>priorityOptions(b).includes(p));
 if(b.goal!=='lease_space')b.transaction=isInvestment(b)?'for-sale':b.goal==='lease_property'?'for-lease':b.goal==='sell_property'?'for-sale':b.goal==='lease_or_sell'?'for-sale-or-lease':'';
 if(schema.sizes[b.size]?.unit && schema.sizes[b.size].unit!==(isLand(b)?'acres':'sf'))b.size='';
 return b;
}
export const stepsFor=b=>['goal','propertyTypes','location','size','timing',b.goal==='manage_asset'?'managementNeeds':'priorities'];
export function stepComplete(b,step){
 if(step==='size')return !!schema.sizes[b.size];
 if(step==='location')return true; // Flexible Houston is a valid carried-forward answer.
 if(step==='propertyTypes'||step==='priorities'||step==='managementNeeds')return b[step].length>0;
 return !!b[step];
}
export function summaryRows(b){
 return [
 ['goal','Goal',schema.goals[b.goal]],
 ['propertyTypes','Property type',b.propertyTypes.map(t=>schema.propertyTypes[t]).join(', ')],
 ['location','Area',[b.location.text,schema.areas[b.location.areaPreset]?.label||'All Houston / flexible'].filter(Boolean).join(' · ')],
 ['size','Approximate size',schema.sizes[b.size]?.label||'Not specified'],
 ...(isInvestment(b)?[['size','Budget',schema.budgets[b.budget].label]]:[]),
 ...(b.goal==='lease_space'?[['goal','Transaction',{'':'All Transactions','for-lease':'For Lease','for-sale-or-lease':'For Sale or Lease'}[b.transaction]]]:[]),
 ...(isOwner(b)?[['goal','Owner intent',schema.ownerIntents[b.ownerIntent]]]:[]),
 ['timing','Timing',schema.timing[b.timing]],
 [b.goal==='manage_asset'?'managementNeeds':'priorities',b.goal==='manage_asset'?'Management needs':'Priorities',(b.goal==='manage_asset'?b.managementNeeds:b.priorities).map(k=>(b.goal==='manage_asset'?schema.managementNeeds:schema.priorities)[k]).join(', ')],
 ];
}
const inRange=(v,r)=>!r||(Number.isFinite(v)&&v>0&&v>=r[0]&&(r[1]===null||v<r[1]));
export function matchBrief(features,b){
 if(!isMatching(b))return [];
 const range=schema.sizes[b.size]?.range,budget=isInvestment(b)?schema.budgets[b.budget]?.range:null,box=schema.areas[b.location.areaPreset]?.bounds;
 return features.flatMap(f=>{
  const p=f.properties,t=p.transactionType?.slug,transaction=b.transaction;
  if(transaction&&t!==transaction&&!(transaction!=='for-sale-or-lease'&&t==='for-sale-or-lease'))return [];
  if(b.propertyTypes.length&&!b.propertyTypes.includes(p.propertyType?.slug))return [];
  const [x,y]=f.geometry.coordinates;
  if(box&&!(x>=box[0][0]&&x<=box[1][0]&&y>=box[0][1]&&y<=box[1][1]))return [];
  const value=isLand(b)?p.metrics?.lotAcres:isInvestment(b)?p.metrics?.buildingSf:usableSf(p);
  if(!inRange(value,range)||!inRange(p.pricing?.salePrice,budget))return [];
  // All chosen criteria must pass. Exact transaction outranks a dual listing; ties use stable ID order.
  return [{feature:f,strength:transaction&&t===transaction?2:1}];
 }).sort((a,c)=>c.strength-a.strength||a.feature.id-c.feature.id).map(m=>m.feature);
}
export function toggleChoice(values,key){return values.includes(key)?values.filter(v=>v!==key):key==='unsure'?[key]:[...values.filter(v=>v!=='unsure'),key];}
