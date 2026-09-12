/* global wp */
(function (blocks, element, blockEditor, components, serverSideRender) {
	'use strict';
	const el = element.createElement;
	const typeDescriptions = {"office": "Places for teams, clients and the working day.", "industrial-flex": "Space for operations, production and the movement of goods.", "land": "Sites to consider in the context of access, use and future plans.", "retail": "Places where businesses meet their customers."};
	const SSR = serverSideRender.default || serverSideRender;
	[
		['property-finder', 'Property Finder', 'search', null],
		['featured-properties', 'Featured Properties', 'building', 'Number of properties'],
		['team-grid', 'Team Grid', 'groups', 'Number of team members'],
		['property-types', 'Property Types', 'category', null]
	].forEach(function (definition) {
		const name = 'aspire-core/' + definition[0];
		const presentationOptions = [{label: 'Classic', value: 'classic'}, {label: 'Corporate editorial', value: 'editorial'}, definition[0] === 'team-grid' ? {label: 'Editorial portraits', value: 'portraits'} : {label: 'Property portfolio', value: 'portfolio'}];
		blocks.registerBlockType(name, {
			apiVersion: 3,
			title: definition[1],
			icon: definition[2],
			category: 'aspirecre',
			attributes: definition[3] ? { count: { type: 'integer', default: 4 }, presentation: { type: 'string', enum: presentationOptions.map(function (option) { return option.value; }), default: 'classic' }, ...(definition[0] === 'team-grid' ? { hideWhenEmpty: { type: 'boolean', default: false } } : {}) } : definition[0] === 'property-types' ? { descriptions: { type: 'object', default: typeDescriptions } } : {},
			supports: { html: false, align: ['wide', 'full'] },
			edit: function (props) {
				const count = Math.max(1, Math.min(8, props.attributes.count || 4));
				return el(element.Fragment, null,
                    definition[0] === 'property-types' && el(blockEditor.InspectorControls, null,
                        el(components.PanelBody, { title: 'Property type descriptions' }, Object.keys(typeDescriptions).map(function (slug) {
                            return el(components.TextareaControl, { key: slug, label: slug.replace(/-/g, ' '), value: (props.attributes.descriptions || typeDescriptions)[slug] || '', onChange: function (value) { props.setAttributes({ descriptions: Object.assign({}, props.attributes.descriptions || typeDescriptions, { [slug]: value }) }); } });
                        }))
                    ),
					definition[3] && el(blockEditor.InspectorControls, null,
						el(components.PanelBody, { title: 'Display settings', initialOpen: true },
							el(components.RangeControl, {
								label: definition[3], value: count, min: 1, max: 8,
								onChange: function (value) { props.setAttributes({ count: Math.max(1, Math.min(8, value || 4)) }); }
							}),
							el(components.SelectControl, { label: 'Presentation', value: props.attributes.presentation, options: presentationOptions, onChange: function (value) { props.setAttributes({ presentation: value }); } }),
							definition[0] === 'team-grid' && el(components.ToggleControl, { label: 'Hide when no team members are published', checked: !!props.attributes.hideWhenEmpty, onChange: function (value) { props.setAttributes({ hideWhenEmpty: value }); } })
						)
					),
					el('div', blockEditor.useBlockProps({ className: 'aspire-editor-dynamic aspire-editor-' + definition[0] }),
						el('div', { className: 'aspire-block-caption' }, definition[1]),
						el(components.Disabled, null, el(SSR, { block: name, attributes: definition[3] ? { count: count, presentation: props.attributes.presentation, ...(definition[0] === 'team-grid' ? { hideWhenEmpty: !!props.attributes.hideWhenEmpty } : {}) } : definition[0] === 'property-types' ? { descriptions: props.attributes.descriptions || typeDescriptions } : {}, httpMethod: 'GET' }))
					)
				);
			},
			save: function () { return null; }
		});
	});
})(wp.blocks, wp.element, wp.blockEditor, wp.components, wp.serverSideRender);
