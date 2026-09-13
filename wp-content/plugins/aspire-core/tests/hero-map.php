<?php
/** Read-only checks of the live corporate hero and map-card payload. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks=0;
$check=static function($ok,$message)use(&$checks){if(!$ok)throw new RuntimeException($message);$checks++;};
$home=get_post(74);
$check('publish'===$home->post_status&&'Home'===$home->post_title&&74===(int)get_option('page_on_front')&&'page'===get_option('show_on_front'),'Published Home 74 is static front page');
$pattern='/<!-- wp:aspire\/atlas (\{.*?\}) \/-->/s';
$before=get_post_meta(74,'_aspirecre_before_hero_conversion',true);
$check($before&&preg_replace($pattern,'[ATLAS]',$before)===preg_replace($pattern,'[ATLAS]',$home->post_content),'All non-Atlas homepage content remains byte-for-byte unchanged');
preg_match($pattern,$home->post_content,$match);$attrs=json_decode($match[1],true);
$html=Aspire_Atlas::render($attrs);$doc=new DOMDocument();libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);libxml_clear_errors();$xpath=new DOMXPath($doc);
$check(1===$xpath->query('//h1')->length&&'Houston Commercial Real Estate, Made Clear.'===$xpath->query('//h1')->item(0)->textContent,'Exact single H1');
$opening=$xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," atlas-opening ")]')->item(0);
$check(!str_contains($opening->textContent,'Real Estate Brief')&&!str_contains($opening->textContent,'Explore Houston. Find your next move.'),'Opening removes workflow and product-first copy');
$check(str_contains($opening->textContent,'TELL US WHAT YOU NEED')&&str_contains($opening->textContent,'Prefer to talk first?'),'Primary and secondary conversion copy');
$check(1===$xpath->query('//textarea[@maxlength="240" and not(@readonly)]')->length,'One editable contextual input with existing payload limit');
$check(4===$xpath->query('//button[@data-intent and @aria-pressed="false"]')->length,'Four accessible objective selectors');
$check(str_contains($opening->textContent,'Manage a Property')&&str_contains($opening->textContent,'Aspire helps tenants, property owners, investors and developers make better commercial real estate decisions across Greater Houston.'),'Current corporate language server rendered');
$data=Aspire_Atlas::collection();$check(4===count($data['features']),'Exactly four published mapped properties');
foreach($data['features'] as $feature){
 $p=$feature['properties'];$check('publish'===get_post_status($feature['id']),'Only published records');
 $check(!in_array($feature['id'],array(351,392),true),'Pilot drafts excluded');
 $check($p['permalink']===get_permalink($feature['id']),'Actual property permalink');
 if($p['image']){
  $preview=$p['image']['preview'];$check($preview&&$preview['width']<=300,'Map card uses a WordPress thumbnail');
  foreach(explode(', ',$preview['srcset']) as $candidate){preg_match('/ (\d+)w$/',$candidate,$size);$check(isset($size[1])&&(int)$size[1]<=800,'Preview srcset caps large image downloads');}
 }
 if(122===$feature['id'])$check(null===$p['pricing']['salePrice']&&null===$p['pricing']['priceDisplay']&&2.21===$p['metrics']['lotAcres'],'FM1093 price suppressed and safe acreage retained');
}
echo "$checks hero/map checks passed.\n";
