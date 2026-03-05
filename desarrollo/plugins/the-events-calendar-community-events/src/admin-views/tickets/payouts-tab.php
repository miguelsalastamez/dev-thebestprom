<div class="wrap tribe-payouts-page">
	<div id="icon-edit" class="icon32 icon32-tickets-orders"><br></div>

	<div id="tribe-payouts-summary" class="welcome-panel tribe-report-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

				<div class="welcome-panel-column welcome-panel-first">
					<h3><?php esc_html_e( 'Event Details', 'tribe-events-community' ); ?></h3>
					<ul>
						<?php
						/**
						 * Provides an action that allows for the injection of fields at the top of the payouts report details meta ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param $event_id
						 */
						do_action( 'events_community_tickets_report_list_first', $event_id );

						/**
						 * Provides an action that allows for the injection of fields at the bottom of the payouts report details ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param $event_id
						 */
						do_action( 'events_community_tickets_after_report_list_first', $event_id );
						?>
					</ul>

				</div>
				<div class="welcome-panel-column welcome-panel-middle">
					<h3><?php esc_html_e( 'Overview', 'tribe-events-community' ); ?></h3>
					<ul>
					<?php
						/**
						 * Provides an action that allows for the injection of fields at the top of the payouts report details meta ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param $event_id
						 */
						do_action( 'events_community_tickets_report_list_middle', $event_id );

						/**
						 * Provides an action that allows for the injection of fields at the bottom of the payouts report details ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param $event_id
						 */
						do_action( 'events_community_tickets_after_report_list_middle', $event_id );
						?>
					</ul>
				</div>
				<div class="welcome-panel-column welcome-panel-last alternate">
					<?php
					/**
					 * Provides an action that allows for the injection of fields at the top of the payouts report details meta ul
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param $event_id
					 */
					do_action( 'events_community_tickets_report_list_last', $event_id );

					/**
					 * Provides an action that allows for the injection of fields at the bottom of the payouts report details ul
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param $event_id
					 */
					do_action( 'events_community_tickets_after_report_list_last', $event_id );
					?>
				</div>
			</div>
		</div>
	</div>

	<form id="topics-filter" method="get">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>"/>
		<?php echo $table; ?>
	</form>
</div>
