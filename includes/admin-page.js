( function ( $ ) {
	$( function () {
		$( ".notice.is-dismissible[data-dismissible]" ).on(
			"click.roc-dismiss-notice",
			".notice-dismiss",
			function ( event ) {
				$.post( ajaxurl, {
					notice: $( this ).parent().attr( "data-dismissible" ),
					action: "roc_dismiss_notice",
				} );

				event.preventDefault();
			}
		);
	} );
} ( jQuery ) );
