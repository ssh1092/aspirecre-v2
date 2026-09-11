import {focusData} from './focus-data.js';
import './property-focus.css';

export function createPropertyFocus(root) {
 const panel=root.querySelector('.atlas-dossier'),content=panel.querySelector('.atlas-dossier-content');
 const media=panel.querySelector('.atlas-dossier-media'),metrics=panel.querySelector('.atlas-dossier-metrics');
 const highlights=panel.querySelector('.atlas-dossier-highlights'),full=panel.querySelector('.atlas-focus-full');
 function fallback(){const brand=document.createElement('span');brand.className='atlas-dossier-image-fallback';brand.textContent='ASPIRE COMMERCIAL';media.replaceChildren(brand);}
 return {
  panel,back:panel.querySelector('.atlas-focus-back'),
  render(feature){
   const data=focusData(feature,location.origin);if(!data)return false;
   panel.querySelector('h2').textContent=data.title;
   for(const [selector,value] of [['.atlas-dossier-eyebrow',data.eyebrow],['.atlas-dossier-location',data.location]]){const el=panel.querySelector(selector);el.textContent=value;el.hidden=!value;}
   fallback();
   if(data.image){const image=document.createElement('img');image.src=data.image.url;image.alt=data.image.alt;image.decoding='async';image.addEventListener('error',()=>{if(media.contains(image))fallback();},{once:true});media.replaceChildren(image);}
   metrics.replaceChildren();metrics.hidden=!data.metrics.length;
   data.metrics.forEach(metric=>{const group=document.createElement('div'),label=document.createElement('dt'),value=document.createElement('dd');label.textContent=metric.label;value.textContent=metric.value;group.append(label,value);metrics.append(group);});
   const list=highlights.querySelector('ul');list.replaceChildren();highlights.hidden=!data.highlights.length;
   data.highlights.forEach(line=>{const item=document.createElement('li');item.textContent=line;list.append(item);});
   full.hidden=!data.permalink;if(data.permalink)full.href=data.permalink;else full.removeAttribute('href');
   content.scrollTop=0;
   return true;
  },
 };
}
