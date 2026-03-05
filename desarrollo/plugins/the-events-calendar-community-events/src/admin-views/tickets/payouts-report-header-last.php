<?php
/**
 * @var array $summary_counts
 */
?>
<h3>
	<?php
	printf(
		'%1$s: %2$s',
		esc_html( $summary_counts['any']['count_label'] ),
		esc_html( number_format_i18n( $summary_counts['any']['count'], 0 ) )
	);
	?>
</h3>

<ul class="tribe-event-meta-note">
	<?php foreach ( $summary_counts as $status => $summary_count ) : ?>
		<?php
		if ( 'any' === $status || $summary_count['count'] <= 0 ) {
			continue;
		}
		?>
		<li>
			<?php
			printf(
				'<strong>%1$s:</strong> %2$s',
				esc_html( $summary_count['count_label'] ),
				esc_html( number_format_i18n( $summary_count['count'] ) )
			);
			echo $summary_count['tooltip'];
			?>
		</li>
	<?php endforeach; ?>
</ul>

<h3>
	<?php
	printf(
		'%1$s: %2$s',
		esc_html( $summary_counts['any']['total_amount_label'] ),
		esc_html( tribe_format_currency( number_format_i18n( $summary_counts['any']['total_amount'], 2 ) ) )
	);
	?>
</h3>
<ul class="tribe-event-meta-note">
	<?php foreach ( $summary_counts as $status => $summary_count ) : ?>
		<?php
		if ( 'any' === $status || $summary_count['total_amount'] <= 0 ) {
			continue;
		}
		?>
		<li>
			<?php
			printf(
				'<strong>%1$s:</strong> %2$s',
				esc_html( $summary_count['total_amount_label'] ),
				esc_html( tribe_format_currency( number_format_i18n( $summary_count['total_amount'], 2 ) ) )
			);
			?>
		</li>
	<?php endforeach; ?>
</ul>
