import {AREAS} from './filters.js';
import {isGuided} from './invest.js';
import {ownerTypes,managementTypes,buildingSizes,landSizes,ownerIntents,managementNeeds,workflowValid,toggleNeed,prepareBrief} from './workflow-data.js';
import './guided.css';
export function createGuided(root,state,onChange,onExplore){
 const panel=root.querySelector('.atlas-guided'),stage=panel.querySelector('.atlas-guided-step');
 const heading=panel.querySelector('h2'),support=panel.querySelector('.atlas-guided-support'),error=panel.querySelector('.atlas-guided-error');
 const back=panel.querySelector('.atlas-guided-back'),next=panel.querySelector('.atlas-guided-next'),status=panel.querySelector('.atlas-guided-status');
 const flow=()=>state.mode==='manage-asset'?state.management:state.ownerDisposition;
 const node=(tag,text,className)=>{const e=document.createElement(tag);if(text)e.textContent=text;if(className)e.className=className;return e;};
 function choices(legend,options,key,multiple=false){
  const group=node('fieldset'),title=node('legend',legend);group.append(title);
  options.forEach(value=>{
   const label=node('label',null,'atlas-guided-choice'),input=node('input');input.type=multiple?'checkbox':'radio';input.name=`atlas-${key}-${heading.id}`;input.value=value;
   input.checked=multiple?flow()[key].includes(value):flow()[key]===value;
   input.addEventListener('change',()=>{
    if(multiple){flow()[key]=toggleNeed(flow()[key],value);group.querySelectorAll('input').forEach(i=>i.checked=flow()[key].includes(i.value));}
    else {if(key==='propertyType' && (flow().propertyType==='Land')!==(value==='Land'))flow().sizeRange='';flow()[key]=value;}
    error.hidden=true;
   });
   label.append(input,node('span',value));group.append(label);
  });stage.append(group);
 }
 function review(){
  const f=flow(),list=node('dl',null,'atlas-guided-review');
  const entries=[['Location',[f.locationText,AREAS[f.areaPreset]?.label].filter(Boolean).join(' · ')],['Property type',f.propertyType],['Approximate size',f.sizeRange],[state.mode==='manage-asset'?'Management needs':'Considering',state.mode==='manage-asset'?f.needs.join(', '):f.intent==='Both'?'Lease or Sell':f.intent]];
  entries.forEach(([label,value])=>{const div=node('div');div.append(node('dt',label),node('dd',value));list.append(div);});stage.append(list);
 }
 function render(focus=true){
  if(!isGuided(state.mode)){panel.hidden=true;stage.replaceChildren();heading.textContent='';support.textContent='';status.textContent='';delete root.dataset.workflowStep;return;}
  panel.hidden=false;error.hidden=true;const f=flow(),management=state.mode==='manage-asset';
  stage.replaceChildren();
  panel.querySelector('.atlas-guided-eyebrow').textContent=`${management?'MANAGE AN ASSET':'LEASE / SELL MY PROPERTY'} · ${f.step<5?`STEP ${f.step} OF 4`:f.step===5?'REVIEW':'DETAILS PREPARED'}`;
  heading.textContent=f.step===1?(management?'Tell us about the property.':'Where is your property?'):f.step===2?'What type of property?':f.step===3?'Approximate size':f.step===4?(management?'What do you need help with?':"What are you considering?"):f.step===5?(management?'YOUR PROPERTY MANAGEMENT NEED':'YOUR PROPERTY'):'Your property details are ready.';
  support.textContent=f.step===1?(management?'Start with the location of your property.':"Start with the location, then tell us what you're considering."):f.step===6?'Review your details or talk to Aspire about your next move.':'';support.hidden=!support.textContent;
  if(f.step===1){
   const group=node('fieldset');group.append(node('legend','Location'));
   const address=node('label','Address or area'),input=node('input');input.type='text';input.maxLength=240;input.autocomplete='off';input.value=f.locationText;input.placeholder='Enter an address or area';input.addEventListener('input',()=>{f.locationText=input.value;error.hidden=true;});address.append(input);
   const area=node('label','Choose an area'),select=node('select');select.add(new Option('Choose an area',''));for(const [id,preset] of Object.entries(AREAS))select.add(new Option(preset.label,id));select.value=f.areaPreset;
   select.addEventListener('change',()=>{f.areaPreset=select.value;error.hidden=true;onChange('workflow-area');});area.append(select);group.append(address,node('p','Or choose an approximate Houston area.','atlas-guided-hint'),area);stage.append(group);
  }else if(f.step===2)choices('Property Type',management?managementTypes:ownerTypes,'propertyType');
  else if(f.step===3)choices('Approximate Size',f.propertyType==='Land'?landSizes:buildingSizes,'sizeRange');
  else if(f.step===4)choices(management?'Select all that apply':'Your plans',management?managementNeeds:ownerIntents,management?'needs':'intent',management);
  else review();
  back.hidden=f.step===1;back.textContent=f.step===6?'Review details':'Back';
  next.hidden=f.step===6;next.textContent=f.step===5?'BUILD MY CRE BRIEF →':'Continue →';
  status.textContent=f.step<5?`Step ${f.step} of 4. ${heading.textContent}`:heading.textContent;
  root.dataset.workflowStep=String(f.step);onChange('workflow-step');
  if(focus)heading.focus();
 }
 next.addEventListener('click',()=>{
  const f=flow();if(!workflowValid(f,state.mode==='manage-asset')){error.textContent=f.step===1?'Enter a location or choose an area to continue.':'Choose an option to continue.';error.hidden=false;stage.querySelector('input,select')?.focus();return;}
  if(f.step===5)state.briefPreparation=prepareBrief(f,state.mode);
  f.step=Math.min(6,f.step+1);render();
 });
 back.addEventListener('click',()=>{flow().step=Math.max(1,flow().step-1);state.briefPreparation=null;render();});
 panel.querySelector('.atlas-guided-explore').addEventListener('click',onExplore);
 return {panel,heading,render};
}
