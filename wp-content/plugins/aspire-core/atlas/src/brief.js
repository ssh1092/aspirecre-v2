import {schema,prefillBrief,normalizeBrief,stepsFor,stepComplete,summaryRows,matchBrief,isMatching,isInvestment,isLand,isOwner,priorityOptions,toggleChoice} from './brief-data.js';
import {propertyMetric} from './filters.js';
import {investMetric} from './invest.js';
import {localUrl} from './focus-data.js';
import './brief.css';
const titles={goal:'What are you looking to accomplish?',propertyTypes:'What type of property?',location:'Where in Houston?',size:'What scale fits your plans?',timing:'When are you looking to make a move?',priorities:'What matters most?',managementNeeds:'What do you need help with?'};
const labels={goal:'Goal',propertyTypes:'Property type',location:'Area',size:'Size / budget',timing:'Timing',priorities:'Priorities',managementNeeds:'Management needs'};
const node=(tag,text,cls)=>{const e=document.createElement(tag);if(text)e.textContent=text;if(cls)e.className=cls;return e;};
const button=(text,fn,cls)=>{const e=node('button',text,cls);e.type='button';e.addEventListener('click',fn);return e;};
export function createBrief(root,state,{getFeatures,getDataState,change,close,select,explore}){
 const panel=root.querySelector('.atlas-cre-brief'),heading=panel.querySelector('h2'),stage=panel.querySelector('.atlas-brief-stage'),nav=panel.querySelector('nav'),back=panel.querySelector('.atlas-brief-back'),next=panel.querySelector('.atlas-brief-next'),status=panel.querySelector('[role=status]');
 const drafts=new Map();let contact={name:'',email:'',phone:'',company:'',consent:false,website:''},submitting=false,revision=0;
 const b=()=>state.brief.data,steps=()=>stepsFor(b());
 function announce(text){status.textContent=text;}
 function update(){state.brief.data=normalizeBrief(b());change('brief-change');}
 function go(screen,step){state.brief.screen=screen;if(step)state.brief.step=step;render();change(screen==='review'?'brief-review':'brief-step');}
 function edit(step){state.brief.submitted=false;state.brief.editing=state.brief.screen==='review';go('questions',step);}
 function choices(legend,options,key,multiple=false){
  const field=node('fieldset'),title=node('legend',legend);field.append(title);field.dataset.field=key;
  Object.entries(options).forEach(([value,text])=>{
   const label=node('label',null,'atlas-brief-choice'),input=node('input');input.type=multiple?'checkbox':'radio';input.name=`${heading.id}-${key}`;input.value=value;input.checked=multiple?b()[key].includes(value):b()[key]===value;
   input.addEventListener('change',()=>{if(key==='goal'&&value==='lease_space'&&b().goal!=='lease_space')b().transaction='for-lease';b()[key]=multiple?toggleChoice(b()[key],value):value;update();if(['goal','propertyTypes'].includes(key))render(false);else field.querySelectorAll('input').forEach(i=>i.checked=multiple?b()[key].includes(i.value):b()[key]===i.value);});
   label.append(input,node('span',text));field.append(label);
  });stage.append(field);return field;
 }
 function review(){
  const dl=node('dl',null,'atlas-brief-summary');
  summaryRows(b()).forEach(([key,label,value])=>{const row=node('div');row.append(node('dt',label),node('dd',value||'Not specified'),button('Edit',()=>edit(key),'atlas-brief-edit'));row.lastChild.setAttribute('aria-label',`Edit ${label.toLowerCase()}`);dl.append(row);});stage.append(dl);
  const matches=matchBrief(getFeatures(),b());state.brief.matches=matches;
  if(isMatching(b())){
   const group=node('section',null,'atlas-brief-matches');
   const dataState=getDataState();if(!dataState.loaded){group.append(node('p',dataState.error?'Aspire opportunities could not load. Your brief can still be sent to Aspire.':'Loading Aspire opportunities…'));stage.append(group);return;}
   group.id=`${heading.id}-matches`;
   group.append(node('h3',matches.length?`${matches.length} Aspire ${matches.length===1?'opportunity matches':'opportunities match'} your brief`:'No current Aspire listings match every part of your brief.'));
   group.append(node('p',matches.length?'Matches meet your selected type, transaction, approximate area, size and budget where specified. Exact transaction matches appear first.':'Aspire may know of opportunities beyond the properties currently shown here.'));
   group.append(node('p','Free-text locations, timing and priorities are for Aspire’s review; they are not verified listing filters.','atlas-brief-hint'));
   if(isInvestment(b()))group.append(node('p','A budget filter uses only published numeric prices. Listings without a reliable price cannot match a specified budget.','atlas-brief-hint'));
   matches.slice(0,3).forEach(f=>{
    const p=f.properties,card=node('article',null,'atlas-brief-match');
    if(localUrl(p.image?.url,location.origin)){const img=node('img');img.src=p.image.url;img.alt=p.image.alt||p.displayTitle||p.title;img.loading='lazy';img.addEventListener('error',()=>img.remove(),{once:true});card.append(img);}
    const body=node('div');body.append(node('p',[p.propertyType?.label,p.transactionType?.label].filter(Boolean).join(' · '),'atlas-brief-hint'),node('h4',p.displayTitle||p.title),node('p',isInvestment(b())?investMetric(p):propertyMetric(p)),node('p',[p.location?.city,p.location?.state].filter(Boolean).join(', '),'atlas-brief-hint'),button('EXPLORE PROPERTY →',e=>select(f.id),'atlas-brief-property'));
    card.append(body);group.append(card);
   });stage.append(group);
  }
 }
 function textInput(parent,labelText,key,type='text',max=120){
  const wrap=node('div',null,'atlas-brief-field'),label=node('label',labelText),input=node('input'),error=node('p',null,'atlas-brief-error');
  input.id=`${heading.id}-${key}`;label.htmlFor=input.id;input.name=key;input.type=type;input.maxLength=max;input.value=contact[key];input.autocomplete={name:'name',email:'email',phone:'tel',company:'organization',website:'off'}[key];
  input.required=['name','email'].includes(key);input.setAttribute('aria-describedby',`${input.id}-error`);error.id=`${input.id}-error`;error.hidden=true;
  input.addEventListener('input',()=>{contact[key]=input.value;input.removeAttribute('aria-invalid');error.hidden=true;});wrap.append(label,input,error);parent.append(wrap);return input;
 }
 function contactForm(){
  const form=node('form');form.noValidate=true;form.setAttribute('aria-label','Send your brief');
  stage.append(node('p','Your brief is ready. Share your contact details to send it to Aspire.'));
  textInput(form,'Name', 'name');textInput(form,'Email','email','email',254);textInput(form,'Phone (optional)','phone','tel',50);textInput(form,'Company (optional)','company');
  const consent=node('label',null,'atlas-brief-choice'),check=node('input');check.type='checkbox';check.name='consent';check.checked=contact.consent;check.addEventListener('change',()=>contact.consent=check.checked);consent.append(check,node('span',"I'd like Aspire to contact me about this requirement."));form.append(consent);
  const trap=node('div',null,'atlas-brief-trap');trap.setAttribute('aria-hidden','true');textInput(trap,'Leave this field empty','website').tabIndex=-1;form.append(trap);
  const error=node('p',null,'atlas-brief-error');error.setAttribute('role','alert');error.hidden=true;form.append(error);
  form.addEventListener('submit',async e=>{
   e.preventDefault();if(submitting)return;
   let invalid=null;
   for(const input of form.querySelectorAll('input[required]')){const message=!input.value.trim()?'Please enter your '+input.name+'.':!input.validity.valid?'Please enter a valid email address.':'';if(message){input.setAttribute('aria-invalid','true');const msg=form.querySelector(`#${input.id}-error`);msg.textContent=message;msg.hidden=false;invalid??=input;}}
   if(invalid){invalid.focus();return;}
   submitting=true;next.disabled=true;back.disabled=true;panel.querySelector('.atlas-brief-close').disabled=true;next.textContent='Sending…';form.setAttribute('aria-busy','true');error.hidden=true;announce('Sending your brief.');
   const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),20000);
   try{
    const response=await fetch(panel.dataset.submitUrl,{signal:controller.signal,method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':panel.dataset.restNonce},body:JSON.stringify({nonce:panel.dataset.submitNonce,contact:{name:contact.name,email:contact.email,phone:contact.phone,company:contact.company,consent:contact.consent},brief:b(),website:contact.website})});
    const result=await response.json();if(!response.ok||result.received!==true)throw Error(result.message||'Your brief could not be sent. Please try again.');
    state.brief.firstName=contact.name.trim().split(/\s+/)[0];state.brief.submitted=true;go('success');announce('Brief received.');
   }catch(e){error.textContent=e.name==='AbortError'||e instanceof TypeError?'We could not confirm receipt. Check your connection before trying again.':e.message||'Your brief could not be sent. Please try again.';error.hidden=false;announce(error.textContent);}
   finally{clearTimeout(timeout);submitting=false;next.disabled=false;back.disabled=false;panel.querySelector('.atlas-brief-close').disabled=false;form.removeAttribute('aria-busy');if(state.brief.screen==='contact')next.textContent='SEND MY BRIEF TO ASPIRE →';}
  });stage.append(form);
 }
 function render(focus=true){
  const screen=state.brief.screen,step=state.brief.step;revision++;const focused=document.activeElement?.value;
  panel.hidden=false;stage.replaceChildren();nav.replaceChildren();nav.hidden=screen!=='questions';
  heading.textContent=screen==='review'?'YOUR HOUSTON CRE BRIEF':screen==='contact'?'Send your brief to Aspire':screen==='success'?'BRIEF RECEIVED':titles[step];
  panel.querySelector('.atlas-brief-progress').textContent=screen==='questions'?`${steps().indexOf(step)+1} / ${steps().length}`:screen==='review'?'REVIEW':screen==='contact'?'CONTACT':'';
  if(screen==='questions'){
   steps().forEach((s,i)=>{const btn=button(String(i+1),()=>edit(s));btn.setAttribute('aria-label',`Edit ${labels[s]}`);if(s===step)btn.setAttribute('aria-current','step');nav.append(btn);});
   stage.append(node('p','Your Atlas choices are carried forward. Use the section numbers to edit any answer.','atlas-brief-hint'));
   if(step==='goal'){
    choices('Your goal',schema.goals,'goal');
    if(b().goal==='lease_space')choices('Transaction',{'for-lease':'For Lease','for-sale-or-lease':'For Sale or Lease','':'All Transactions'},'transaction');
    if(b().goal==='lease_or_sell')choices('Owner intent',{both:'Lease or sell',unsure:'Not sure yet'},'ownerIntent');
   }
   if(step==='propertyTypes'){
    // One type keeps SF / acreage requirements unambiguous; schema retains a normalized array.
    const opts=Object.fromEntries(Object.entries(schema.propertyTypes).filter(([k])=>k!=='mixed-use'||b().goal==='manage_asset'||isOwner(b())||isInvestment(b())));
    const field=node('fieldset');field.dataset.field='propertyTypes';field.append(node('legend','Property type'));
    Object.entries(opts).forEach(([k,label])=>{const row=node('label',null,'atlas-brief-choice'),input=node('input');input.type='radio';input.name=heading.id+'-type';input.value=k;input.checked=b().propertyTypes.includes(k);input.addEventListener('change',()=>{b().propertyTypes=[k];update();});row.append(input,node('span',label));field.append(row);});stage.append(field);
   }
   if(step==='location'){
    const field=node('fieldset');field.append(node('legend','Houston location'));const label=node('label','Approximate area'),area=node('select');area.add(new Option('All Houston / flexible',''));Object.entries(schema.areas).forEach(([k,v])=>area.add(new Option(v.label,k)));area.value=b().location.areaPreset;area.addEventListener('change',()=>{b().location.areaPreset=area.value;update();change('brief-area');});label.append(area);
    const address=node('label','Address, area or submarket'),input=node('input');input.type='text';input.maxLength=240;input.value=b().location.text;input.addEventListener('input',()=>b().location.text=input.value);address.append(input);field.append(label,address,node('p','Area presets are approximate. Typed locations are shared with Aspire without geocoding.','atlas-brief-hint'));stage.append(field);
   }
   if(step==='size'){
    choices(isLand(b())?'Approximate lot size':'Approximate building / space size',Object.fromEntries(Object.entries(schema.sizes).filter(([k,v])=>k==='unsure'||v.unit===(isLand(b())?'acres':'sf')&&(!v.legacy||k===b().size)).map(([k,v])=>[k,v.label])),'size');
    if(isInvestment(b()))choices('Investment budget',Object.fromEntries(Object.entries(schema.budgets).map(([k,v])=>[k,v.label])),'budget');
   }
   if(step==='timing')choices('Timing',schema.timing,'timing');
   if(step==='priorities')choices('Select all that apply',Object.fromEntries(priorityOptions(b()).map(k=>[k,schema.priorities[k]])),'priorities',true);
   if(step==='managementNeeds')choices('Management needs',schema.managementNeeds,'managementNeeds',true);
  }else if(screen==='review')review();
  else if(screen==='contact')contactForm();
  else {stage.append(node('p',`Thanks, ${state.brief.firstName}. Aspire now has the details of what you're looking to accomplish.`),button('CONTINUE EXPLORING HOUSTON',explore,'atlas-brief-success-action'));if(isMatching(b())&&matchBrief(getFeatures(),b()).length)stage.append(button('VIEW MATCHING PROPERTIES',()=>{go('review');stage.querySelector('.atlas-brief-matches')?.scrollIntoView({block:'nearest'});},'atlas-brief-success-action'));}
  back.hidden=screen==='success';next.hidden=screen==='success'||screen==='review'&&state.brief.submitted;
  back.textContent=screen==='review'?'← Edit answers':screen==='contact'?'← Back to brief':'← Back';
  next.textContent=screen==='review'||screen==='contact'?'SEND MY BRIEF TO ASPIRE →':state.brief.editing?'Save changes →':'Continue →';
  if(focus){if(root.clientWidth>700&&root.getBoundingClientRect().top<0)root.scrollIntoView({block:'start',behavior:'instant'});panel.querySelector('.atlas-brief-body').scrollTop=0;heading.focus({preventScroll:true});if(root.clientWidth<=700)heading.scrollIntoView({block:'nearest'});}
  else if(focused)stage.querySelectorAll('input').forEach(i=>{if(i.value===focused)i.focus({preventScroll:true});});
  announce(heading.textContent);
 }
 next.addEventListener('click',()=>{
  if(state.brief.screen==='contact'){stage.querySelector('form').requestSubmit();return;}
  if(state.brief.screen==='review'){go('contact');return;}
  const step=state.brief.step;
  if(!stepComplete(b(),step)){
   const error=node('p','Choose an option to continue.','atlas-brief-error');error.id=heading.id+'-validation-'+revision;error.setAttribute('role','alert');stage.querySelector('.atlas-brief-error')?.remove();stage.append(error);const field=stage.querySelector('fieldset');field?.setAttribute('aria-describedby',error.id);stage.querySelector('input,select')?.focus();return;
  }
  const missing=steps().filter(s=>!stepComplete(b(),s));
  if(state.brief.editing&&!missing.length){state.brief.editing=false;go('review');return;}
  const later=missing.find(s=>steps().indexOf(s)>steps().indexOf(step));
  if(later||missing.length)go('questions',later||missing[0]);else go('review');
 });
 back.addEventListener('click',()=>{
  if(state.brief.screen==='contact'){go('review');return;}
  if(state.brief.screen==='review'){edit('goal');return;}
  if(state.brief.editing){state.brief.editing=false;const missing=steps().find(s=>!stepComplete(b(),s));if(!missing){go('review');return;}}
  const index=steps().indexOf(state.brief.step);if(index>0)go('questions',steps()[index-1]);else close();
 });
 panel.querySelector('.atlas-brief-close').addEventListener('click',close);
 return {panel,heading,render,
  open(){const seed=prefillBrief(state),key=state.mode+JSON.stringify(seed);state.brief=drafts.get(key)||{data:seed,screen:'questions',step:stepsFor(seed).find(s=>!stepComplete(seed,s))||'timing',editing:false,submitted:false};drafts.set(key,state.brief);render();},
  refresh(){if(state.briefOpen&&state.brief.screen==='review'&&!state.propertyFocusOpen)render(false);},
  matches:()=>matchBrief(getFeatures(),b()),
 };
}
