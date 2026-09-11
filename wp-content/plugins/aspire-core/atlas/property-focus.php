<?php defined('ABSPATH') || exit; ?>
<section class="atlas-dossier" aria-labelledby="<?php echo esc_attr($uid); ?>focus-title" hidden>
 <div class="atlas-dossier-back"><button type="button" class="atlas-focus-back">← Back to results</button></div>
 <div class="atlas-dossier-content" tabindex="0" role="region" aria-label="Property details">
  <div class="atlas-dossier-media"></div>
  <div class="atlas-dossier-details">
   <p class="atlas-dossier-eyebrow"></p>
   <h2 id="<?php echo esc_attr($uid); ?>focus-title"></h2>
   <p class="atlas-dossier-location"></p>
   <dl class="atlas-dossier-metrics" aria-label="Property metrics"></dl>
   <section class="atlas-dossier-highlights" aria-labelledby="<?php echo esc_attr($uid); ?>highlights-title" hidden><h3 id="<?php echo esc_attr($uid); ?>highlights-title">PROPERTY HIGHLIGHTS</h3><ul></ul></section>
  </div>
 </div>
 <div class="atlas-dossier-actions"><a class="atlas-focus-full">VIEW FULL PROPERTY <span aria-hidden="true">→</span></a><a class="atlas-focus-contact" href="tel:+17139332001">TALK TO ASPIRE</a></div>
</section>
<p class="atlas-focus-announcement atlas-sr-only" role="status" aria-atomic="true"></p>
<p class="atlas-focus-error" role="status" hidden></p>
