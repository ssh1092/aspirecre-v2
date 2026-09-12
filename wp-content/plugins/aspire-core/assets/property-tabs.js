/* Shared progressive disclosure inside the public and native admin property forms. */
window.AspirePropertyTabs = root => {
 const tabs=[...root.querySelectorAll('[role="tab"]')],panels=[...root.querySelectorAll('[role="tabpanel"]')];
 const activate=(id,focus=false)=>{
  if(!panels.some(panel=>panel.id===id))id=panels[0].id;
  for(const tab of tabs){const selected=tab.getAttribute('aria-controls')===id;tab.setAttribute('aria-selected',String(selected));tab.tabIndex=selected?0:-1;if(selected&&focus)tab.focus();}
  for(const panel of panels)panel.hidden=panel.id!==id;
  root.dispatchEvent(new CustomEvent('propertymodechange',{detail:{id}}));
 };
 const select=tab=>{const hash='#'+tab.getAttribute('aria-controls');if(location.hash!==hash)history.pushState(null,'',hash);activate(hash.slice(1),true);};
 tabs.forEach((tab,index)=>{
  tab.addEventListener('click',event=>{event.preventDefault();select(tab);});
  tab.addEventListener('keydown',event=>{const key=event.key;let next;if(key==='ArrowRight')next=(index+1)%tabs.length;else if(key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;else if(key==='Home')next=0;else if(key==='End')next=tabs.length-1;else return;event.preventDefault();select(tabs[next]);});
 });
 window.addEventListener('hashchange',()=>activate(location.hash.slice(1)));
 window.addEventListener('popstate',()=>activate(location.hash.slice(1)));
 activate(location.hash.slice(1));return activate;
};
