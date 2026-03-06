<?php

/**
 * Generic CT settings
 */
$ct_settings = [
	'tickets-heading'          => [
		'type' => 'html',
		'html' => '<h3 class="tec-settings-form__section-header">' . esc_html__( 'Community Tickets', 'tribe-events-community' ) . '</h3>',
	],
	'enable_community_tickets' => [
		'type' => 'checkbox_bool',
		'label' => esc_html__( 'Enable Community Tickets', 'tribe-events-community' ),
		'tooltip' => esc_html__( 'Check this box if you wish to turn on Community Tickets functionality', 'tribe-events-community' ),
		'default' => false,
		'validation_type' => 'boolean',
		'parent_option' => self::OPTIONNAME,
	],
	'edit_event_tickets_cap'   => [
		'type' => 'checkbox_bool',
		'label' => esc_html__( 'Allow any user to create tickets', 'tribe-events-community' ),
		'tooltip' => __( 'Check this box if you wish all Subscribers to receive the ability to create tickets. Uncheck it if you will be altering the <code>edit_event_tickets</code> capability either via a plugin or custom filter. Allowing Subscribers to create tickets will not let them manage attendees.', 'tribe-events-community' ),
		'default' => true,
		'validation_type' => 'boolean',
		'parent_option' => self::OPTIONNAME,
	],
	'enable_image_uploads'     => [
		'type' => 'checkbox_bool',
		'label' => esc_html__( 'Enable ticket images', 'tribe-events-community' ),
		'tooltip' => esc_html__( 'Check this box if you wish to allow community organizers to upload images for their tickets.', 'tribe-events-community' ),
		'default' => false,
		'validation_type' => 'boolean',
		'parent_option' => self::OPTIONNAME,
	],
];

/**
 * Settings specific to the site fees
 */
$site_fee_settings = [
	'site_fee_title'      => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__header-block tec-settings-form__element--rowspan-2">'
			. '<h3 id="tec-events-community-fee-settings__title" class="tec-settings-form__section-header tec-settings-form__section-header--sub">'
			. esc_html__( 'Ticket Fees', 'tribe-events-community' )
			. '</h3>'
			. sprintf(
				'<p>%1$s %2$s <a href="https://evnt.is/site-fees">%3$s</a></p>',
				esc_html__( 'Certain fee restrictions apply.', 'tribe-events-community' ),
				sprintf(
					/* translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag */
					esc_html__( 'Review our %1$sUnderstanding Site Fees KB%2$s for a further explanation so that you set your site fees according to what Community Tickets supports.', 'tribe-events-community' ),
					'<a href="https://evnt.is/site-fees">',
					'</a>'
				),
				esc_html__( 'Learn more.', 'tribe-events-community' )
			)
			. '</div>',
	],
	'site_fee_type'       => [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Ticket Fee Type', 'tribe-events-community' ),
		'tooltip'         => esc_html__( 'What type of fee will be charged?', 'tribe-events-community' ),
		'default'         => 'none',
		'validation_type' => 'options',
		'options'         => [
			'none'                           => esc_html__( 'None', 'tribe-events-community' ),
			'flat'                           => esc_html__( 'Flat Fee Per Event', 'tribe-events-community' ),
			'flat-per-ticket'                => esc_html__( 'Flat Fee Per Ticket', 'tribe-events-community' ),
			'percentage'                     => esc_html__( 'Percentage Fee Per Transaction', 'tribe-events-community' ),
			'flat-and-percentage'            => esc_html__( 'Flat & Percentage Fee Per Event', 'tribe-events-community' ),
			'flat-and-percentage-per-ticket' => esc_html__( 'Flat & Percentage Fee Per Ticket', 'tribe-events-community' ),
		],
		'attributes' => [ 'id' => 'site_fee_type-select' ],
	],
	'site_fee_percentage' => [
		'type'            => 'text',
		'label'           => esc_html__( 'Fee percentage', 'tribe-events-community' ),
		'tooltip'         => esc_html__( 'The percentage fee charged to each ticket transaction.', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'positive_decimal_or_percent',
		'size'            => 'small',
		'class'           => 'tribe-dependent',
		'fieldset_attributes'      => [
			'data-depends'   => '#site_fee_type-select',
			'data-condition-not' => '["none", "flat", "flat-per-ticket"]',
		],
	],
	'site_fee_flat'       => [
		'type'            => 'text',
		'label'           => esc_html__( 'Flat fee', 'tribe-events-community' ),
		'tooltip'         => esc_html__( 'The flat fee charged per the Ticket Fee Type setting.', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'positive_decimal',
		'size'            => 'small',
		'class'           => 'tribe-dependent',
		'fieldset_attributes'      => [
			'data-depends'   => '#site_fee_type-select',
			'data-condition-not' => '["none","percentage"]',
		],
	],
	'payment_fee_setting' => [
		'type'                => 'radio',
		'label'               => esc_html__( 'Fee display option', 'tribe-events-community' ),
		'validation_type'     => 'options',
		'class'               => 'tribe-dependent',
		'fieldset_attributes' => [
			'data-depends'       => '#site_fee_type-select',
			'data-condition-not' => 'none',
		],
		'default'             => 'absorb',
		'options'             => [
			'absorb' => esc_html__( 'Fees will be subtracted from the cost of the ticket (paid tickets only)', 'tribe-events-community' ) .
				// spacing in front of the paragraph is intentional to make the auto-generated tooltip look OK
				' <p class="tooltip description">' .
				sprintf(
					'%1s %2s%3s%4s',
					esc_html__( 'Additional fees will be subtracted from the individual ticket price. The fee will be displayed on the event submission form, but ticket prices will not be restricted to accommodate the fee.', 'tribe-events-community' ),
					'<a href="https://evnt.is/site-fees-absorb">',
					esc_html__( 'Learn more', 'tribe-events-community' ),
					'</a>'
				) .
				'</p>',
			'add'    => esc_html__( 'Fees will be added to the price of the ticket', 'tribe-events-community' ) .
				// spacing in front of the paragraph is intentional to make the auto-generated tooltip look OK
				' <p class="tooltip description">' .
				sprintf(
					'%1s %2s%3s%4s',
					esc_html__( 'Additional fees will be added to the individual ticket price. The fee will also be displayed on the event submission form, but ticket prices will not be restricted to accommodate the fee.', 'tribe-events-community' ),
					'<a href="https://evnt.is/site-fees-add">',
					esc_html__( 'Learn more', 'tribe-events-community' ),
					'</a>'
				) .
				'</p>',
			'pass'   => esc_html__( 'Display fees in addition to subtotal on the Cart page', 'tribe-events-community' ) .
				// spacing in front of the paragraph is intentional to make the auto-generated tooltip look OK
				' <p class="tooltip description">' .
				sprintf(
					'%1s %2s%3s%4s',
					esc_html__( 'Additional fees will be added to the total ticket price (applies to paid and free tickets).', 'tribe-events-community' ),
					'<a href="https://evnt.is/site-fees-pass">',
					esc_html__( 'Learn more', 'tribe-events-community' ),
					'</a>'
				) .
				'</p>',
		],
	],
	'site_fee_on_free'    => [
		'type'                => 'checkbox_bool',
		'label'               => esc_html__( 'Add flat fees to free tickets', 'tribe-events-community' ),
		'tooltip'             => esc_html__( 'When checked, flat fees will be added to free tickets as well as paid tickets.', 'tribe-events-community' ),
		'validation_type'     => 'boolean',
		'class'               => 'tribe-dependent',
		'fieldset_attributes' => [
			'data-depends'       => '#site_fee_type-select',
			'data-condition-not' => '["none","percentage"]',
		],
	],
];

foreach ( $site_fee_settings as $name => $setting ) {
	// skip non-field "settings"
	if ( 'html' === $setting['type'] ) {
		continue;
	}

	$setting['parent_option'] = self::OPTIONNAME;

	if ( ! isset( $setting['can_be_empty'] ) ) {
		$setting['can_be_empty'] = true;
	}

	$site_fee_settings[ $name ] = $setting;
}

/**
 * Settings for split payments
 */
$split_payment_settings  = [
	//Split Payments
	'split_payments_settings_title' => [
		'type' => 'html',
		'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub tribe-dependent" data-depends="#site_fee_type-select" data-condition-not="none">' . esc_html__( 'Split Payment Settings', 'tribe-events-community' ) . '</h3>',
	],
	'enable_split_payments'         => [
		'type'                => 'checkbox_bool',
		'label'               => esc_html__( 'Enable split payments', 'tribe-events-community' ),
		'tooltip'             => sprintf(
			esc_html__(
				'Leaving this box unchecked means that all funds will be collected by the payment methods configured in WooCommerce.
				Check this box if you prefer the money for tickets to be distributed to the site and the event
				organizer. You will need a PayPal %1$sdeveloper account%2$s. We advise turning off auto-complete and issuing refunds.
				%3$s
				',
				'tribe-events-community'
			),
			'<a href="https://developer.paypal.com/" target="_blank">',
			'</a>',
			'<a href="https://evnt.is/split-payments">' . esc_html__( 'Read more', 'tribe-events-community' ) . '</a>'
		),
		'class'               => 'tribe-dependent',
		'fieldset_attributes' => [
			'data-depends'       => '#site_fee_type-select',
			'data-condition-not' => '["none"]',
		],
		'default'             => 'none',
		'validation_type'     => 'boolean',
		'parent_option'       => self::OPTIONNAME,
		'attributes'          => [
			'id' => 'community-tickets-enable-split-payments',
		],
	],
];

/**
 * PayPal settings required for split payments
 */
$paypal_settings = [
	'paypal_settings_start'    => [
		'type' => 'html',
		'html' => '<div id="tribe-events-community-tickets-paypal-settings" class="tribe-dependent tribe-settings-last-block" data-depends="#community-tickets-enable-split-payments" data-condition-is-checked>',
	],
	'tickets_blurb'            => [
		'type'            => 'wrapped_html',
		'label'           => esc_html__( 'Configure PayPal:', 'tribe-events-community' ),
		'html'            => '<span>' . esc_html__( 'The following PayPal settings are required for enabling split payments.', 'tribe-events-community' ) . '</span>',
		'validation_type' => 'html',
	],
	'split_payment_method'     => [
		'type'            => 'radio',
		'label'           => esc_html__( 'Split Payment method', 'tribe-events-community' ),
		'default'         => 'paypal_payouts_api',
		'validation_type' => 'options',
		'options'         => [
			'paypal_payouts_api' => esc_html__( 'Use the PayPal Payouts API', 'tribe-events-community' ) .
				// spacing in front of the paragraph is intentional to make the auto-generated tooltip look OK
				' <p class="ticket_form_right tribe-style-selection description">' .
				sprintf(
					'%1$s <a href="%2$s" title="%3$s">%4$s</a><br><br>%5$s<br><br>%6$s',
					esc_html__( 'Learn about PayPal\'s Payouts API ', 'tribe-events-community' ),
					esc_url( 'https://evnt.is/split-payments-payouts' ),
					esc_attr__( "Learn more about PayPal's Payouts API", 'tribe-events-community' ),
					esc_html__( 'Read more.', 'tribe-events-community' ),
					esc_html__( 'IMPORTANT: You may use any payment gateway we currently support for selling Community Tickets through WooCommerce. However, all Payout funds will be withdrawn from the PayPal account you enter into the PayPal settings.', 'tribe-events-community' ),
					esc_html__( "For example, if you use Stripe as your payment processor for WooCommerce and you have enabled Split Payments for Community Tickets, your Organizer will be paid via your PayPal account, not your Stripe account. It's important that you have enough funds within your PayPal account in order to successfully pay your organizers.", 'tribe-events-community' )
				) .
				'</p>',
			'paypal_adaptive_payments' => esc_html__( 'Use the legacy PayPal Adaptive Payments API', 'tribe-events-community' ) .
				// spacing in front of the paragraph is intentional to make the auto-generated tooltip look OK
				' <p class="ticket_form_right tribe-style-selection description">' .
					sprintf(
						'%1$s <a href="%2$s" title="%3$s">%4$s</a>',
						esc_html__( 'This API has been deprecated by PayPal and you have to have an existing account to use it. ', 'tribe-events-community' ),
						esc_url( 'https://evnt.is/split-payments-adaptive' ),
						esc_attr__( "Read more about PayPal's Adaptive Payments API", 'tribe-events-community' ),
						esc_html__( 'Read more.', 'tribe-events-community' )
					) .
				'</p>',
		],
	],
	'paypal_sandbox'           => [
		'type'            => 'checkbox_bool',
		'label'           => esc_html__( 'Use PayPal sandbox', 'tribe-events-community' ),
		'tooltip'         => esc_html__( 'Check this box if you wish all payments to be test payments via PayPal sandbox.', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'class'           => 'light-bordered full-width',
	],
	'paypal_api_client_id'     => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal API Client ID', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'html',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_payouts_api',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_api_client_secret' => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal API Client Secret', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'html',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_payouts_api',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_api_username'      => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal API Username', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'html',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_adaptive_payments',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_api_password'      => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal API Password', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'html',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_adaptive_payments',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_api_signature'     => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal API Signature', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'alpha_numeric_multi_line_with_dots_and_dashes',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_adaptive_payments',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_application_id'    => [
		'type'            => 'text',
		'label'           => esc_html__( 'PayPal Application ID', 'tribe-events-community' ),
		'default'         => '',
		'validation_type' => 'html',
		'class'           => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_adaptive_payments',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_receiver_email'    => [
		'type'                => 'text',
		'label'               => esc_html__( 'Receiver email', 'tribe-events-community' ),
		'tooltip'             => esc_html__( 'This is the email address for the PayPal account that will receive payments.', 'tribe-events-community' ),
		'default'             => '',
		'validation_type'     => 'email',
		'class'               => 'light-bordered tribe-dependent full-width',
		'fieldset_attributes' => [
			'data-depends'              => '#tribe-field-split_payment_method-paypal_adaptive_payments',
			'data-condition-is-checked' => '1',
		],
	],
	'paypal_invoice_prefix'    => [
		'type'            => 'text',
		'label'           => esc_html__( 'Invoice prefix', 'tribe-events-community' ),
		'tooltip'         => esc_html__( 'Enter a prefix for your PayPal invoices. If you run multiple stores/event sites, entering a value here will ensure that your invoice numbers are unique - PayPal does not accept duplicate invoice numbers.', 'tribe-events-community' ),
		'default'         => 'CT-',
		'validation_type' => 'html',
		'class'           => 'light-bordered full-width',
	],
	'paypal_settings_end'      => [
		'type' => 'html',
		'html' => '</div>',
	],
];

foreach ( $paypal_settings as $name => $setting ) {
	// skip non-field "settings"
	if ( 'html' === $setting['type'] || 'wrapped_html' === $setting['type'] ) {
		continue;
	}

	$setting['parent_option'] = self::OPTIONNAME;

	if ( ! isset( $setting['can_be_empty'] ) ) {
		$setting['can_be_empty'] = true;
	}

	$paypal_settings[ $name ] = $setting;
}

$settings = array_merge( $settings, $ct_settings, $site_fee_settings, $split_payment_settings, $paypal_settings );
