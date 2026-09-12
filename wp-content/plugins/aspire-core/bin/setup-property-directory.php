<?php
/** Explicit, idempotent local Page setup. Existing edited directory content is preserved. */
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,4).'/wp-load.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('Local prototype only.');
$page=get_page_by_path('properties',OBJECT,'page');
if(!$page){$existing=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private','pending'),'title'=>'Properties','numberposts'=>1));$page=$existing[0]??null;}
$intro='<!-- wp:group {"className":"property-directory-intro"} --><div class="wp-block-group property-directory-intro"><!-- wp:paragraph {"className":"directory-eyebrow"} --><p class="directory-eyebrow">PROPERTIES</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"65%"} --><div class="wp-block-column" style="flex-basis:65%"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Commercial opportunities across Greater Houston.</h1><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"35%"} --><div class="wp-block-column" style="flex-basis:35%"><!-- wp:paragraph --><p>Explore Aspire\'s current retail, office, industrial and land opportunities across the Houston market.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.esc_url(home_url('/')).'">OPEN ASPIRE ATLAS ↗</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->';
$editorial='<!-- wp:group {"className":"property-directory-editorial"} --><div class="wp-block-group property-directory-editorial"><!-- wp:heading --><h2 class="wp-block-heading">Commercial property across Greater Houston</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Aspire Commercial represents retail, office, industrial, flex and land opportunities across Greater Houston. Explore current availability above or browse by property type.</p><!-- /wp:paragraph --><!-- wp:list --><ul class="wp-block-list">';
foreach(array('retail'=>'Retail Properties in Houston','office'=>'Office Properties in Houston','industrial-flex'=>'Industrial &amp; Flex Space','land'=>'Commercial Land','office-condo'=>'Office Condos') as $type=>$label)$editorial.='<!-- wp:list-item --><li><a href="'.esc_url(add_query_arg('type',$type,home_url('/properties/'))).'">'.$label.' →</a></li><!-- /wp:list-item -->';
$editorial.='</ul><!-- /wp:list --></div><!-- /wp:group -->';
$content=$intro."\n\n<!-- wp:aspire/property-directory /-->\n\n".$editorial;
$data=array('post_title'=>'Properties','post_name'=>'properties','post_type'=>'page','post_status'=>'publish');
if($page){$data['ID']=$page->ID;if(!has_block('aspire/property-directory',$page)){add_post_meta($page->ID,'_aspire_before_property_directory',wp_slash($page->post_content),true);wp_save_post_revision($page->ID);$data['post_content']=$content;}}else $data['post_content']=$content;
$id=$page?wp_update_post(wp_slash($data),true):wp_insert_post(wp_slash($data),true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
update_post_meta($id,'_wp_page_template','templates/property-directory.php');flush_rewrite_rules(false);
echo "Properties Page {$id}: ".get_permalink($id)." Native directory block ready.\n";
