<?php defined('ABSPATH') || exit; ?>
<section <?php echo get_block_wrapper_attributes(array('class'=>'aspire-atlas'.($editor?' aspire-atlas-preview':''))); ?> aria-labelledby="<?php echo esc_attr($uid); ?>title" <?php if(!$editor): ?>data-atlas data-worker="<?php echo esc_url(plugins_url('atlas/build/worker.js',ASPIRE_CORE_FILE)); ?>" data-endpoint="<?php echo esc_url(rest_url('aspire/v1/atlas/properties')); ?>" data-tiles="<?php echo esc_url($tiles); ?>" data-glyphs="<?php echo esc_attr($glyphs); ?>"<?php endif; ?>>
 <div class="atlas-map" role="region" aria-label="Interactive Houston commercial real estate map"></div>
 <div class="atlas-shade" aria-hidden="true"></div>
 <?php if(!$editor && is_front_page()): ?>
 <header class="atlas-nav">
  <a class="atlas-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Aspire Commercial home"><?php $logo=get_theme_mod('custom_logo'); if($logo): echo wp_get_attachment_image($logo,'full',false,array('class'=>'custom-logo','alt'=>'Aspire Commercial')); else: ?>Aspire Commercial<?php endif; ?></a>
  <nav aria-label="Atlas navigation"><a class="atlas-properties-nav" href="#<?php echo esc_attr($uid); ?>properties">Properties</a><a href="<?php echo esc_url(home_url('/insights/')); ?>">Insights</a><a href="<?php echo esc_url(home_url('/about/')); ?>">Company</a><a class="atlas-contact" href="<?php echo esc_url(home_url('/contact/')); ?>">Talk to Aspire <span aria-hidden="true">↗</span></a></nav>
 </header>
 <?php endif; ?>
 <div class="atlas-opening">
  <p class="atlas-eyebrow"><span aria-hidden="true"></span> ASPIRE ATLAS</p>
  <h1 id="<?php echo esc_attr($uid); ?>title"><?php echo nl2br(esc_html($headline)); ?></h1>
  <p class="atlas-support"><?php echo nl2br(esc_html(str_replace('. ', ".\n", $support))); ?></p>
  <p class="atlas-prompt">What are you looking to do?</p>
  <div class="atlas-intents">
   <?php foreach(array('Find Space'=>'Lease commercial space','Buy or Invest'=>'Explore investment opportunities','Lease or Sell My Property'=>'Get a market perspective','Manage an Asset'=>'Expert support for better performance') as $title=>$description): ?>
    <button type="button" class="atlas-intent" data-intent="<?php echo esc_attr(array('Find Space'=>'find-space','Buy or Invest'=>'invest','Lease or Sell My Property'=>'owner-disposition','Manage an Asset'=>'manage-asset')[$title]); ?>" <?php if($title==='Find Space'): ?>data-find-space<?php endif; ?> aria-pressed="false"><span><strong><?php echo esc_html($title); ?></strong><small><?php echo esc_html($description); ?></small></span><span aria-hidden="true">↗</span></button>
   <?php endforeach; ?>
  </div>
  <?php if($natural): ?><label class="atlas-input-label" for="<?php echo esc_attr($uid); ?>input">Describe what you're looking for…</label><input id="<?php echo esc_attr($uid); ?>input" class="atlas-input" type="text" placeholder="Describe what you're looking for…" readonly aria-describedby="<?php echo esc_attr($uid); ?>example"><p class="atlas-command-example" id="<?php echo esc_attr($uid); ?>example">10,000–20,000 SF industrial space in West Houston</p><?php endif; ?>
  <?php if($brief): ?><button type="button" class="atlas-brief">Build My Brief <span aria-hidden="true">↗</span></button><?php endif; ?>
  <?php if($editor): ?><p class="atlas-scope">Interactive map renders on the frontend.</p><?php endif; ?>
  <p class="atlas-intent-status atlas-sr-only" role="status"></p>
 </div>
 <?php if(!$editor): require __DIR__.'/find-space.php'; require __DIR__.'/property-focus.php'; require __DIR__.'/guided.php'; require __DIR__.'/brief.php'; endif; ?>
 <div class="atlas-map-meta">
  <p class="atlas-count" aria-live="polite"><?php echo esc_html($count); ?> ASPIRE OPPORTUNITIES</p>
  <p class="atlas-data-status atlas-sr-only" role="status"><?php if(!$editor): ?>Loading property data…<?php endif; ?></p>
  <p class="atlas-map-status atlas-sr-only" role="status"><?php if(!$editor): ?>Loading map…<?php endif; ?></p>
  <a class="atlas-fallback-link" href="#<?php echo esc_attr($uid); ?>properties" aria-controls="<?php echo esc_attr($uid); ?>properties" aria-expanded="false">View Properties ↗</a>
  <div class="atlas-fallback-properties" id="<?php echo esc_attr($uid); ?>properties" hidden><p>Mapped properties</p><ul><?php foreach($mapped as $feature): ?><li><a href="<?php echo esc_url($feature['properties']['permalink']); ?>"><?php echo esc_html($feature['properties']['title']); ?></a></li><?php endforeach; ?></ul><?php if(!$mapped): ?><p>No mapped properties are available yet.</p><?php endif; ?></div>
 </div>
 <?php if(!$editor): ?><div class="atlas-selection atlas-keyboard-selection" hidden><label for="<?php echo esc_attr($uid); ?>selection">Select a mapped property</label><select id="<?php echo esc_attr($uid); ?>selection"><option value="">Choose a property</option></select><p class="atlas-selected-label" role="status"></p></div><?php endif; ?>
 <!-- Coordinate verification status remains in the REST data and Task 1 documentation. -->
 <div class="atlas-attribution">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a> · <a href="https://protomaps.com" target="_blank" rel="noopener">Protomaps</a></div>
 <noscript><div class="atlas-noscript"><p>Enable JavaScript to explore the map. Properties:</p><ul><?php foreach($mapped as $feature): ?><li><a href="<?php echo esc_url($feature['properties']['permalink']); ?>"><?php echo esc_html($feature['properties']['title']); ?></a></li><?php endforeach; ?></ul></div></noscript>
</section>
