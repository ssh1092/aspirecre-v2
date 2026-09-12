<?php defined( 'ABSPATH' ) || exit; ?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'hp-field-rail', 'role' => 'group', 'aria-label' => 'In the field: Aspire photographs and activity' ) ); ?> data-hp-rail="field" data-feed-source="<?php echo esc_attr( $source ); ?>">
	<p class="hp-field-source"><?php echo 'instagram' === $source ? 'From Aspire Commercial on Instagram' : 'Curated photography from Aspire’s property collection'; ?></p>
	<div class="hp-rail-track" data-hp-rail-track>
	<?php foreach ( $items as $position => $item ) : ?>
		<article class="hp-field-item<?php echo 'VIDEO' === $item['media_type'] ? ' hp-field-video' : ''; ?>" data-hp-rail-item data-contact-number="<?php echo esc_attr( sprintf( '%02d', $position + 1 ) ); ?>">
			<a href="<?php echo esc_url( $item['permalink'] ); ?>"<?php if ( 'instagram' === $source ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
				<figure class="hp-field-image"><?php if ( ! empty( $item['attachment_id'] ) ) : echo wp_get_attachment_image( $item['attachment_id'], 'large', false, array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 700px) 80vw, 35vw' ) ); else : ?><img src="<?php echo esc_url( $item['image_url'] ); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?></figure>
				<p class="hp-field-caption"><?php echo esc_html( wp_trim_words( $item['caption'], 24, '…' ) ?: ( 'VIDEO' === $item['media_type'] ? 'Watch Aspire on Instagram' : 'View Aspire on Instagram' ) ); ?> <span aria-hidden="true">↗</span></p>
				<?php if ( $item['timestamp'] ) : ?><time datetime="<?php echo esc_attr( $item['timestamp'] ); ?>"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $item['timestamp'] ) ) ); ?></time><?php endif; ?>
			</a>
		</article>
	<?php endforeach; ?>
	</div>
	<div class="hp-rail-controls" data-hp-rail-controls hidden><button type="button" data-hp-rail-prev aria-label="Previous field photograph">←</button><output data-hp-rail-count aria-live="polite"></output><button type="button" data-hp-rail-next aria-label="Next field photograph">→</button></div>
	<p class="hp-field-instagram"><a href="https://www.instagram.com/aspirecre/" target="_blank" rel="noopener noreferrer">Follow Aspire in the field →</a></p>
</div>
