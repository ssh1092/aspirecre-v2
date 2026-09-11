/* global wp */
(function (blocks, element, blockEditor, components, serverSideRender) {
	'use strict';
	const el = element.createElement;
	const SSR = serverSideRender.default || serverSideRender;
	[
		['property-finder', 'Property Finder', 'search', null],
		['featured-properties', 'Featured Properties', 'building', 'Number of properties'],
		['team-grid', 'Team Grid', 'groups', 'Number of team members']
	].forEach(function (definition) {
		const name = 'aspire-core/' + definition[0];
		blocks.registerBlockType(name, {
			apiVersion: 3,
			title: definition[1],
			icon: definition[2],
			category: 'aspirecre',
			attributes: definition[3] ? { count: { type: 'integer', default: 4 } } : {},
			supports: { html: false, align: ['wide', 'full'] },
			edit: function (props) {
				const count = Math.max(1, Math.min(8, props.attributes.count || 4));
				return el(element.Fragment, null,
					definition[3] && el(blockEditor.InspectorControls, null,
						el(components.PanelBody, { title: 'Display settings', initialOpen: true },
							el(components.RangeControl, {
								label: definition[3], value: count, min: 1, max: 8,
								onChange: function (value) { props.setAttributes({ count: Math.max(1, Math.min(8, value || 4)) }); }
							})
						)
					),
					el('div', blockEditor.useBlockProps({ className: 'aspire-editor-dynamic aspire-editor-' + definition[0] }),
						el('div', { className: 'aspire-block-caption' }, definition[1]),
						el(components.Disabled, null, el(SSR, { block: name, attributes: definition[3] ? { count: count } : {}, httpMethod: 'GET' }))
					)
				);
			},
			save: function () { return null; }
		});
	});
})(wp.blocks, wp.element, wp.blockEditor, wp.components, wp.serverSideRender);
