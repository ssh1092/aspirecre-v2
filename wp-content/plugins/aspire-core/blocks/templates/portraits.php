<?php
/** Real published Aspire people; no generated names, roles, biographies or portraits. */
defined( 'ABSPATH' ) || exit;
$rail_id = wp_unique_id( 'hp-team-' );
?>
<div class="hp-team-rail" data-hp-rail="team" role="group" aria-label="Meet the Aspire team">
	<div class="hp-rail-track" id="<?php echo esc_attr( $rail_id ); ?>" data-hp-rail-track>
		<?php foreach ( $members as $member ) :
			$id = $member->ID;
			$name = get_the_title( $id );
			$role = get_post_meta( $id, '_aspire_job_title', true );
			$bio = get_post_meta( $id, '_aspire_short_bio', true );
			$title_id = $rail_id . '-title-' . $id;
			?>
			<article class="hp-team-person" data-hp-rail-item data-team-id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
				<?php if ( has_post_thumbnail( $id ) ) : ?><figure class="hp-team-portrait"><?php echo get_the_post_thumbnail( $id, 'large', array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 82vw, 44vw' ) ); ?></figure><?php endif; ?>
				<div class="hp-team-copy">
					<h3 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $name ); ?></h3>
					<?php if ( is_string( $role ) && '' !== trim( $role ) ) : ?><p class="hp-team-role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
					<?php if ( is_string( $bio ) && '' !== trim( $bio ) ) : ?><p class="hp-team-bio"><?php echo esc_html( $bio ); ?></p><?php endif; ?>
					<a class="hp-team-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>" aria-label="<?php echo esc_attr( 'Meet ' . $name . ', view profile' ); ?>">Meet <?php echo esc_html( $name ); ?> <span aria-hidden="true">↗</span></a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="hp-rail-controls" data-hp-rail-controls hidden>
		<button type="button" data-hp-rail-prev aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Previous team member"><span aria-hidden="true">←</span></button>
		<output data-hp-rail-count aria-live="polite" aria-atomic="true">1 / <?php echo esc_html( count( $members ) ); ?></output>
		<button type="button" data-hp-rail-next aria-controls="<?php echo esc_attr( $rail_id ); ?>" aria-label="Next team member"><span aria-hidden="true">→</span></button>
	</div>
</div>
