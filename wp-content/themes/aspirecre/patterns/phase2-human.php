<?php
/**
 * Title: Aspire Commercial — Human Expertise
 * Slug: aspirecre/phase2-human
 * Categories: aspirecre
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"section","anchor":"human-expertise","metadata":{"name":"Human Expertise"},"className":"aspire-corporate-section corp-human","layout":{"type":"default"}} -->
<section id="human-expertise" class="wp-block-group aspire-corporate-section corp-human">
<!-- wp:group {"className":"corp-container","layout":{"type":"default"}} -->
<div class="wp-block-group corp-container">
<!-- wp:group {"className":"corp-section-heading","layout":{"type":"default"}} -->
<div class="wp-block-group corp-section-heading">
<!-- wp:paragraph {"className":"corp-eyebrow"} -->
<p class="corp-eyebrow">HOUSTON KNOWLEDGE. HUMAN JUDGMENT.</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Local Expertise. Better Real Estate Decisions.</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"corp-editorial-split","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-split">
<!-- wp:group {"className":"corp-editorial-copy","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-copy">
<!-- wp:paragraph {"className":"corp-human-statement"} -->
<p class="corp-human-statement">Technology can surface an opportunity. Experience helps you understand what to do with it.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Aspire Commercial is a Houston-based commercial real estate brokerage and advisory company serving tenants, property owners, investors and developers.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>From the first conversation through representation and execution, an Aspire advisor helps put the property and the decision in context.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'contact' ) ); ?>">Talk to an Aspire advisor →</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:image {"id":114,"sizeSlug":"full","linkDestination":"none","className":"corp-houston-image"} -->
<figure class="wp-block-image size-full corp-houston-image"><img src="<?php echo esc_url( wp_get_attachment_url( 114 ) ); ?>" alt="Downtown Houston skyline" class="wp-image-114"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->
<!-- wp:aspire-core/team-grid {"count":3,"presentation":"editorial","hideWhenEmpty":true} /-->
<?php $phase2_team_page = get_page_by_path( 'our-team' ); if ( $phase2_team_page && 'publish' === $phase2_team_page->post_status ) : ?>
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'team' ) ); ?>">Meet the Aspire Team →</a></p>
<!-- /wp:paragraph -->
<?php endif; ?>
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->

