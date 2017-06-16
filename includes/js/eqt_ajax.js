jQuery(document).ready(function($) {

	$( '#ep-wp-ajax-button' ).click( function() {
		var id = $( '#ep-ajax-option-id' ).val();
		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: { 'action': 'ep_ajax_tester_approal_action', 'id': id }
		})
		.done(function( data ) {
			console.log('Successful AJAX Call! /// Return Data: ' + data);

			var rawresponse = data;
			data = JSON.parse( data );

			// Response Body
			$("#ep-response").val( rawresponse );

			rows = '';
			for (var i = 0; i < data.hits.hits.length; i++) {
				rows = rows + '<tr><td class="eqt-rw--1">' + i + '</td><td>' + data.hits.hits[i]._index + '</td><td>' + data.hits.hits[i]._source.post_title + '</td><td>' + data.hits.hits[i]._source.post_date + '</td></tr>';
			}

			// Response Table
			$( '#ep-ajax-table tbody' ).html(rows);

		})
		.fail(function( data ) {
			console.log('Failed AJAX Call :( /// Return Data: ' + data);
		});
	});

});
