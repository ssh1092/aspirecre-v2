/* Native #post form, native publishing actions and the existing Aspire field names. */
(() => {
 const root=document.querySelector('.aspire-property-workspace');if(!root)return;
 const title=document.getElementById('titlediv'),publishing=document.getElementById('submitdiv');
 if(title)root.querySelector('[data-native-title]').append(title);
 if(publishing)root.querySelector('[data-native-publishing]').append(publishing);
 const activate=window.AspirePropertyTabs(root),form=document.getElementById('post');
 root.querySelector('[data-property-save]').addEventListener('click',event=>{
  const button=event.currentTarget;const native=document.getElementById(button.dataset.published==='true'?'publish':'save-post');
  if(form.reportValidity())native?.click();
 });
 form.addEventListener('invalid',event=>{const panel=event.target.closest('[role="tabpanel"]');if(panel)activate(panel.id);let parent=event.target.parentElement;while(parent&&parent!==root){if(parent.tagName==='DETAILS')parent.open=true;parent=parent.parentElement;}},true);
 document.getElementById('title')?.addEventListener('input',event=>root.querySelector('[data-property-name]').textContent=event.target.value||'New property');
 const rows=root.querySelector('#pw-suites');let index=Math.max(-1,...[...rows.querySelectorAll('input')].map(input=>Number(input.name.match(/\[suites\]\[(\d+)\]/)?.[1]??-1)))+1;
 rows.addEventListener('click',event=>{const remove=event.target.closest('.pw-remove-suite');if(remove){const row=remove.closest('.pw-suite');const next=row.nextElementSibling?.querySelector('summary')||row.previousElementSibling?.querySelector('summary')||root.querySelector('#pw-add-space');row.remove();next.focus();}});
 rows.addEventListener('toggle',event=>{if(event.target.tagName==='DETAILS'&&event.target.open)rows.querySelectorAll('details[open]').forEach(item=>{if(item!==event.target)item.open=false;});},true);
 rows.addEventListener('input',event=>{
  const row=event.target.closest('.pw-suite');if(!row)return;
  const value=key=>row.querySelector(`[name$="[${key}]"]`).value;
  row.querySelector('[data-suite-name]').textContent=value('suite_name')||'New space';
  row.querySelector('[data-suite-info]').textContent=[value('square_feet')?Number(value('square_feet')).toLocaleString('en-US')+' SF':'',value('former_use'),value('rate')?'$'+value('rate')+'/SF '+value('rate_type'):''].filter(Boolean).join(' · ');
  row.querySelector('[data-suite-status]').textContent='Status: '+(value('availability_status').replaceAll('_',' ')||'Not confirmed');
 });
 root.querySelector('#pw-add-space').addEventListener('click',()=>{rows.insertAdjacentHTML('beforeend',root.querySelector('#pw-suite-template').innerHTML.replaceAll('__INDEX__',String(index++)));const detail=rows.lastElementChild.querySelector('details');rows.querySelectorAll('details').forEach(item=>item.open=item===detail);detail.querySelector('input').focus();});
 const announce=message=>root.querySelector('[data-media-announcement]').textContent=message;
 root.querySelectorAll('[data-media-kind]').forEach(section=>{
  const kind=section.dataset.mediaKind,input=section.querySelector('input[type="hidden"]'),grid=section.querySelector('.pw-media-grid');let dragged=null;
  const sync=()=>{input.value=[...grid.children].map(item=>item.dataset.id).join(',')||(kind==='featured'?'-1':'');input.dispatchEvent(new Event('change',{bubbles:true}));};
  const makeButton=(label,attribute,value)=>{const button=document.createElement('button');button.type='button';button.textContent=label;button.setAttribute(attribute,value);button.setAttribute('aria-label',attribute==='data-photo-remove'?'Remove photo':value==='-1'?'Move photo earlier':'Move photo later');return button;};
  function render(items){grid.replaceChildren();for(const item of items){const li=document.createElement('li');li.dataset.id=item.id;if(kind==='gallery')li.draggable=true;
   if(kind==='brochure'){const name=document.createElement('strong');name.textContent=item.filename||item.title||'Property brochure';const type=document.createElement('span');type.textContent='PDF';const link=document.createElement('a');link.href=item.url;link.target='_blank';link.rel='noopener';link.textContent='View ↗';li.append(name,type,link);}
   else{const img=document.createElement('img');img.src=item.sizes?.medium?.url||item.url;img.alt=item.alt||item.title||'Property photo';li.append(img);}
   if(kind==='gallery'){const controls=document.createElement('div');controls.className='pw-photo-controls';controls.append(makeButton('←','data-photo-move','-1'),makeButton('→','data-photo-move','1'),makeButton('Remove','data-photo-remove',''));li.append(controls);}grid.append(li);
  }sync();announce('Media selection updated. Save the property to keep changes.');}
  section.querySelector('[data-media-select]').addEventListener('click',()=>{
   const frame=wp.media({title:kind==='gallery'?'Property gallery':kind==='featured'?'Featured property image':'Property brochure',button:{text:'Use selected media'},library:{type:kind==='brochure'?'application/pdf':'image'},multiple:kind==='gallery'});
   frame.on('open',()=>{const selection=frame.state().get('selection');for(const id of input.value.split(',').map(Number).filter(id=>id>0)){const attachment=wp.media.attachment(id);attachment.fetch();selection.add(attachment);}});
   frame.on('select',()=>render(frame.state().get('selection').toJSON()));frame.on('close',()=>frame.remove());frame.open();
  });
  section.querySelector('[data-media-clear]')?.addEventListener('click',()=>{grid.replaceChildren();sync();announce('Media removed from this property. Save to keep changes.');});
  grid.addEventListener('click',event=>{const button=event.target.closest('button'),li=button?.closest('li');if(!li)return;
   if(button.hasAttribute('data-photo-remove')){const next=li.nextElementSibling?.querySelector('button')||li.previousElementSibling?.querySelector('button')||section.querySelector('[data-media-select]');li.remove();next.focus();}
   else if(button.dataset.photoMove==='-1'&&li.previousElementSibling)grid.insertBefore(li,li.previousElementSibling);
   else if(button.dataset.photoMove==='1'&&li.nextElementSibling)grid.insertBefore(li.nextElementSibling,li);
   sync();if(button.isConnected)button.focus();announce('Gallery order updated. Save to keep changes.');
  });
  grid.addEventListener('dragstart',event=>{dragged=event.target.closest('li');if(dragged){event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain',dragged.dataset.id);dragged.classList.add('is-dragging');}});
  grid.addEventListener('dragover',event=>{if(dragged)event.preventDefault();});
  grid.addEventListener('drop',event=>{event.preventDefault();const target=event.target.closest('li');if(dragged&&target&&target!==dragged){const children=[...grid.children];grid.insertBefore(dragged,children.indexOf(dragged)<children.indexOf(target)?target.nextSibling:target);sync();announce('Gallery order updated. Save to keep changes.');}});
  grid.addEventListener('dragend',()=>{dragged?.classList.remove('is-dragging');dragged=null;});
 });
 root.querySelector('#aspire-broker-filter')?.addEventListener('input',event=>{const query=event.target.value.toLowerCase();root.querySelectorAll('.aspire-brokers label').forEach(label=>label.hidden=!label.textContent.toLowerCase().includes(query));});
})();
