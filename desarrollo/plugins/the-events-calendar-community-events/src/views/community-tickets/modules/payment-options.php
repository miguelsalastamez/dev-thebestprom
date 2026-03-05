<?php
/**
 * My Payment Options Template.
 * The template for payment options.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community-tickets/modules/payment-options.php
 *
 * @link https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @version 4.10.17
 */

use Tribe\Community\Tickets\Payouts;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/** @var Tribe__Events__Community__Tickets__Main $main */
$main    = tribe( 'community-tickets.main' );
$ce_main = tribe( Tribe__Events__Community__Main::class );

/** @var Payouts $payouts */
$payouts = tribe( 'community-tickets.payouts' );

/** @var Tribe__Events__Community__Tickets__Fees $fees */
$fees = tribe( 'community-tickets.fees' );
$events_label_singular = $ce_main->get_event_label( 'singular' );
$events_label_plural   = $ce_main->get_event_label( 'plural' );
?>
	<div id="tribe-community-events">
		<div class="tribe-menu-wrapper">
			<a href="<?php echo esc_url( tribe_community_events_list_events_link() ); ?>" class="button">
				<?php
				echo sprintf(
					// Translators: dynamic 'Events' text.
					esc_html__( 'My %s', 'tribe-events-community' ),
					esc_html( $events_label_plural )
				);
				?>
			</a>
			<?php
			/**
			 * Triggered to display the navigation options in the Community Tickets payment options template.
			 */
			do_action( 'tribe_community_tickets_payment_options_nav' );
			?>
		</div>

		<?php
		/**
		 * Triggered before the payment options in the Community Tickets template.
		 */
		do_action( 'tribe_community_tickets_before_the_payment_options' );
		$options = get_option( $main::OPTIONNAME, $main->option_defaults );
		?>

		<form method="post">
			<?php wp_nonce_field( 'tribe_community_tickets_save_payment_options', 'payment_options_nonce' ); ?>
			<h3>
				<?php
				echo esc_html__( 'PayPal Options', 'tribe-events-community' );
				?>
			</h3>
			<div class="tribe-section-container">
				<p>
					<?php
					esc_html_e( 'Please enter your PayPal email address; this is needed in order to take payment.', 'tribe-events-community' );
					?>
				</p>
				<table class="tribe-community-tickets-payment-options" cellspacing="0" cellpadding="0">
					<tbody>
					<tr>
						<td>
							<?php tribe_community_events_field_label( 'paypal_account_email', __( 'Email:', 'tribe-events-community' ) ); ?>
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
								esc_html_e( 'Tickets cannot be created without an email address that is associated with PayPal', 'tribe-events-community' );
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
				<h3><?php echo esc_html__( 'Ticket Fees', 'tribe-events-community' ); ?></h3>
				<div class="tribe-section-container">
					<table class="tribe-community-tickets-payment-options" cellspacing="0" cellpadding="0">
						<tbody>
						<tr>
							<td>
								<?php echo esc_html__( 'Fee Structure:', 'tribe-events-community' ); ?>
							</td>
							<td>
								<?php
								if ( $flat && $percentage ) :
									// @todo Future note: We will want to implement the tribe_get_ticket_label_singular_lowercase() replacement here.
									echo sprintf(
									// Translators: 1: per transaction fee amount, 2: flat fee amount
										esc_html__(
											'Fees are %1$s%% per transaction plus a %2$s flat fee per ticket.',
											'tribe-events-community'
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
											'tribe-events-community'
										),
										esc_html( tribe_format_currency( number_format( $flat, 2 ) ) )
									);
								else :
									echo sprintf(
										// Translators: the formatted percentage fee
										esc_html__(
											'Fees are %s%% per transaction.',
											'tribe-events-community'
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
				<input type="submit" class="button submit events-community-submit" value="<?php echo esc_attr__( 'Save', 'tribe-events-community' ); ?>">
			</div>
		</form>
	</div>
<?php
/**
 * Triggered after the payment options in the Community Tickets template.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 */
do_action( 'tec_community_tickets_after_the_payment_options' );
