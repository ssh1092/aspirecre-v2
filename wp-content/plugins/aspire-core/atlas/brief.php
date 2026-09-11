<?php defined('ABSPATH') || exit; ?>
<section class="atlas-cre-brief" hidden aria-labelledby="<?php echo esc_attr($uid); ?>brief-title" data-submit-url="<?php echo esc_url(rest_url('aspire/v1/atlas/inquiries')); ?>" data-submit-nonce="<?php echo esc_attr(wp_create_nonce('aspire_atlas_submit')); ?>" data-rest-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
 <header class="atlas-brief-header"><button type="button" class="atlas-brief-close">← Close Brief</button><p>CREATE MY REAL ESTATE BRIEF <span class="atlas-brief-progress"></span></p><h2 id="<?php echo esc_attr($uid); ?>brief-title" tabindex="-1"></h2></header>
 <div class="atlas-brief-body" tabindex="0" role="region" aria-label="Real estate brief content"><div class="atlas-brief-steps"></div><div class="atlas-brief-stage"></div></div>
 <div class="atlas-brief-controls"><button type="button" class="atlas-brief-back">← Back</button><button type="button" class="atlas-brief-next">Continue →</button></div>
 <p class="atlas-brief-status atlas-sr-only" role="status" aria-atomic="true"></p>
</section>
