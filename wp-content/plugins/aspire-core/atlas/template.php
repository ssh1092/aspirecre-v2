<?php defined('ABSPATH') || exit; ?>
<section <?php echo get_block_wrapper_attributes(array('class'=>'aspire-atlas'.($editor?' aspire-atlas-preview':''))); ?> aria-labelledby="<?php echo esc_attr($uid); ?>title" <?php if(!$editor): ?>data-atlas data-worker="<?php echo esc_url(plugins_url('atlas/build/worker.js',ASPIRE_CORE_FILE)); ?>" data-endpoint="<?php echo esc_url(rest_url('aspire/v1/atlas/properties')); ?>" data-tiles="<?php echo esc_url($tiles); ?>" data-glyphs="<?php echo esc_attr($glyphs); ?>"<?php endif; ?>>
 <div class="atlas-map" role="region" aria-label="Interactive Houston commercial real estate map"></div>
 <div class="atlas-shade" aria-hidden="true"></div>
 <div class="atlas-opening">
  <p class="atlas-eyebrow"><span aria-hidden="true"></span> ASPIRE ATLAS</p>
  <h1 id="<?php echo esc_attr($uid); ?>title"><?php echo nl2br(esc_html($headline)); ?></h1>
  <p class="atlas-support"><?php echo esc_html($support); ?></p>
  <p class="atlas-prompt">What are you looking to do?</p>
  <div class="atlas-intents">
   <?php foreach(array('Find Space'=>'Lease commercial space','Buy or Invest'=>'Explore investment opportunities','Lease or Sell My Property'=>'Get a market perspective','Manage an Asset'=>'Expert support for better performance') as $title=>$description): ?>
    <button type="button" class="atlas-intent" aria-pressed="false"><span><strong><?php echo esc_html($title); ?></strong><small><?php echo esc_html($description); ?></small></span><span aria-hidden="true">↗</span></button>
   <?php endforeach; ?>
  </div>
  <?php if($natural): ?><label class="atlas-input-label" for="<?php echo esc_attr($uid); ?>input">Describe what you're looking for…</label><input id="<?php echo esc_attr($uid); ?>input" class="atlas-input" type="text" placeholder="Describe what you're looking for…" readonly aria-describedby="<?php echo esc_attr($uid); ?>scope"><?php endif; ?>
  <?php if($brief): ?><button type="button" class="atlas-brief">Build My Brief <span aria-hidden="true">↗</span></button><?php endif; ?>
  <p class="atlas-scope" id="<?php echo esc_attr($uid); ?>scope"><?php echo $editor ? 'Interactive map renders on the frontend.' : 'Prototype · Intent workflows, search and briefs are coming in a later phase.'; ?></p>
  <p class="atlas-intent-status" role="status"></p>
 </div>
 <div class="atlas-map-meta">
  <p class="atlas-count" aria-live="polite"><?php echo esc_html($count); ?> mapped properties</p>
  <p class="atlas-data-status" role="status"><?php if(!$editor): ?>Loading property data…<?php endif; ?></p>
  <p class="atlas-map-status" role="status"><?php echo $editor ? 'Houston · Local basemap' : 'Loading Houston basemap…'; ?></p>
  <a class="atlas-fallback-link" href="#<?php echo esc_attr($uid); ?>properties" aria-controls="<?php echo esc_attr($uid); ?>properties" aria-expanded="false">View Properties ↗</a>
  <div class="atlas-fallback-properties" id="<?php echo esc_attr($uid); ?>properties" hidden><p>Mapped properties</p><ul><?php foreach($mapped as $feature): ?><li><a href="<?php echo esc_url($feature['properties']['permalink']); ?>"><?php echo esc_html($feature['properties']['title']); ?></a></li><?php endforeach; ?></ul><?php if(!$mapped): ?><p>No mapped properties are available yet.</p><?php endif; ?></div>
 </div>
 <?php if(!$editor): ?><div class="atlas-selection" hidden><label for="<?php echo esc_attr($uid); ?>selection">Select a mapped property</label><select id="<?php echo esc_attr($uid); ?>selection"><option value="">Choose a property</option></select><p class="atlas-selected-label" role="status"></p></div><?php endif; ?>
 <p class="atlas-coordinate-note">Prototype locations. Presidio Square and 21617 FM 1093 are provisional and need final visual verification; they are not verified parcel centroids.</p>
 <div class="atlas-attribution">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a> · <a href="https://protomaps.com" target="_blank" rel="noopener">Protomaps</a></div>
 <noscript><div class="atlas-noscript"><p>Enable JavaScript to explore the map. Properties:</p><ul><?php foreach($mapped as $feature): ?><li><a href="<?php echo esc_url($feature['properties']['permalink']); ?>"><?php echo esc_html($feature['properties']['title']); ?></a></li><?php endforeach; ?></ul></div></noscript>
</section>
