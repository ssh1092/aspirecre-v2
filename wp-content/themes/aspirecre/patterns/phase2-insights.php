<?php
/**
 * Title: Aspire Commercial — Insights
 * Slug: aspirecre/phase2-insights
 * Categories: aspirecre
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"section","anchor":"insights","metadata":{"name":"Insights"},"className":"aspire-corporate-section corp-insights","layout":{"type":"default"}} -->
<section id="insights" class="wp-block-group aspire-corporate-section corp-insights">
<!-- wp:group {"className":"corp-container","layout":{"type":"default"}} -->
<div class="wp-block-group corp-container">
<!-- wp:group {"className":"corp-editorial-split","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-split">
<!-- wp:group {"className":"corp-section-heading","layout":{"type":"default"}} -->
<div class="wp-block-group corp-section-heading">
<!-- wp:paragraph {"className":"corp-eyebrow"} -->
<p class="corp-eyebrow">PERSPECTIVE FROM ASPIRE</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Insights From Houston Commercial Real Estate</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"corp-editorial-copy","layout":{"type":"default"}} -->
<div class="wp-block-group corp-editorial-copy">
<!-- wp:paragraph -->
<p>Questions about leasing, ownership or the market? Bring them to an Aspire advisor.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'contact' ) ); ?>">Start a real estate conversation →</a></p>
<!-- /wp:paragraph -->
<?php $phase2_insights_page = get_page_by_path( 'insights' ); if ( $phase2_insights_page && 'publish' === $phase2_insights_page->post_status ) : ?>
<!-- wp:paragraph {"className":"corp-text-link"} -->
<p class="corp-text-link"><a href="<?php echo esc_url( aspirecre_home_destination( 'insights' ) ); ?>">View Insights →</a></p>
<!-- /wp:paragraph -->
<?php endif; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"corp-insights-query"} -->
<div class="wp-block-query corp-insights-query">
<!-- wp:post-template {"className":"corp-insights-list"} -->
<!-- wp:post-featured-image {"isLink":true,"sizeSlug":"medium_large","aspectRatio":"3/2"} /-->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt {"moreText":"","excerptLength":24} /-->

<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->

