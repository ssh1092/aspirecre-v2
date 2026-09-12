<?php
/** Four actual property taxonomy territories, using current local inventory. */
defined( 'ABSPATH' ) || exit;
$territories = aspire_core_block_property_types();
if ( ! $territories ) {
	if ( $editor ) { echo '<p>No property types are registered yet.</p>'; }
	return;
}
$descriptions = is_array( $attributes['descriptions'] ?? null ) ? $attributes['descriptions'] : array();
$rail_id = wp_unique_id( 'hp-property-types-' );
?>
<div class="hp-property-types" data-hp-rail="types" role="group" aria-label="Explore commercial property types">
	<div class="hp-rail-track" id="<?php echo esc_attr( $rail_id ); ?>" data-hp-rail-track>
		<?php foreach ( $territories as $territory ) : ?>
			<article class="hp-property-type" data-hp-rail-item data-property-type="<?php echo esc_attr( $territory['slug'] ); ?>" data-property-count="<?php echo esc_attr( $territory['count'] ); ?>">
				<div class="hp-property-type-link">
					<?php if ( $territory['image'] ) : ?><figure class="hp-property-type-image"><?php echo wp_get_attachment_image( $territory['image'], 'large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 82vw, 50vw' ) ); ?></figure><?php endif; ?>
					<div class="hp-property-type-copy"><h3><?php echo esc_html( $territory['name'] ); ?></h3><p><?php echo esc_html( sprintf( _n( '%s current property', '%s current properties', $territory['count'], 'aspire-core' ), number_format_i18n( $territory['count'] ) ) ); ?></p><div class="hp-type-details"><p class="hp-type-description"><?php echo esc_html( is_string( $descriptions[ $territory['slug'] ] ?? null ) ? $descriptions[ $territory['slug'] ] : '' ); ?></p><a class="hp-type-action" href="<?php echo esc_url( $territory['url'] ); ?>">Explore <?php echo esc_html( $territory['name'] ); ?> <span aria-hidden="true">↗</span></a></div></div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="hp-rail-controls" data-hp-rail-controls hidden>
		<button type="button" data-hp-rail-prev aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Previous property type"><span aria-hidden="true">←</span></button>
		<output data-hp-rail-count aria-live="polite" aria-atomic="true">1 / <?php echo esc_html( count( $territories ) ); ?></output>
		<button type="button" data-hp-rail-next aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Next property type"><span aria-hidden="true">→</span></button>
	</div>
</div>
