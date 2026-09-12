<?php
/**
 * Title: Aspire Commercial — Property Management
 * Slug: aspirecre/phase2-management
 * Categories: aspirecre
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"section","anchor":"property-management","metadata":{"name":"Property Management"},"className":"aspire-corporate-section corp-management","layout":{"type":"default"}} -->
<section id="property-management" class="wp-block-group aspire-corporate-section corp-management">
<!-- wp:group {"className":"corp-container","layout":{"type":"default"}} -->
<div class="wp-block-group corp-container">
<!-- wp:group {"className":"corp-editorial-split","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-split">
<!-- wp:image {"id":113,"sizeSlug":"full","linkDestination":"none","className":"corp-management-image"} -->
<figure class="wp-block-image size-full corp-management-image"><img src="<?php echo esc_url( wp_get_attachment_url( 113 ) ); ?>" alt="Office building exterior at Chasewood Technology Park" class="wp-image-113"/></figure>
<!-- /wp:image -->
<!-- wp:group {"className":"corp-editorial-copy","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-copy">
<!-- wp:group {"className":"corp-section-heading","layout":{"type":"default"}} -->
<div class="wp-block-group corp-section-heading">
<!-- wp:paragraph {"className":"corp-eyebrow"} -->
<p class="corp-eyebrow">FOR OWNERS</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Commercial Property Management That Goes Beyond Operations</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"corp-intro"} -->
<p class="corp-intro">From leasing and tenant coordination to day-to-day operations and asset positioning, Aspire helps owners manage commercial property with the broader real estate objective in view.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Operational decisions deserve the same market context and attention as the next lease or transaction.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'contact' ) ); ?>">Discuss your property with Aspire →</a></p>
<!-- /wp:paragraph -->
<?php $phase2_management_page = get_page_by_path( 'services/property-management' ); if ( $phase2_management_page && 'publish' === $phase2_management_page->post_status ) : ?>
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'property-management' ) ); ?>">Explore Property Management →</a></p>
<!-- /wp:paragraph -->
<?php endif; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->

