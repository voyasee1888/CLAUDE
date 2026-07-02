( function ( $ ) {
	'use strict';

	$( function () {
		// Convenience: auto-suggest a slug from the name field on add screens
		// when the slug field is still empty. Does not overwrite an existing
		// slug, and only runs once per keystroke pause.
		var $name = $( '#name' );
		var $slug = $( '#slug' );

		if ( ! $name.length || ! $slug.length ) {
			return;
		}

		$name.on( 'blur', function () {
			if ( $slug.val() ) {
				return;
			}
			var suggestion = $name.val()
				.toLowerCase()
				.trim()
				.replace( /[^a-z0-9\s-]/g, '' )
				.replace( /\s+/g, '-' );
			$slug.val( suggestion );
		} );
	} );
} )( jQuery );
