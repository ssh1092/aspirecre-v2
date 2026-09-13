export const heroIntents={
 'find-space':{goal:'lease_space',question:'What kind of space do you need?',placeholder:'10,000–20,000 SF industrial space in West Houston'},
 'owner-disposition':{goal:'lease_or_sell',question:'Tell us about your property',placeholder:'Retail center in Katy with upcoming vacancy'},
 invest:{goal:'invest',question:'What kind of opportunity are you looking for?',placeholder:'Retail investment opportunity in Katy or Sugar Land'},
 'manage-asset':{goal:'manage_asset',question:'What do you need help managing?',placeholder:'Office property in Houston needing leasing and management support'},
};

// Ordering is a transaction signal, never a claim of suitability or a filter.
export function prioritizeProperties(features,intent,selected){
 const transaction=intent==='find-space'?'for-lease':intent==='invest'?'for-sale':null;
 const rank=f=>f.id===selected?4:transaction&&[transaction,'for-sale-or-lease'].includes(f.properties.transactionType?.slug)?2:0;
 return [...features].sort((a,b)=>rank(b)-rank(a)||a.id-b.id);
}

const overlaps=(a,b,gap=12)=>a.x<b.x+b.width+gap&&a.x+a.width+gap>b.x&&a.y<b.y+b.height+gap&&a.y+a.height+gap>b.y;
// Projected coordinates stay fixed. Only the photographic labels are displaced.
// If no readable position fits, keep the real map point as the accessible fallback.
export function placePropertyCards(items,bounds,width=208,reserved=[]){
 const placed=[...reserved];
 return items.map(item=>{
  const height=item.height,point=item.point;
  if(!Number.isFinite(point.x)||!Number.isFinite(point.y)||point.x<bounds.left||point.x>bounds.right||point.y<bounds.top||point.y>bounds.bottom)return {...item,box:null};
  const candidates=[];
  for(const gap of [18,70,135])for(const [dx,dy] of [[18,-height/2],[-width-18,-height/2],[-width/2,-height-gap],[-width/2,gap],[gap,-height-gap],[-width-gap,-height-gap],[gap,gap],[-width-gap,gap]]){
   const box={x:Math.max(bounds.left,Math.min(bounds.right-width,point.x+dx)),y:Math.max(bounds.top,Math.min(bounds.bottom-height,point.y+dy)),width,height};
   if(box.x>=bounds.left&&box.y>=bounds.top&&box.x+width<=bounds.right&&box.y+height<=bounds.bottom&&!(point.x>=box.x-8&&point.x<=box.x+width+8&&point.y>=box.y-8&&point.y<=box.y+height+8)&&!placed.some(p=>overlaps(box,p)))candidates.push(box);
  }
  candidates.sort((a,b)=>Math.hypot(a.x+width/2-point.x,a.y+height/2-point.y)-Math.hypot(b.x+width/2-point.x,b.y+height/2-point.y));
  const box=candidates[0]??null;if(box)placed.push(box);
  return {...item,box};
 }
 );
}
