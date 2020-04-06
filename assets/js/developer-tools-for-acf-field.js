jQuery(document).ready(function ($) {

	var PmDebugToolForAcf = {
		list_of_fields: {},
		done_field: {},
		hide_items: [],
		last_postbox_container: $('.postbox-container:last-child'),
		make_feild_detail_html: function (field_name, field_key, field_type) {
			return '<span class="pm-acf-field-detail">'
				+ '<span class="pm-acf-field field_name"><em>name</em>' + field_name + '</span>'
				+ '<span class="pm-acf-field field_key"><em>key</em>' + field_key + '</span>'
				+ '<span class="pm-acf-field field_type"><em>type</em>' + field_type + '</span>'
				+ '</span>';
		},
		get_description_html: function (label) {
			return ($(label.find('p.description')).html() != undefined) ? $(label.find('p.description'))[0].outerHTML : '';
		},
		get_required_html: function (label) {
			return ($(label.find('span.acf-required')).html() != undefined) ? $(label.find('span.acf-required'))[0].outerHTML : '';
		},
		make_list: function (list) {
			var buf = '';
			$.each(list, function (panel, detail) {
				buf += '<div class="panel table">'
					+ '<h3>' + panel + '</h3>'
					+ '<div class="table">';
				$.each(detail, function (i, item) {
					buf += '<div class="tr">'
						+ '<span class="td label">' + item[0] + '</span>'
						+ '<span class="td detail">' + item[1] + '</span>'
						+ '</div>';
				});
				buf += '</div>'
					+ '</div>';
			});
			return '<div class="pm-acf-panels">' + buf + '</div>'
		},
	};

	if (typeof acf !== 'undefined' && typeof dtfa_settings !== 'undefined') {

		// get ignore type
		PmDebugToolForAcf.ignore_field_type = (dtfa_settings['ignore_field_type']) ? dtfa_settings['ignore_field_type'] : [];

		// get hide items
		PmDebugToolForAcf.hide_items = (dtfa_settings['hide_field_detail']) ? dtfa_settings['hide_field_detail'] : [];

		// display details to field label
		$(".acf-field").each(function (i) {

			if ($.inArray($(this).attr('data-type'), PmDebugToolForAcf.ignore_field_type) === -1) {

				var field = $(this);
				var group_name = field.closest('.acf-postbox').find('h2 span').text();
				var field_type = field.attr('data-type');
				var field_name = field.attr('data-name');
				var field_key = field.attr('data-key');
				var label = $(field.find('.acf-label > label')[0]);
				var label_html = label.html();
				var label_text = '';
				var found_label = false;
				var description = '';
				var required = '';

				// prevent run twice
				if (PmDebugToolForAcf.done_field[field_key] == undefined || PmDebugToolForAcf.done_field[field_key] != 1) {

					// find where label is
					if (label_html == undefined) {
						if ($(field.parents('table').find('th[data-key=' + field_key + ']')[0]) != undefined) {
							label = $(field.parents('table').find('th[data-key=' + field_key + ']')[0]);
							label_html = label.html();
							found_label = true;
						}
					}
					else {
						found_label = true;
					}

					if (found_label == true) {
						description = PmDebugToolForAcf.get_description_html(label);
						required = PmDebugToolForAcf.get_required_html(label);
						if (label_html != undefined) {
							label_html = label_html.replace(description, '').replace(required, '');
						}
						label_text = '<span class="label_only">' + $('<p>' + label_html + '</p>').text() + required + '</span>';
						var feild_detail = PmDebugToolForAcf.make_feild_detail_html(field_name, field_key, field_type);

						// replace label
						label.html(label_text + feild_detail + description);

						// keep for list
						if (PmDebugToolForAcf.list_of_fields[group_name] == undefined) {
							PmDebugToolForAcf.list_of_fields[group_name] = [];
						}
						PmDebugToolForAcf.list_of_fields[group_name].push([label_text, feild_detail]);

						// prevent run twice
						PmDebugToolForAcf.done_field[field_key] = 1;
					}

				}

			}
		});

		// remove hide items
		if (PmDebugToolForAcf.hide_items.length === 3) {
			// remove all
			$('.pm-acf-field-detail').remove()
		} else {
			// remove partial
			$.each(PmDebugToolForAcf.hide_items, function (i, item) {
				if (item != undefined) {
					$('.pm-acf-field.field_' + item).remove();
				}
			});
		}

		// show list
		PmDebugToolForAcf.last_postbox_container.after(
			'<div class="pm-acf-field-list postbox-container">'
			+ '<h2 class="hndle"><span>List of ACF groups and fields in this screen.</span></h2>'
			+ PmDebugToolForAcf.make_list(PmDebugToolForAcf.list_of_fields)
			+ '</div>'
		);
	}

});
