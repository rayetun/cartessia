/**
 * Cartessia — attribute-term swatch admin.
 *
 * Turns the hex input into the WordPress colour picker and wires the swatch
 * image field to the media library.
 */
( function ( $ ) {
	$( function () {
		if ( $.fn.wpColorPicker ) {
			$( '.cartessia-color-field' ).wpColorPicker();
		}
	} );

	var strings =
		window.cartessiaSwatchAdmin || {
			frameTitle: 'Select image',
			frameButton: 'Use image',
		};

	// Open the media library and store the chosen attachment.
	$( document ).on( 'click', '.cartessia-swatch-image__select', function ( e ) {
		e.preventDefault();
		var wrap = $( this ).closest( '.cartessia-swatch-image' );

		var frame = window.wp.media( {
			title: strings.frameTitle,
			button: { text: strings.frameButton },
			library: { type: 'image' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url =
				att.sizes && att.sizes.thumbnail
					? att.sizes.thumbnail.url
					: att.url;
			wrap.find( '.cartessia-swatch-image__id' ).val( att.id );
			wrap
				.find( '.cartessia-swatch-image__preview' )
				.attr( 'src', url )
				.css( 'display', 'block' );
			wrap.find( '.cartessia-swatch-image__remove' ).css( 'display', 'inline' );
		} );

		frame.open();
	} );

	// Clear the chosen image.
	$( document ).on( 'click', '.cartessia-swatch-image__remove', function ( e ) {
		e.preventDefault();
		var wrap = $( this ).closest( '.cartessia-swatch-image' );
		wrap.find( '.cartessia-swatch-image__id' ).val( '' );
		wrap
			.find( '.cartessia-swatch-image__preview' )
			.attr( 'src', '' )
			.css( 'display', 'none' );
		$( this ).css( 'display', 'none' );
	} );
}( jQuery ) );
