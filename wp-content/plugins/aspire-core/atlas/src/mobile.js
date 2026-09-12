export const mobileViewport=()=>matchMedia('(max-width: 767px)').matches;
export function createMobileSheet(root){
 const media=matchMedia('(max-width: 767px)'),sheet=document.createElement('section'),bar=document.createElement('div'),content=document.createElement('div'),footer=document.createElement('div');
 sheet.className='atlas-mobile-sheet';sheet.hidden=true;sheet.setAttribute('aria-label','Atlas panel');
 bar.className='atlas-sheet-handle';content.className='atlas-sheet-content';footer.className='atlas-sheet-footer';
 content.id=`${root.querySelector('h1').id}-workspace`;content.tabIndex=0;content.setAttribute('role','region');content.setAttribute('aria-label','Atlas content');
 const status=document.createElement('span');status.className='atlas-sr-only';status.setAttribute('role','status');
 const controls=['Collapse','Expand'].map(text=>{const b=document.createElement('button');b.type='button';b.setAttribute('aria-label',text==='Collapse'?'Show more map':'Show more content');b.title=text==='Collapse'?'Show more map':'Show more content';const icon=document.createElement('span');icon.className=`atlas-sheet-chevron ${text==='Collapse'?'is-down':'is-up'}`;icon.setAttribute('aria-hidden','true');b.append(icon);b.setAttribute('aria-controls',content.id);bar.append(b);return b;});
 sheet.append(bar,content,footer,status);root.append(sheet);
 const panels=[...root.querySelectorAll('.atlas-opening,.atlas-find,.atlas-dossier,.atlas-guided,.atlas-cre-brief')];
 const homes=new Map(panels.map(p=>{const marker=document.createComment('Atlas panel position');p.before(marker);return[p,marker];}));
 const history=new Map();
 let position='half',drag=null,active=null,actionHomes=[],lastKey='',onLayout=()=>{};
 const sizes=()=>{const height=root.clientHeight;const expanded=Math.max(150,height-(height<500?110:180));return{peek:Math.min(112,expanded),half:Math.min(expanded,Math.max(240,height*.56)),expanded};};
 function layout(notify=true){const h=sizes()[position];root.style.setProperty('--atlas-sheet-height',`${h}px`);sheet.dataset.position=position;status.textContent=position==='peek'?'Map view. Use Show more content to return to your details.':position==='half'?'Map and details visible.':'More room for your details.';controls[0].disabled=position==='peek';controls[1].disabled=position==='expanded';content.inert=position==='peek';footer.hidden=position==='peek';if(notify)onLayout();}
 function snap(next){position=next;sheet.classList.remove('is-dragging');layout();}
 controls[0].onclick=()=>snap(position==='expanded'?'half':'peek');controls[1].onclick=()=>snap(position==='peek'?'half':'expanded');
 function restoreActions(){for(const [node,marker] of actionHomes){marker.replaceWith(node);}actionHomes=[];}
 function sync(state,reason){if(!media.matches)return;
  const panel=state.propertyFocusOpen?panels[2]:state.briefOpen?panels[4]:['owner-disposition','manage-asset'].includes(state.mode)?panels[3]:state.mode==='explore'?panels[0]:panels[1];
  const key=state.propertyFocusOpen?'focus':state.briefOpen?`brief-${state.brief?.screen}-${state.brief?.step}`:`${state.mode}-${root.dataset.workflowStep||''}`;
  if(key!==lastKey&&lastKey)history.set(lastKey,{position,scroll:content.scrollTop});
  if(panel!==active){restoreActions();active=panel;content.scrollTop=0;
   const selector=panel===panels[2]?'.atlas-dossier-actions':panel===panels[4]?'.atlas-brief-controls':panel===panels[3]?'.atlas-guided-controls,.atlas-guided-phone':null;
   if(selector)for(const node of panel.querySelectorAll(selector)){const marker=document.createComment('Atlas action position');node.before(marker);actionHomes.push([node,marker]);footer.append(node);}
  }
  if(key!==lastKey){const previous=history.get(key);content.scrollTop=previous?.scroll??0;lastKey=key;snap(previous?.position??(state.propertyFocusOpen||state.briefOpen&& !['intro','success'].includes(state.brief?.screen)||['owner-disposition','manage-asset'].includes(state.mode)&&Number(root.dataset.workflowStep)>1?'expanded':'half'));}
 }
 function begin(e){if(e.target.closest('button')||e.button>0)return;drag={id:e.pointerId,y:e.clientY,height:sheet.getBoundingClientRect().height};bar.setPointerCapture(e.pointerId);sheet.classList.add('is-dragging');}
 bar.addEventListener('pointerdown',begin);
 bar.addEventListener('pointermove',e=>{if(!drag||e.pointerId!==drag.id)return;const s=sizes();root.style.setProperty('--atlas-sheet-height',`${Math.max(s.peek,Math.min(s.expanded,drag.height+drag.y-e.clientY))}px`);});
 function release(e){if(!drag||e.pointerId!==drag.id)return;const height=sheet.getBoundingClientRect().height;drag=null;snap(Object.entries(sizes()).sort((a,b)=>Math.abs(a[1]-height)-Math.abs(b[1]-height))[0][0]);}
 bar.addEventListener('pointerup',release);bar.addEventListener('pointercancel',release);
 // Native touch scrolling remains enabled. A pull down from the very top collapses one stop.
 let pull=null;content.addEventListener('touchstart',e=>{pull=content.scrollTop===0&&!e.target.closest('input,select,textarea,button,a')?e.touches[0].clientY:null;},{passive:true});
 content.addEventListener('touchend',e=>{if(pull!==null&&content.scrollTop===0&&e.changedTouches[0].clientY-pull>70)snap(position==='expanded'?'half':'peek');pull=null;},{passive:true});
 const nav=root.querySelector('.atlas-nav nav'),menu=document.createElement('button');menu.type='button';menu.className='atlas-mobile-menu';menu.textContent='Menu';menu.hidden=true;
 if(nav){nav.id=`${content.id}-navigation`;menu.setAttribute('aria-controls',nav.id);menu.setAttribute('aria-expanded','false');nav.before(menu);}
 function closeMenu(){menu.textContent='Menu';if(!nav)return;root.classList.remove('atlas-menu-open');menu.setAttribute('aria-expanded','false');if(media.matches)nav.hidden=true;}
 menu.onclick=()=>{const open=menu.getAttribute('aria-expanded')!=='true';nav.hidden=!open;root.classList.toggle('atlas-menu-open',open);menu.setAttribute('aria-expanded',String(open));menu.textContent=open?'Close':'Menu';};
 root.addEventListener('keydown',e=>{if(e.key==='Escape'&&menu.getAttribute('aria-expanded')==='true'){closeMenu();menu.textContent='Menu';menu.focus();}});
 root.addEventListener('pointerdown',e=>{if(menu.getAttribute('aria-expanded')==='true'&&!e.target.closest('.atlas-nav')){closeMenu();menu.textContent='Menu';}});
 nav?.addEventListener('click',e=>{if(e.target.closest('a')){closeMenu();menu.textContent='Menu';}});
 function toggle(){closeMenu();restoreActions();active=null;lastKey='';sheet.hidden=!media.matches;menu.hidden=!media.matches;root.classList.toggle('atlas-mobile',media.matches);
  if(media.matches){panels.forEach(p=>content.append(p));nav&&(nav.hidden=true);layout(false);}else{panels.forEach(p=>homes.get(p).after(p));nav&&(nav.hidden=false);root.style.removeProperty('--atlas-sheet-height');closeMenu();}
  root.dispatchEvent(new Event('atlas-mobile-change'));onLayout();
 }
 media.addEventListener('change',toggle);toggle();
 let timer;new ResizeObserver(()=>{if(media.matches){clearTimeout(timer);timer=setTimeout(()=>layout(),100);}}).observe(root);
 return{sync,connect(fn){onLayout=fn;},get active(){return media.matches;},padding(){const bounds=root.getBoundingClientRect();const top=Math.ceil((root.querySelector('.atlas-nav')?.getBoundingClientRect().bottom??bounds.top+60)-bounds.top+8);return{top,left:16,right:60,bottom:Math.min(bounds.height-top-40,sizes()[position]+12)};}};
}
