<?php defined('ABSPATH') || exit;$d=$args['data'];$w=$args['workspace']; ?>
<div class="property-panel-heading"><h2>Location</h2><address><?php echo esc_html(trim($d['address'].', '.$d['locality'],', ')); ?></address></div>
<?php require WP_PLUGIN_DIR.'/aspire-core/includes/property-map.php'; ?>
<?php if($w['location']): ?><dl class="property-location-facts"><?php foreach($w['location'] as $fact): ?><div><dt><?php echo esc_html($fact['label']); ?></dt><dd><?php echo esc_html($fact['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
