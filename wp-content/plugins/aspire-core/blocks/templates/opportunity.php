<?php
/** Editorial presentation of an existing public Property; no additional data model. */
defined( 'ABSPATH' ) || exit;
$id = $property->ID;
$data = Aspire_Property_Dossier::data( $id );
$metrics = array_slice( array_intersect_key( $data['snapshot'], array_flip( array( 'AVAILABLE', 'BUILDING', 'SITE', 'UNIT SIZE', 'CONTIGUOUS', 'LISTED SUITE SIZES' ) ) ), 0, 2, true );
?>
<article class="corp-opportunity">
	<a class="corp-opportunity-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
		<?php if ( has_post_thumbnail( $id ) ) : ?><div class="corp-opportunity-image"><?php echo get_the_post_thumbnail( $id, 'large', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 700px) calc(100vw - 40px), (max-width: 1100px) 44vw, 40vw' ) ); ?></div><?php endif; ?>
		<div class="corp-opportunity-copy">
			<p class="corp-opportunity-tags"><span><?php echo esc_html( aspire_core_block_term_names( $id, 'property_type' ) ); ?></span><span><?php echo esc_html( implode( ' / ', wp_list_pluck( $data['transactions'], 'name' ) ) ); ?></span></p>
			<h3><?php echo esc_html( $data['title'] ); ?></h3>
			<?php if ( $data['locality'] ) : ?><p class="corp-opportunity-locality"><?php echo esc_html( $data['locality'] ); ?></p><?php endif; ?>
			<div class="corp-opportunity-bottom"><dl><?php foreach ( $metrics as $label => $value ) : ?><div><dt><?php echo esc_html( ucwords( strtolower( $label ) ) ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?></dl><span class="corp-opportunity-arrow" aria-hidden="true">↗</span></div>
		</div>
	</a>
</article>
