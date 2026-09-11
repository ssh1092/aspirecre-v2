<?php
defined( 'ABSPATH' ) || exit;
$id = $property->ID;
$location = implode( ', ', array_filter( array( get_post_meta( $id, '_aspire_city', true ), get_post_meta( $id, '_aspire_state', true ) ) ) );
$metric = aspire_core_block_metric( $id );
?>
<article class="property-card">
	<a class="card-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>" aria-labelledby="property-title-<?php echo esc_attr( $id ); ?>">
		<div class="card-image"><?php if ( has_post_thumbnail( $id ) ) : echo get_the_post_thumbnail( $id, 'aspire-card', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 560px) 100vw, (max-width: 1000px) 50vw, 25vw' ) ); else : ?><span class="card-placeholder">Property photograph to come</span><?php endif; ?></div>
		<div class="card-copy"><div class="card-tags"><span><?php echo esc_html( aspire_core_block_term_names( $id, 'property_type' ) ); ?></span><span><?php echo esc_html( aspire_core_block_term_names( $id, 'transaction_type' ) ); ?></span></div>
		<h3 id="property-title-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></h3>
		<?php if ( $location ) : ?><p class="card-location"><?php echo esc_html( $location ); ?></p><?php endif; ?>
		<div class="card-bottom"><span><?php echo esc_html( $metric ); ?></span><span class="card-arrow" aria-hidden="true">↗</span></div></div>
	</a>
</article>
