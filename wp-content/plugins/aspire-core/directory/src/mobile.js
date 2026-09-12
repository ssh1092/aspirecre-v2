import {SF,ACRES,defaults,filter} from './data.js';
// A directory-only dialog. Draft filters never alter inventory or history until applied.
export function mobileDirectory(root,api){
 const media=matchMedia('(max-width:767px)'),bar=document.createElement('div'),chips=document.createElement('div'),dialog=document.createElement('dialog');
 bar.className='directory-mobile-controls';chips.className='directory-mobile-chips';dialog.className='directory-filter-dialog';
 bar.innerHTML='<button type="button" data-open-filters>FILTERS</button>'+(root.dataset.mapModule?'<button type="button" data-mobile-view>MAP</button>':'');
 dialog.setAttribute('aria-label','Filter properties');dialog.innerHTML='<header><h2>FILTER PROPERTIES</h2><button type="button" aria-label="Close filters">×</button></header><div class="directory-filter-fields"></div><footer><button type="button" data-clear>CLEAR</button><button type="button" data-apply>SHOW PROPERTIES</button></footer>';
 const form=root.querySelector('.directory-filters').cloneNode(true);form.querySelector('.directory-reset').remove();form.setAttribute('aria-label','Mobile property filters');dialog.querySelector('.directory-filter-fields').append(form);
 root.prepend(bar,chips);root.append(dialog);
 const open=bar.querySelector('[data-open-filters]'),toggle=bar.querySelector('[data-mobile-view]'),apply=dialog.querySelector('[data-apply]'),controls=[...form.querySelectorAll('select')];let draft=defaults(),mobileView='list',desktopView=root.dataset.view;
 function paintDraft(){const size=form.querySelector('[name=size]');size.replaceChildren(...(draft.type==='land'?ACRES:SF).map(([v,l])=>new Option(l,v)));form.querySelector('.directory-size-label').textContent=draft.type==='land'?'SIZE · ACRES':'SIZE';controls.forEach(c=>c.value=draft[c.name]);const n=filter(api.features(),draft).length;apply.textContent=n?`SHOW ${n} ${n===1?'PROPERTY':'PROPERTIES'}`:'NO MATCHING PROPERTIES';}
 function close(){dialog.close();document.body.classList.remove('directory-filters-open');}
 open.addEventListener('click',()=>{draft={...api.filters()};paintDraft();dialog.showModal();document.body.classList.add('directory-filters-open');});
 dialog.querySelector('header button').addEventListener('click',close);dialog.addEventListener('close',()=>document.body.classList.remove('directory-filters-open'));
 controls.forEach(c=>c.addEventListener('change',()=>{if(c.name==='type'&&(draft.type==='land')!==(c.value==='land'))draft.size='';draft[c.name]=c.value;paintDraft();}));
 dialog.querySelector('[data-clear]').addEventListener('click',()=>{draft=defaults();paintDraft();});
 function commit(){api.apply({...draft});close();open.focus();}apply.addEventListener('click',commit);form.addEventListener('submit',e=>{e.preventDefault();commit();});
 function mode(){root.dataset.mobile=String(media.matches);root.dataset.mobileView=mobileView;api.view(media.matches?(mobileView==='map'?'split':'list'):desktopView);if(toggle)toggle.textContent=mobileView==='map'?'LIST':'MAP';}
 toggle?.addEventListener('click',()=>{mobileView=mobileView==='list'?'map':'list';mode();if(mobileView==='map')bar.scrollIntoView({block:'start'});else api.scrollSelected();});
 media.addEventListener('change',()=>{close();mode();});
 apply.setAttribute('aria-live','polite');apply.setAttribute('aria-atomic','true');
 const sizing=new ResizeObserver(()=>{const chrome=[bar,chips,root.querySelector('.directory-toolbar'),root.querySelector('.directory-sort-note'),document.querySelector('#wpadminbar')].filter(Boolean).reduce((sum,el)=>sum+el.getBoundingClientRect().height,0)+32;root.style.setProperty('--directory-map-chrome',`${chrome}px`);});[bar,chips,root.querySelector('.directory-toolbar'),root.querySelector('.directory-sort-note')].forEach(el=>sizing.observe(el));
 mode();
 return {isMap:()=>media.matches&&mobileView==='map',isMobile:()=>media.matches,focus:()=>open.focus(),desktopView(value){desktopView=value;mode();},refresh(){
  chips.replaceChildren();const values=api.filters();for(const [key,value] of Object.entries(values)){if(!value)continue;const original=root.querySelector(`.directory-filters [name="${key}"]`),label=[...original.options].find(o=>o.value===value)?.textContent||value,b=document.createElement('button');b.type='button';b.textContent=label+' ×';b.setAttribute('aria-label',`Remove ${label} filter`);b.addEventListener('click',()=>{const next={...api.filters(),[key]:''};if(key==='type')next.size='';api.apply(next);open.focus();});chips.append(b);}
  if(chips.children.length){const clear=document.createElement('button');clear.type='button';clear.textContent='Clear all';clear.addEventListener('click',()=>{api.apply(defaults());open.focus();});chips.append(clear);}chips.hidden=!chips.children.length;if(dialog.open){draft={...values};paintDraft();}
 },close};
}
