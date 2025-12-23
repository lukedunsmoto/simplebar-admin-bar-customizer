jQuery(document).ready(function($){
	'use strict';

	// 1. Initialize Color Pickers
	if ( $.fn.wpColorPicker ) {
		$('.sbabc-colour-field').wpColorPicker();
	}

	// 2. Initialize Sortable Custom Links (The Table)
	var $tbody = $('#sbabc-links-body');
	if ( $tbody.length ) {
		$tbody.sortable({
			handle: '.sbabc-handle', // Drag using the icon/handle cell
			placeholder: 'ui-state-highlight',
			axis: 'y',
			helper: function(e, ui) {
				ui.children().each(function() {
					$(this).width($(this).width());
				});
				return ui;
			},
			update: function(e, ui) {
				reindexPositions();
			}
		});
	}

	function reindexPositions(){
		$('#sbabc-links-body').find('tr.sbabc-row').each(function(i, tr){
			$(tr).find('input.sbabc-position').val(i+1);
		});
	}

	// 3. Initialize Sortable Top-Level Items (The List)
	var $orderList = $('#sbabc-order-list');
	if ( $orderList.length ) {
		$orderList.sortable({
			handle: '.dashicons-move', // Fix: Drag using the move icon specifically
			placeholder: 'ui-state-highlight',
			update: function(e, ui) {
				var order = [];
				$orderList.find('.sbabc-order-item').each(function(){
					order.push( $(this).data('id') );
				});
				$('#sbabc-order-input').val( JSON.stringify(order) );
			}
		});
	}

	// 4. Delete Row
	$(document).on('click', '.sbabc-delete-row', function(){
		$(this).closest('tr.sbabc-row').remove();
		reindexPositions();
	});

	// 5. Add Row
	$('#sbabc-add-row').on('click', function(){
		var roleKey = $('input[name="sbabc_roles[__role__]"]').val();
		var idx     = $tbody.find('tr.sbabc-row').length + 1;
		var id      = 'sbabc-custom-' + (Date.now().toString(36) + Math.random().toString(36).slice(2,6));
		
		var row = '<tr class="sbabc-row">'
			+ '<td class="sbabc-handle" style="cursor:move;"><span class="dashicons dashicons-move"></span>'
			+ '<input type="hidden" class="sbabc-position" name="sbabc_roles[' + roleKey + '][custom]['+idx+'][position]" value="'+idx+'" />'
			+ '<input type="hidden" name="sbabc_roles[' + roleKey + '][custom]['+idx+'][id]" value="'+id+'" />'
			+ '</td>'
			+ '<td><input type="text" name="sbabc_roles[' + roleKey + '][custom]['+idx+'][title]" value="" class="regular-text" /></td>'
			+ '<td><input type="url"  name="sbabc_roles[' + roleKey + '][custom]['+idx+'][url]"   value="" class="regular-text" /></td>'
			+ '<td><select name="sbabc_roles[' + roleKey + '][custom]['+idx+'][target]"><option value="_self" selected>_self</option><option value="_blank">_blank</option></select></td>'
			+ '<td><input type="checkbox" name="sbabc_roles[' + roleKey + '][custom]['+idx+'][visible]" value="1" checked /></td>'
			+ '<td><button type="button" class="button sbabc-delete-row">Delete</button></td>'
			+ '</tr>';
			
		$tbody.append(row);
		$tbody.sortable('refresh');
	});

});
