/* global wp */
(function({blocks,element,blockEditor,components,serverSideRender}){
 const el=element.createElement; const SSR=serverSideRender.default||serverSideRender;
 blocks.registerBlockType('aspire/atlas',{
  apiVersion:3,title:'Aspire Atlas',category:'aspirecre',icon:'location-alt',
  attributes:{headline:{type:'string',default:'Explore Houston.\nFind your next move.'},supportingText:{type:'string',default:'Commercial real estate intelligence. Real opportunities. A stronger view of Houston.'},showNaturalLanguage:{type:'boolean',default:true},enableBrief:{type:'boolean',default:false},corporateHero:{type:'boolean',default:false},companyName:{type:'string',default:'ASPIRE COMMERCIAL'},productName:{type:'string',default:'ASPIRE ATLAS'},productLine:{type:'string',default:'Explore Houston. Find your next move.'},advisorPrompt:{type:'string',default:'Prefer to talk it through?'},advisorLabel:{type:'string',default:'Talk to an Aspire advisor'},continueLabel:{type:'string',default:'Discover Aspire Commercial'},continueTarget:{type:'string',default:'current-opportunities'}},
  supports:{html:false,anchor:true,align:['wide','full']},
  edit({attributes,setAttributes}){
   return el(element.Fragment,null,
    el(blockEditor.InspectorControls,null,el(components.PanelBody,{title:'Atlas hero content'},
     el(components.ToggleControl,{label:'Company homepage presentation',help:'Connects Aspire Commercial’s company message to the Atlas experience.',checked:attributes.corporateHero,onChange:corporateHero=>setAttributes({corporateHero})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Company name',value:attributes.companyName,onChange:companyName=>setAttributes({companyName})}),
     el(components.TextareaControl,{label:attributes.corporateHero?'Company heading (H1)':'Headline',value:attributes.headline,onChange:headline=>setAttributes({headline})}),
     el(components.TextareaControl,{label:attributes.corporateHero?'Company supporting copy':'Supporting text',value:attributes.supportingText,onChange:supportingText=>setAttributes({supportingText})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Product name',value:attributes.productName,onChange:productName=>setAttributes({productName})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Atlas product line',value:attributes.productLine,onChange:productLine=>setAttributes({productLine})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Advisor prompt',value:attributes.advisorPrompt,onChange:advisorPrompt=>setAttributes({advisorPrompt})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Advisor link label',value:attributes.advisorLabel,onChange:advisorLabel=>setAttributes({advisorLabel})}),
     attributes.corporateHero&&el(components.TextControl,{label:'Continue down the page',value:attributes.continueLabel,onChange:continueLabel=>setAttributes({continueLabel})}),
     attributes.corporateHero&&el(components.SelectControl,{label:'Continue to',value:attributes.continueTarget,options:[{label:'Client journey',value:'client-journey'},{label:'Current opportunities',value:'current-opportunities'}],onChange:continueTarget=>setAttributes({continueTarget})}),
     el(components.ToggleControl,{label:'Show requirements input',help:'Displays the existing read-only requirements prompt. Visitors can start with the Real Estate Brief button.',checked:attributes.showNaturalLanguage,onChange:showNaturalLanguage=>setAttributes({showNaturalLanguage})}),
     el(components.ToggleControl,{label:'Enable Real Estate Brief',help:'Includes the existing guided requirements and enquiry workflow.',checked:attributes.enableBrief,onChange:enableBrief=>setAttributes({enableBrief})})
    )),
    el('div',blockEditor.useBlockProps(),el(components.Disabled,null,el(SSR,{block:'aspire/atlas',attributes})))
   );
  },save:()=>null
 });
})(wp);
