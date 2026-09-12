<?php defined('ABSPATH') || exit;$d=$args['data'];$photos=$d['photos']; ?>
<?php if($photos): ?>
<div class="dossier-media-stage">
 <div class="dossier-mosaic dossier-mosaic-<?php echo min(3,count($photos)); ?>">
 <?php foreach(array_slice($photos,0,3) as $i=>$image): ?>
 <a href="<?php echo esc_url(wp_get_attachment_url($image)); ?>" data-gallery-open="<?php echo $i; ?>" aria-label="<?php echo esc_attr('Open photo '.($i+1).' of '.$d['title']); ?>"><?php echo wp_get_attachment_image($image,$i===0?'1536x1536':'large',false,array('loading'=>$i===0?'eager':'lazy','fetchpriority'=>$i===0?'high':'auto','sizes'=>$i===0?'(max-width: 800px) 100vw, 66vw':'(max-width: 800px) 50vw, 33vw','alt'=>get_post_meta($image,'_wp_attachment_image_alt',true)?:$d['title'].' — property view '.($i+1))); ?></a>
 <?php endforeach; ?>
 </div>
 <div class="dossier-media-actions"><a href="<?php echo esc_url(wp_get_attachment_url($photos[0])); ?>" data-gallery-open="0">▦ &nbsp; VIEW ALL PHOTOS (<?php echo count($photos); ?>)</a><?php if($d['brochure']): ?><a href="<?php echo esc_url(wp_get_attachment_url($d['brochure'])); ?>" target="_blank" rel="noopener">VIEW BROCHURE ↗</a><?php endif; ?></div>
</div>
<dialog class="dossier-viewer" aria-labelledby="dossier-viewer-title">
 <header><p id="dossier-viewer-title"><?php echo esc_html($d['title']); ?></p><button type="button" data-gallery-close autofocus aria-label="Close gallery">CLOSE <span aria-hidden="true">×</span></button></header>
 <div class="dossier-viewer-stage"><button type="button" data-gallery-prev aria-label="Previous photo">←</button><figure><img alt="" data-gallery-image><figcaption data-gallery-caption></figcaption></figure><button type="button" data-gallery-next aria-label="Next photo">→</button></div>
 <p class="dossier-viewer-count" data-gallery-count aria-live="polite"></p>
 <script type="application/json" data-gallery-data><?php $items=array();foreach($photos as $i=>$image)$items[]=array('src'=>wp_get_attachment_image_url($image,'2048x2048'),'srcset'=>wp_get_attachment_image_srcset($image,'2048x2048')?:'','alt'=>get_post_meta($image,'_wp_attachment_image_alt',true)?:$d['title'].' — property view '.($i+1));echo wp_json_encode($items,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
</dialog>
<?php endif; ?>
