jQuery(function($) {
	console.log('here');
	$('.autocomplete.product-search').autocomplete({
		source: function(request, response) {
			$.ajax({
				dataType: 'json',
				url: AutocompleteSearch.ajax_url,
				data: {
					term: request.term,
					action: 'autocompleteSearch',
					security: AutocompleteSearch.ajax_nonce,
				},
				success: function(data) {
					console.log('got here');
					response(data);
				}
			});
		},
		select: function(event, ui) {
			$('#product-to-search-for').val(ui.item.id);
			//window.location.href = ui.item.link;
		},
	});
});