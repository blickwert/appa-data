/* Media picker for the file of a member document. Uploads made here go to the protected folder. */
( function ( $, __ ) {
	let frame;
	$( document ).on( 'click', '#bw-wm-doc-pick', function ( event ) {
		event.preventDefault();
		if ( ! frame ) {
			frame = wp.media( { title: __( 'Datei für das Mitglieder-Dokument', 'bw-woo-membership' ), button: { text: __( 'Übernehmen', 'bw-woo-membership' ) }, multiple: false } );
			frame.on( 'select', function () {
				const file = frame.state().get( 'selection' ).first().toJSON();
				$( '#bw-wm-doc-file' ).val( file.id );
				$( '#bw-wm-doc-name' ).text( file.filename + ( file.filesizeHumanReadable ? ' (' + file.filesizeHumanReadable + ')' : '' ) );
			} );
		}
		frame.open();
	} );
} )( jQuery, wp.i18n.__ );
