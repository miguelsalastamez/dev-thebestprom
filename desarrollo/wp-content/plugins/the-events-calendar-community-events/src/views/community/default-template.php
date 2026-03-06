<?php
/**
 * Default Events Template placeholder:
 * Used to display Community content within the default events template itself.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/default-placeholder.php
 *
 * @link   https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @since  4.10.0
 * @since 5.0.0 Corrected template override path back to `tribe-events`.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Allows filtering the classes for the main element.
 *
 * @since 5.8.0
 *
 * @param array<string> $classes An (unindexed) array of classes to apply.
 */
$classes = apply_filters( 'tribe_default_events_template_classes', [ 'tribe-events-pg-template' ] );

get_header();

?>
	<div>
		<main
			id="tribe-events-pg-template"
			<?php tribe_classes( $classes ); ?>
		>
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		</main> <!-- #tribe-events-pg-template -->
	</div>
<?php

get_footer();
?>
