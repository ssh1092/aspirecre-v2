/* global wp */
(function ({ blocks, element, blockEditor, components, serverSideRender }) {
 const el = element.createElement, SSR = serverSideRender.default || serverSideRender;
 blocks.registerBlockType('aspire-core/field-feed', {
  apiVersion: 3, title: 'Aspire In the Field', category: 'aspirecre', icon: 'format-gallery',
  attributes: { count: { type: 'integer', default: 6 } }, supports: { html: false, align: ['wide', 'full'] },
  edit({ attributes, setAttributes }) {
   return el(element.Fragment, null,
    el(blockEditor.InspectorControls, null, el(components.PanelBody, {title: 'In the Field'},
     el(components.RangeControl, {label: 'Media items', min: 1, max: 12, value: attributes.count, onChange: count => setAttributes({count})}),
     el('p', null, 'Uses the last refreshed Instagram feed. Until connected, the public block is clearly labeled as curated Aspire property photography.'))),
    el('div', blockEditor.useBlockProps(), el(components.Disabled, null, el(SSR, {block: 'aspire-core/field-feed', attributes}))));
  }, save: () => null
 });
})(wp);
