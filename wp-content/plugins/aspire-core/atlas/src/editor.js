/* global wp */
(function({blocks,element,blockEditor,components,serverSideRender}){
 const el=element.createElement; const SSR=serverSideRender.default||serverSideRender;
 blocks.registerBlockType('aspire/atlas',{
  apiVersion:3,title:'Aspire Atlas',category:'aspirecre',icon:'location-alt',
  attributes:{headline:{type:'string',default:'Explore Houston.\nFind your next move.'},supportingText:{type:'string',default:'Commercial real estate intelligence. Real opportunities. A stronger view of Houston.'},showNaturalLanguage:{type:'boolean',default:true},enableBrief:{type:'boolean',default:false}},
  supports:{html:false,align:['wide','full']},
  edit({attributes,setAttributes}){
   return el(element.Fragment,null,
    el(blockEditor.InspectorControls,null,el(components.PanelBody,{title:'Atlas opening content'},
     el(components.TextareaControl,{label:'Headline',value:attributes.headline,onChange:headline=>setAttributes({headline})}),
     el(components.TextareaControl,{label:'Supporting text',value:attributes.supportingText,onChange:supportingText=>setAttributes({supportingText})}),
     el(components.ToggleControl,{label:'Show natural-language input',help:'Visual placeholder only in Task 1.',checked:attributes.showNaturalLanguage,onChange:showNaturalLanguage=>setAttributes({showNaturalLanguage})}),
     el(components.ToggleControl,{label:'Enable Build My Brief',help:'Shows a coming-later control. Brief creation is not part of Task 1.',checked:attributes.enableBrief,onChange:enableBrief=>setAttributes({enableBrief})})
    )),
    el('div',blockEditor.useBlockProps(),el(components.Disabled,null,el(SSR,{block:'aspire/atlas',attributes})))
   );
  },save:()=>null
 });
})(wp);
