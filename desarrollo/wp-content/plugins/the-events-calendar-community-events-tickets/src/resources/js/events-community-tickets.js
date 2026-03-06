let tribe_events_community_tickets = {};

(function ( $, obj ) {
	'use strict';

	obj.init = function () {
		$( '.wp-list-table' ).wrap( '<div class="tribe-scrollable-table"/>' );
	};

	$( obj.init );

})( jQuery, tribe_events_community_tickets );
