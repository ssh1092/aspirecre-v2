<?php
/** Readable, indexable real-property portfolio; the theme may enhance the rail. */
defined( 'ABSPATH' ) || exit;
$rail_id = wp_unique_id( 'hp-portfolio-' );
?>
<div class="hp-portfolio" data-hp-rail="portfolio" role="group" aria-label="Current property opportunities">
	<div class="hp-rail-track" id="<?php echo esc_attr( $rail_id ); ?>" data-hp-rail-track>
		<?php foreach ( $properties as $position => $property ) :
			$id = $property->ID;
			$data = Aspire_Property_Dossier::data( $id );
			$transactions = wp_list_pluck( $data['transactions'], 'slug' );
			$metrics = array_slice( array_intersect_key( $data['snapshot'], array_flip( array( 'AVAILABLE', 'BUILDING', 'SITE', 'UNIT SIZE', 'CONTIGUOUS', 'LISTED SUITE SIZES' ) ) ), 0, 1, true );
			$title_id = $rail_id . '-title-' . $id;
			?>
			<article class="hp-portfolio-property" data-hp-rail-item data-property-id="<?php echo esc_attr( $id ); ?>" data-transactions="<?php echo esc_attr( implode( ' ', $transactions ) ); ?>" data-editorial-order="<?php echo esc_attr( $position ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
				<?php if ( has_post_thumbnail( $id ) ) : ?><figure class="hp-portfolio-image"><?php echo get_the_post_thumbnail( $id, 'full', array( 'alt' => $data['title'], 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 90vw' ) ); ?></figure><?php endif; ?>
				<div class="hp-portfolio-copy">
					<p class="hp-property-tags"><span><?php echo esc_html( $data['type']->name ?? '' ); ?></span><span><?php echo esc_html( implode( ' / ', wp_list_pluck( $data['transactions'], 'name' ) ) ); ?></span></p>
					<h3 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $data['title'] ); ?></h3>
					<?php if ( $data['locality'] ) : ?><p class="hp-property-locality"><?php echo esc_html( $data['locality'] ); ?></p><?php endif; ?>
					<div class="hp-portfolio-detail">
						<?php if ( $metrics ) : ?><dl class="hp-property-metric"><?php foreach ( $metrics as $label => $value ) : ?><div><dt><?php echo esc_html( ucwords( strtolower( $label ) ) ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
						<a class="hp-property-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>" aria-label="<?php echo esc_attr( 'View Property: ' . $data['title'] ); ?>">View Property <span aria-hidden="true">↗</span></a>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="hp-rail-controls" data-hp-rail-controls hidden>
		<button type="button" data-hp-rail-prev aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Previous property"><span aria-hidden="true">←</span></button>
		<output data-hp-rail-count aria-live="polite" aria-atomic="true">1 / <?php echo esc_html( count( $properties ) ); ?></output>
		<button type="button" data-hp-rail-next aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Next property"><span aria-hidden="true">→</span></button>
	</div>
</div>
