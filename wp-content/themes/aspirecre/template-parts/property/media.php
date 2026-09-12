<?php defined('ABSPATH') || exit;$d=$args['data'];$photos=$d['photos']; ?>
<?php if($photos): ?>
<div class="dossier-media-stage">
 <a class="property-stage-image" href="<?php echo esc_url(wp_get_attachment_url($photos[0])); ?>" data-gallery-open="current" aria-label="Open property photos"><?php echo wp_get_attachment_image($photos[0],'1536x1536',false,array('loading'=>'eager','fetchpriority'=>'high','sizes'=>'(max-width: 760px) 100vw, 68vw','data-stage-image'=>'','alt'=>get_post_meta($photos[0],'_wp_attachment_image_alt',true)?:$d['title'].' — property view 1')); ?></a>
 <div class="dossier-media-actions"><div><button type="button" data-stage-prev aria-label="Previous stage photo">←</button><span data-stage-count aria-live="polite">1 / <?php echo count($photos); ?></span><button type="button" data-stage-next aria-label="Next stage photo">→</button></div><a href="<?php echo esc_url(wp_get_attachment_url($photos[0])); ?>" data-gallery-open="current">VIEW ALL PHOTOS ↗</a></div>
</div>
<dialog class="dossier-viewer" aria-labelledby="dossier-viewer-title">
 <header><p id="dossier-viewer-title"><?php echo esc_html($d['title']); ?></p><button type="button" data-gallery-close autofocus aria-label="Close gallery">CLOSE <span aria-hidden="true">×</span></button></header>
 <div class="dossier-viewer-stage"><button type="button" data-gallery-prev aria-label="Previous photo">←</button><figure><img alt="" data-gallery-image><figcaption data-gallery-caption></figcaption></figure><button type="button" data-gallery-next aria-label="Next photo">→</button></div>
 <p class="dossier-viewer-count" data-gallery-count aria-live="polite"></p>
 <script type="application/json" data-gallery-data><?php $items=array();foreach($photos as $i=>$image)$items[]=array('src'=>wp_get_attachment_image_url($image,'2048x2048'),'srcset'=>wp_get_attachment_image_srcset($image,'2048x2048')?:'','alt'=>get_post_meta($image,'_wp_attachment_image_alt',true)?:$d['title'].' — property view '.($i+1));echo wp_json_encode($items,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
</dialog>
<?php else: ?><div class="property-no-photo">Property photography is not available.</div><?php endif; ?>
