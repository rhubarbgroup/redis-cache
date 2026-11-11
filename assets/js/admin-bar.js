( function ( settings, element ) {
    if ( ! element ) {
        return;
    }

    element.addEventListener( 'click', async function ( event ) {
        event.preventDefault();

        var node = document.querySelector( '#wp-admin-bar-redis-cache' );
        var textNode = node.querySelector( '.ab-item:first-child' );

        if ( ! textNode.dataset.text ) {
            textNode.dataset.text = textNode.innerText;
        }

        node.classList.remove( 'hover' );
        textNode.innerText = settings.l10n.flushing;

        try {
            var data = new FormData();
            data.append( 'action', 'roc_flush_cache' );
            data.append( 'nonce', settings.nonce );

            var response = await fetch( settings.ajaxurl, {
                method: 'POST',
                body: data,
            } );

            textNode.innerText = await response.text();

            setTimeout( function () {
                textNode.innerText = textNode.dataset.text;
            }, 3000 );
        } catch ( error ) {
            textNode.innerText = textNode.dataset.text;
            alert( 'Object cache could not be flushed: ' + error );
        }
    } );
} )(
    window.rediscache_admin_bar,
    document.querySelector( '#wp-admin-bar-redis-cache-flush > a' )
);
