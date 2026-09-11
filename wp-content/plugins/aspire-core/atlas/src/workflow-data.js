export const ownerTypes=['Retail','Office','Industrial / Flex','Land','Other'];
export const managementTypes=['Retail','Office','Industrial / Flex','Mixed Use','Other'];
export const buildingSizes=['Under 5,000 SF','5,000–25,000 SF','25,000–100,000 SF','100,000+ SF','Not sure'];
export const landSizes=['Under 2 acres','2–5 acres','5–20 acres','20+ acres','Not sure'];
export const ownerIntents=['Lease it','Sell it','Both','Not sure yet'];
export const managementNeeds=['Day-to-day management','Tenant relations','Operations / vendors','Financial oversight','Leasing coordination','Not sure yet'];
export const newOwner=()=>({step:1,locationText:'',areaPreset:'',propertyType:'',sizeRange:'',intent:''});
export const newManagement=()=>({step:1,locationText:'',areaPreset:'',propertyType:'',sizeRange:'',needs:[]});
export function workflowValid(flow,management=false){
 if(flow.step===1)return !!(flow.locationText.trim()||flow.areaPreset);
 if(flow.step===2)return (management?managementTypes:ownerTypes).includes(flow.propertyType);
 if(flow.step===3)return (flow.propertyType==='Land'?landSizes:buildingSizes).includes(flow.sizeRange);
 if(flow.step===4)return management?flow.needs.length>0&&flow.needs.every(n=>managementNeeds.includes(n)):ownerIntents.includes(flow.intent);
 return true;
}
export function toggleNeed(needs,value){
 if(needs.includes(value))return needs.filter(n=>n!==value);
 return value==='Not sure yet'?[value]:[...needs.filter(n=>n!=='Not sure yet'),value];
}
