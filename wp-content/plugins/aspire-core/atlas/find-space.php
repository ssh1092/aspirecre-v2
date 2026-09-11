<?php
/** Hidden frontend-only discovery controls. Values come from the registered taxonomy. */
defined('ABSPATH') || exit;
$types = get_terms(array('taxonomy'=>'property_type','hide_empty'=>false));
$transactions = get_terms(array('taxonomy'=>'transaction_type','hide_empty'=>false));
?>
<div class="atlas-find" hidden>
 <div class="atlas-filter-rail" role="region" aria-label="Find Space filters">
  <button class="atlas-explore" type="button">← Explore</button>
  <h2 id="<?php echo esc_attr($uid); ?>find-title" tabindex="-1">FIND SPACE</h2>
  <label>Property Type<select data-filter="propertyType"><option value="">All Property Types</option><?php if(!is_wp_error($types)): foreach($types as $term): ?><option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option><?php endforeach; endif; ?></select></label>
  <label>Transaction<select data-filter="transactionType"><?php if(!is_wp_error($transactions)): foreach($transactions as $term): if(!in_array($term->slug,array('for-lease','for-sale-or-lease'),true)) continue; ?><option value="<?php echo esc_attr($term->slug); ?>" <?php selected($term->slug,'for-lease'); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; endif; ?><option value="">All Transactions</option></select></label>
  <label hidden>Price<select data-filter="price"><option value="">Any Price</option><option value="under1">Under $1M</option><option value="one">$1M–$2.5M</option><option value="two">$2.5M–$5M</option><option value="five">$5M+</option></select></label>
  <label><span class="atlas-size-label">Size</span><select data-filter="size"><option value="">Any Size</option><option value="small">Under 5,000 SF</option><option value="medium">5,000–10,000 SF</option><option value="large">10,000–25,000 SF</option><option value="larger">25,000–50,000 SF</option><option value="largest">50,000+ SF</option></select></label>
  <label>Area<select data-filter="area"><option value="">All Houston</option></select></label>
  <button class="atlas-reset" type="button">Reset</button>
 </div>
 <section class="atlas-results" aria-label="Aspire opportunities" aria-busy="true">
  <div class="atlas-results-heading"><div class="atlas-brief-entry"><p>Turn your search into a clear requirement Aspire can review.</p><button class="atlas-build-brief" type="button">CREATE MY REAL ESTATE BRIEF →</button></div><p class="atlas-result-count" role="status" aria-atomic="true">Loading opportunities…</p><span>HOUSTON & SURROUNDING AREAS</span></div>
  <ul class="atlas-result-list" aria-label="Matching properties"></ul>
  <div class="atlas-empty" hidden><p>No Aspire opportunities currently match these filters.</p><button class="atlas-reset" type="button">Reset filters</button></div>
  <p class="atlas-results-error" hidden>Opportunities could not load. Reload the page to try again.</p>
  <p class="atlas-result-selection atlas-sr-only" role="status"></p>
 </section>
</div>
