( function( wp ) {
    'use strict';
    wp.data.dispatch('core/notices').createNotice(
        'success', // Can be one of: success, info, warning, error.
        'This post is successfully converted into Gutenberg blocks.', // Text string to display.
        {
            isDismissible: true, // Whether the user can dismiss the notice.
        }
    );
} )( window.wp );