let tribe_events_community_tickets_admin = {
	event : {}
};

( function ( $, obj ) {
	"use strict";

	obj.selectors = {
		'siteFeeType': 'select[name="site_fee_type"]',
		'enableSplitPayments': 'input[name="enable_split_payments"]',
	}
	// Set our form variable to use later.
	const $form = $( obj.selectors.siteFeeType ).closest("form");

	obj.init = function () {

		$(document).on( 'change', obj.selectors.siteFeeType , obj.event.change_site_fee)
		$(document).on( 'change', obj.selectors.enableSplitPayments , obj.event.change_split_payments)

		$( obj.selectors.siteFeeType ).trigger( "change" );
		$( obj.selectors.enableSplitPayments ).trigger( "change" );
	};

	obj.change_site_fee = function ( fee_value ) {

		$form.removeClass( "site-fee-none site-fee-flat site-fee-percentage" );

		if ( "none" === fee_value ) {
			$form.addClass( "site-fee-none" );
		} else if ( "flat" === fee_value ) {
			$form.addClass( "site-fee-flat" );
		} else if ( "percentage" === fee_value ) {
			$form.addClass( "site-fee-percentage" );
		} else if ( "flat-and-percentage" === fee_value ) {
			$form.addClass( "site-fee-flat site-fee-percentage" );
		}
	};

	obj.change_split_payments = function ( enabled ) {
		if ( enabled ) {
			$form.addClass( "split-payments-enabled" );
		} else {
			$form.removeClass( "split-payments-enabled" );
		}
	};

	obj.event.change_site_fee = function () {
		obj.change_site_fee( $( this ).val() );
	};

	obj.event.change_split_payments = function () {
		obj.change_split_payments( $( this ).is( ":checked" ) );
	};

	$( obj.init );
} )( jQuery, tribe_events_community_tickets_admin );
