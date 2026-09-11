<?php defined( 'ABSPATH' ) || exit; $properties = aspirecre_featured_properties(); ?>
	<?php if ( $properties ) : ?><div class="property-grid">
		<?php foreach ( $properties as $property ) : get_template_part( 'template-parts/property-card', null, array( 'property' => $property ) ); endforeach; ?>
	</div><?php else : ?><div class="empty-state"><p>Property listings will appear here when available.</p><?php if ( current_user_can( 'edit_posts' ) ) : ?><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=property' ) ); ?>">Manage properties <span aria-hidden="true">→</span></a><?php endif; ?></div><?php endif; ?>
