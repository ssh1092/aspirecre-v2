/* global jQuery, wp */
(function ($) {
	'use strict';
	let nextSuite = 0;
	$('#aspire-suite-rows .aspire-suite').each(function () {
		const match = $(this).find('input').first().attr('name').match(/\[(\d+)\]/);
		if (match) nextSuite = Math.max(nextSuite, Number(match[1]) + 1);
	});
	$('#aspire-add-suite').on('click', function () {
		const html = $('#aspire-suite-template').html().replace(/__INDEX__/g, String(nextSuite++));
		$('#aspire-suite-rows').append(html).children().last().find('input').first().trigger('focus');
	});
	$('#aspire-suite-rows').on('click', '.aspire-remove-suite', function () {
		$(this).closest('.aspire-suite').remove();
		$('#aspire-add-suite').trigger('focus');
	});
	$('#aspire-broker-filter').on('input', function () {
		const query = this.value.toLowerCase();
		$('.aspire-brokers label').each(function () { $(this).toggle($(this).text().toLowerCase().includes(query)); });
	});
	$('.aspire-select-media').on('click', function () {
		const box = $(this).closest('.aspire-media');
		const pdf = box.data('kind') === 'brochure_attachment_id';
		const frame = wp.media({ title: pdf ? 'Select Brochure PDF' : 'Select Gallery Images', library: { type: pdf ? 'application/pdf' : 'image' }, button: { text: 'Use selection' }, multiple: !pdf });
		frame.on('open', function () {
			const selection = frame.state().get('selection');
			box.find('input').val().split(',').filter(Boolean).forEach(function (id) {
				const attachment = wp.media.attachment(Number(id));
				attachment.fetch();
				selection.add(attachment);
			});
		});
		frame.on('select', function () {
			const items = frame.state().get('selection').toJSON().filter(function (item) { return pdf ? item.mime === 'application/pdf' : item.type === 'image'; });
			box.find('input').val(items.map(function (item) { return item.id; }).join(','));
			const preview = box.find('.aspire-media-preview').empty();
			items.forEach(function (item) { $('<li>').text((item.title || item.filename) + ' (#' + item.id + ')').appendTo(preview); });
		});
		frame.open();
	});
	$('.aspire-clear-media').on('click', function () {
		const box = $(this).closest('.aspire-media');
		box.find('input').val('');
		box.find('.aspire-media-preview').empty();
	});
})(jQuery);
