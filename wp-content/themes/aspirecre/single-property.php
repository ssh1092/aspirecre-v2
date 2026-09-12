<?php
/** Desktop Property Intelligence Dossier. The theme renders Core's safe model. */
defined('ABSPATH') || exit;
get_header();
while(have_posts()):the_post();$d=Aspire_Property_Dossier::data(get_the_ID());
$sections=array('overview'=>'Overview');if($d['lens']['standouts'])$sections['lens']='Aspire Lens';if($d['suites']||$d['lens']['availability_contexts']||$d['lens']['offering_contexts'])$sections['availability']='Availability';$sections['details']='Details';if($d['address']||$d['locality']||$d['map'])$sections['location']='Location';if($d['gallery'])$sections['gallery']='Gallery';$sections['contact']='Contact';
?>
<main id="main-content" class="property-dossier"><article aria-labelledby="property-title">
 <div class="dossier-breadcrumb"><a href="<?php echo esc_url(home_url('/properties/')); ?>">← ALL PROPERTIES</a><span>ASPIRE COMMERCIAL / PROPERTY INTELLIGENCE</span></div>
 <?php get_template_part('template-parts/property/media',null,array('data'=>$d)); ?>
 <header id="property-overview" class="dossier-identity">
  <div><p class="dossier-eyebrow"><?php echo esc_html(implode(' · ',array_filter(array_merge(array($d['type']->name??''),array_column($d['transactions'],'name'))))); ?></p><h1 id="property-title"><?php echo esc_html($d['title']); ?></h1><p class="dossier-locality"><?php echo esc_html($d['locality']); ?></p></div>
  <div class="dossier-actions"><a class="dossier-button" href="tel:+17139332001">TALK TO ASPIRE <span aria-hidden="true">↗</span></a><a class="dossier-text-link" href="<?php echo esc_url(home_url('/')); ?>">CREATE MY REAL ESTATE BRIEF →</a><?php if($d['brochure']): ?><a class="dossier-document-link" href="<?php echo esc_url(wp_get_attachment_url($d['brochure'])); ?>" download>DOWNLOAD BROCHURE <span aria-hidden="true">↓</span></a><?php endif; ?></div>
 </header>
 <?php if($d['snapshot']): ?><dl class="dossier-snapshot"><?php foreach($d['snapshot'] as $label=>$value): ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
 <nav class="dossier-nav" aria-label="Property sections"><?php foreach($sections as $key=>$label): ?><a href="#property-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav>
 <?php foreach(array('lens','availability','details','questions','location','gallery','advisors') as $part){get_template_part('template-parts/property/'.$part,null,array('data'=>$d));if($part==='lens')do_action('aspire_property_dossier_after_lens',$d['id']);} ?>
 <section id="property-contact" class="dossier-contact"><div><p class="dossier-eyebrow">YOUR NEXT MOVE</p><h2>Let’s look at the<br>possibilities.</h2><p>Talk with Aspire about availability, pricing or whether this opportunity fits what you're looking for.</p></div><div class="dossier-actions"><a class="dossier-button" href="tel:+17139332001">TALK TO ASPIRE →</a><a class="dossier-text-link" href="<?php echo esc_url(home_url('/')); ?>">CREATE MY REAL ESTATE BRIEF →</a></div></section>
</article></main>
<?php endwhile;get_footer(); ?>
