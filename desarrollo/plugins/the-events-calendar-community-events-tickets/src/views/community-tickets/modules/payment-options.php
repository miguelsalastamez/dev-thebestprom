<?php
/**
 * My Payment Options Template.
 * The template for payment options.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community-tickets/modules/payment-options.php
 *
 * @link https://evnt.is/1ao4 Help article for Community Events & Tickets template files.
 *
 * @since   3.1
 * @since   4.7.0
 * @since   4.7.4 Correct text domains, add translation comments, and code comments to make the file easier to follow.
 * @since 	4.8.2 Updated template link.
 * @since 	4.9.2 Updated the `tec_community_tickets_after_the_payment_options` filter naming.
 *
 * @version 4.9.2
 */

use Tribe\Community\Tickets\Payouts;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/** @var Tribe__Events__Community__Tickets__Main $main */
$main = tribe( 'community-tickets.main' );

/** @var Payouts $payouts */
$payouts = tribe( 'community-tickets.payouts' );

/** @var Tribe__Events__Community__Tickets__Fees $fees */
$fees = tribe( 'community-tickets.fees' );
?>
	<div id="tribe-community-events">
		<div class="tribe-menu-wrapper">
			<a href="<?php echo esc_url( tribe_community_events_list_events_link() ); ?>" class="button">
				<?php
				echo sprintf(
					// Translators: dynamic 'Events' text.
					esc_html__( 'My %s', 'tribe-events-community-tickets' ),
					esc_html( tribe_get_event_label_plural() )
				);
				?>
			</a>
			<?php
			/**
			 * Triggered to display the navigation options in the Community Tickets payment options template.
			 */
			do_action( 'tribe_community_tickets_payment_options_nav' );
			do_action_deprecated(
				'tribe_ct_payment_options_nav',
				null,
				'4.6.3',
				'tribe_community_tickets_payment_options_nav',
				'The action "tribe_ct_payment_options_nav" has been renamed to "tribe_community_tickets_payment_options_nav" to match plugin namespacing.'
			);
			?>
		</div>

		<?php
		/**
		 * Triggered before the payment options in the Community Tickets template.
		 */
		do_action( 'tribe_community_tickets_before_the_payment_options' );
		do_action_deprecated(
			'tribe_ct_before_the_payment_options',
			[],
			'4.6.3',
			'tribe_community_tickets_before_the_payment_options',
			'The action "tribe_ct_before_the_payment_options" has been renamed to "tribe_community_tickets_before_the_payment_options" to match plugin namespacing.'
		);
		$options = get_option( $main::OPTIONNAME, $main->option_defaults );
		?>

		<form method="post">
			<?php wp_nonce_field( 'tribe_community_tickets_save_payment_options', 'payment_options_nonce' ); ?>
			<h3>
				<?php
				echo esc_html__( 'PayPal Options', 'tribe-events-community-tickets' );
				?>
			</h3>
			<div class="tribe-section-container">
				<p>
					<?php
					esc_html_e( 'Please enter your PayPal email address; this is needed in order to take payment.', 'tribe-events-community-tickets' );
					?>
				</p>
				<table class="tribe-community-tickets-payment-options" cellspacing="0" cellpadding="0">
					<tbody>
					<tr>
						<td>
							<?php tribe_community_events_field_label( 'paypal_account_email', __( 'Email:', 'tribe-events-community-tickets' ) ); ?>
						</td>
						<td>
							<input type="email" id="paypal_account_email" name="paypal_account_email" value="<?php echo esc_attr( $data['paypal_account_email'] ); ?>" />
						</td>
					</tr>
					<?php
					if ( $payouts->is_split_payments_enabled() ) :
						?>
						<tr>
							<td></td>
							<td class="note">
								<?php
								// @todo Future note: We will want to implement the tribe_get_ticket_label_plural() replacement here.
								esc_html_e( 'Tickets cannot be created without an email address that is associated with PayPal', 'tribe-events-community-tickets' );
								?>
							</td>
						</tr>
					<?php
					endif;
					?>
					</tbody>
				</table>
			</div>

			<?php
			$gateway = $main->gateway( 'PayPal' );

			$flat       = $gateway->fee_flat;
			$percentage = $gateway->fee_percentage;

			if ( $fees->is_flat_fee( $options['site_fee_type'] ) ) {
				$flat += (float) $options['site_fee_flat'];
			}

			if ( $fees->is_percentage_fee( $options['site_fee_type'] ) ) {
				$percentage += (float) $options['site_fee_percentage'];
			}

			if ( $flat || $percentage ) :
				// @todo Future note: We will want to implement the tribe_get_ticket_label_singular() replacement here.
				?>
				<h3><?php echo esc_html__( 'Ticket Fees', 'tribe-events-community-tickets' ); ?></h3>
				<div class="tribe-section-container">
					<table class="tribe-community-tickets-payment-options" cellspacing="0" cellpadding="0">
						<tbody>
						<tr>
							<td>
								<?php echo esc_html__( 'Fee Structure:', 'tribe-events-community-tickets' ); ?>
							</td>
							<td>
								<?php
								if ( $flat && $percentage ) :
									// @todo Future note: We will want to implement the tribe_get_ticket_label_singular_lowercase() replacement here.
									echo sprintf(
									// Translators: 1: per transaction fee amount, 2: flat fee amount
										esc_html__(
											'Fees are %1$s%% per transaction plus a %2$s flat fee per ticket.',
											'tribe-events-community-tickets'
										),
										number_format( $percentage, 1 ),
										esc_html( tribe_format_currency( number_format( $flat, 2 ) ) )
									);
								elseif ( $flat ) :
									// @todo Future note: We will want to implement the tribe_get_ticket_label_plural_lowercase() replacement here.
									echo sprintf(
									// Translators: 1: fee amount
										esc_html__(
											'Fees are a flat fee of %1$s per ticket.',
											'tribe-events-community-tickets'
										),
										esc_html( tribe_format_currency( number_format( $flat, 2 ) ) )
									);
								else :
									echo sprintf(
										// Translators: the formatted percentage fee
										esc_html__(
											'Fees are %s%% per transaction.',
											'tribe-events-community-tickets'
										),
										number_format( $percentage, 1 )
									);
								endif;
								?>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
			endif;
			?>
			<div class="tribe-events-community-footer">
				<input type="submit" class="button submit events-community-submit" value="<?php echo esc_attr__( 'Save', 'tribe-events-community-tickets' ); ?>">
			</div>
		</form>
	</div>
<?php
/**
 * Triggered after the payment options in the Community Tickets template.
 *
 * @since 4.9.2
 */
do_action( 'tec_community_tickets_after_the_payment_options' );

/**
 * Triggered after the payment options in the Community Tickets template.
 *
 * @deprecated 4.9.2 Use tribe_community_tickets_after_the_payment_options instead.
 * @since      4.9.2
 *
 * @see        tribe_community_tickets_after_the_payment_options()
 */
do_action_deprecated(
	'tribe_commuity_tickets_before_the_payment_options',
	[],
	'4.9.2',
	'tribe_community_tickets_after_the_payment_options',
	'The action "tribe_commuity_tickets_before_the_payment_options" has been renamed to "tec_community_tickets_after_the_payment_options".'
);

/**
 * Fires before the payment options in the Community Tickets template.
 *
 * @deprecated 4.6.3 Use tribe_community_tickets_before_the_payment_options instead.
 * @since      4.6.3
 *
 * @see        tribe_community_tickets_before_the_payment_options()
 */
do_action_deprecated(
	'tribe_ct_before_the_payment_options',
	[],
	'4.6.3',
	'tribe_community_tickets_before_the_payment_options',
	'The action "tribe_ct_before_the_payment_options" has been renamed to "tribe_community_tickets_before_the_payment_options" to match plugin namespacing.'
);