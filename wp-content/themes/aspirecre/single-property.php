<?php
/** Focused property experience. Core supplies the read-only presentation. */
defined('ABSPATH') || exit;
get_header();
while(have_posts()):the_post();$d=Aspire_Property_Dossier::data(get_the_ID());$w=Aspire_Property_Dossier::workspace($d);
?>
<main id="main-content" class="property-dossier"><article aria-labelledby="property-title">
 <div class="dossier-breadcrumb"><a href="<?php echo esc_url(home_url('/properties/')); ?>">← ALL PROPERTIES</a><span>ASPIRE COMMERCIAL</span></div>
 <div class="property-stage">
 <?php get_template_part('template-parts/property/media',null,array('data'=>$d)); ?>
 <header class="property-rail">
  <p class="dossier-eyebrow"><?php echo esc_html(implode(' · ',array_filter(array_merge(array($d['type']->name??''),array_column($d['transactions'],'name'))))); ?></p>
  <h1 id="property-title"><?php echo esc_html($d['title']); ?></h1><p class="dossier-locality"><?php echo esc_html($d['locality']); ?></p>
  <dl class="property-metrics"><?php foreach($w['metrics'] as $metric): ?><div><dt><?php echo esc_html($metric['label']); ?></dt><dd><?php echo esc_html($metric['value']); ?></dd></div><?php endforeach; ?></dl>
  <div class="dossier-actions"><a class="dossier-button" href="tel:+17139332001">TALK TO ASPIRE <span aria-hidden="true">↗</span></a><?php if($d['brochure']): ?><a class="dossier-text-link" href="<?php echo esc_url(wp_get_attachment_url($d['brochure'])); ?>" target="_blank" rel="noopener">VIEW BROCHURE ↗</a><?php endif; ?></div>
 </header></div>
 <div class="property-modes" data-property-tabs>
  <nav class="dossier-nav" role="tablist" aria-label="Property modes"><?php foreach(array('property'=>'Property','space'=>'Space','location'=>'Location','due-diligence'=>'Due diligence') as $key=>$label): ?><a role="tab" id="tab-<?php echo $key; ?>" href="#<?php echo $key; ?>" aria-controls="<?php echo $key; ?>" aria-selected="<?php echo $key==='property'?'true':'false'; ?>" tabindex="<?php echo $key==='property'?'0':'-1'; ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav>
  <?php foreach(array('property','space','location','due-diligence') as $part): ?><section id="<?php echo $part; ?>" class="property-mode" role="tabpanel" aria-labelledby="tab-<?php echo $part; ?>" tabindex="0" <?php if($part!=='property')echo 'hidden'; ?>><?php get_template_part('template-parts/property/'.$part,null,array('data'=>$d,'workspace'=>$w)); ?></section><?php endforeach; ?>
 </div>
 <section class="dossier-contact"><div><h2>INTERESTED IN THIS PROPERTY?</h2><p>Talk with Aspire about availability or the details you want to confirm.</p></div><a class="dossier-text-link" href="tel:+17139332001">TALK TO ASPIRE ↗</a></section>
</article></main>
<?php endwhile;get_footer(); ?>
