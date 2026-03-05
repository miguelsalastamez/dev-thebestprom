<?php
/**
 * The Content Creation settings tab.
 *
 * @since 5.0.4
 */

$statuses = [
	'draft'   => 'Draft',
	'pending' => 'Pending Review',
	'publish' => 'Published',
];

/**
 * Allow for customizing the possible post statuses that submitted events default to.
 *
 * @since 1.0
 *
 * @param array $statuses
 */
$statuses = apply_filters( 'tribe_community_events_default_status_options', $statuses );

$tec_events_community_content_creation_fields = [
	'tec-events-community-settings-content-creation-title' => [
		'type' => 'html',
		'html' => '<h3 id="tec-events-community-settings-content-creation" class="tec-settings-form__section-header">' . esc_html__( 'Content Creation', 'tribe-events-community' ) . '</h3>',
	],
	'allowAnonymousSubmissions'                            => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Allow anonymous submissions', 'tribe-events-community' ),
		'tooltip'         => __( 'Check this box to allow users to submit events without having a WordPress account', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'useVisualEditor'                                      => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Use visual editor for event descriptions', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'default_post_type'                                    => [
		'type'  => 'wrapped_html',
		'label' => __( 'Default Post Type for submitted events', 'tribe-events-community' ),
		'html'  => tribe( 'community.main' )->get_community_events_post_type(),
	],
	'defaultStatus'                                        => [
		'type'            => 'dropdown',
		'label'           => __( 'Default status for submitted events', 'tribe-events-community' ),
		'validation_type' => 'options',
		'size'            => 'large',
		'default'         => 'draft',
		'options'         => $statuses,
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'spamPrevention'                                       => [
		'type'  => 'wrapped_html',
		'label' => __( 'Spam Prevention', 'tribe-events-community' ),
		'html'  => sprintf(
			/* Translators: %1$s is a placeholder for the URL to the API settings */
			__(
				'<p><i>To enable spam prevention for anonymous submissions, enter your reCAPTCHA API keys under <a href="%1$s">Events &rarr; Settings &rarr; APIs</a></i></p>',
				'tribe-events-community'
			),
			tribe( 'community.main' )->get_settings_strategy()->get_url( [ 'tab' => 'addons' ] ),
		),
	],
	'tec-events-community-settings-submission-terms-title' => [
		'type' => 'html',
		'html' => '<h3 id="tec-events-community-settings-submission-terms" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'Terms of Submission', 'tribe-events-community' ) . '</h3>',
	],
	'termsEnabled'                                         => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Enable Terms of Submission?', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'tooltip'         => __( 'Event submitters will have to agree to your terms to add/edit events.', 'tribe-events-community' ),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'termsDescription'                                     => [
		'type'            => 'textarea',
		'label'           => __( 'Terms of submission', 'tribe-events-community' ),
		'default'         => '',
		'tooltip'         => __( 'The Terms of Submission that event submitters will have to agree to upon adding/editing events.', 'tribe-events-community' ),
		'validation_type' => 'textarea',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
];

$content_creation_tab = new Tribe__Settings_Tab(
	'community-content-creation-tab',
	esc_html__( 'Content Creation', 'tribe-events-community' ),
	[
		'priority' => 36.05,
		'fields'   => apply_filters( 'tec_events_community_settings_content_creation_section', $tec_events_community_content_creation_fields ),
	]
);

/**
 * Fires after the Content Creation settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_content_creation', $content_creation_tab );

return $content_creation_tab;
