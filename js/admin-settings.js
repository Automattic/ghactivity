/* global Query */

jQuery( document ).ready( function( $ ) {
	// When clicking on the Full sync button, start sync.
	$( '.full_sync' ).on( 'click', function( e ) {
		// Get the name of the repo we will need to synchronize.
		var repo_id = $(this).attr( 'name' );
		var repo    = repo_id.replace('_full_sync','');

		// Make a query to our custom endpoint to launch sync.
		$.ajax({
			url: ghactivity_settings.api_url + 'ghactivity/v1/sync/' + repo,
			method: 'POST',
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ghactivity_settings.api_nonce );
			}
		}).done( function ( response ) {
			$( '#' + repo_id ).attr( 'value', ghactivity_settings.progress_message );
			$( '#' + repo_id ).addClass( 'disabled' );
			$( '#' + repo + '_full_sync_details' ).html( response ).show();
		});
	});

	// When clicking on the button to rebuild graphs, call that function.
	$( '#ghactivity_redo_graphs' ).on( 'click', function( e ) {
		// Make a query to our custom endpoint to rebuild the graphs.
		$.ajax({
			url: ghactivity_settings.api_url + 'ghactivity/v1/build/graphs',
			method: 'POST',
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ghactivity_settings.api_nonce );
			}
		}).done( function ( response ) {
			$( '#ghactivity_redo_graphs_output' ).addClass( 'disabled' );
			$( '#ghactivity_redo_graphs_output' ).html( response ).show();
		});
	});

	$( '.reset_sync_status' ).on( 'click', function( e ) {
		// Get the name of the repo we will need to synchronize.
		var repo_id = $(this).attr( 'id' );
		var repo    = repo_id.replace('_reset_sync_status','');

		// Make a query to our custom endpoint to launch sync.
		$.ajax({
			url: ghactivity_settings.api_url + 'ghactivity/v1/sync/' + repo + '?reset=true',
			method: 'POST',
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ghactivity_settings.api_nonce );
			}
		}).done( function ( response ) {
			$( '#' + repo_id ).remove();
			$( '#' + repo_id ).attr( 'value', '' );
			$( '#' + repo_id ).removeClass( 'disabled' );
			$( '#' + repo + '_full_sync_details' ).html( response ).hide();

			console.log(response);
		});
	});
});
