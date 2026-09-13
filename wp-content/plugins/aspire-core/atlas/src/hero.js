import {heroIntents} from './hero-data.js';
export function createHero(root,discovery){
 const form=root.querySelector('.atlas-hero-form');if(!form)return;
 const input=form.querySelector('textarea'),label=form.querySelector('label'),error=form.querySelector('[role=alert]');
 const buttons=[...root.querySelectorAll('.atlas-intent')];
 buttons.forEach(button=>button.addEventListener('click',()=>{
  const intent=button.dataset.intent,content=heroIntents[intent];if(!content)return;
  root.dataset.heroIntent=intent;buttons.forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
  label.textContent=content.question;input.placeholder=content.placeholder;error.hidden=true;
  discovery.prioritizeHero(intent);
  root.dispatchEvent(new Event('atlas-hero-intent'));
 }));
 form.addEventListener('submit',event=>{
  event.preventDefault();const intent=root.dataset.heroIntent,need=input.value.trim();
  if(!heroIntents[intent]){error.hidden=false;buttons[0].focus();return;}
  if(!need){input.setCustomValidity('Tell us a little about what you need.');input.reportValidity();return;}
  discovery.openHeroBrief({goal:heroIntents[intent].goal,need});
 });
 input.addEventListener('input',()=>input.setCustomValidity(''));
}
