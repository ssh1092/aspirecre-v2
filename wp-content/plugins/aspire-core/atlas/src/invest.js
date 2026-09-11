import {AREAS,SIZES} from './filters.js';
export const isDiscovery=mode=>mode==='find-space'||mode==='invest';
export const isGuided=mode=>mode==='owner-disposition'||mode==='manage-asset';
export const defaultInvestFilters=()=>({propertyType:'',transactionType:'for-sale',price:'',size:'',area:''});
export const PRICE_RANGES={under1:[0,1000000],one:[1000000,2500000],two:[2500000,5000000],five:[5000000,Infinity]};
export const ACRE_RANGES={acSmall:[0,2],acMedium:[2,5],acLarge:[5,20],acLargest:[20,Infinity]};
export const SF_OPTIONS=[['','Any Size'],['small','Under 5,000 SF'],['medium','5,000–10,000 SF'],['large','10,000–25,000 SF'],['larger','25,000–50,000 SF'],['largest','50,000+ SF']];
export const ACRE_OPTIONS=[['','Any Acreage'],['acSmall','Under 2 acres'],['acMedium','2–5 acres'],['acLarge','5–20 acres'],['acLargest','20+ acres']];
const positive=n=>Number.isFinite(n)&&n>0;
export function filterInvest(features,filters){
 return features.filter(({properties:p,geometry})=>{
  if(!['for-sale','for-sale-or-lease'].includes(p.transactionType?.slug))return false;
  if(filters.propertyType && p.propertyType?.slug!==filters.propertyType)return false;
  const price=PRICE_RANGES[filters.price],amount=p.pricing?.salePrice;
  if(price && (!positive(amount)||amount<price[0]||amount>=price[1]))return false;
  const land=p.propertyType?.slug==='land',acres=!!ACRE_RANGES[filters.size],range=ACRE_RANGES[filters.size]??SIZES[filters.size];
  const size=acres?p.metrics?.lotAcres:p.metrics?.buildingSf;
  if(range && (land!==acres || !positive(size)||size<range[0]||size>=range[1]))return false;
  const box=AREAS[filters.area]?.bounds,[x,y]=geometry.coordinates;
  return !box||(x>=box[0][0]&&x<=box[1][0]&&y>=box[0][1]&&y<=box[1][1]);
 });
}
export function investMetric(p){
 const m=p.metrics??{},building=p.propertyType?.slug!=='land'&&positive(m.buildingSf),n=building?m.buildingSf:m.lotAcres;
 const metric=positive(n)?`${n.toLocaleString('en-US',{maximumFractionDigits:2})} ${building?'SF':'AC'}`:'';
 const price=p.pricing?.salePrice;
 const priceLabel=positive(price)?new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',maximumFractionDigits:0}).format(price):'';
 return [metric,priceLabel].filter(Boolean).join(' · ')||'Contact for details';
}
