<?php
/** Read-only dossier contracts against local approved records; no fixtures written. */
require dirname(__DIR__,4).'/wp-load.php';
$count=0;
function check($ok,$label){global $count;if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}++$count;echo "PASS: $label\n";}
function fingerprint(){global $wpdb;return hash('sha256',serialize(array($wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type IN ('property','team_member') ORDER BY ID",ARRAY_A),$wpdb->get_results("SELECT m.* FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type IN ('property','team_member') ORDER BY m.meta_id",ARRAY_A))));}
$before=fingerprint();$ids=get_posts(array('post_type'=>'property','post_status'=>'publish','fields'=>'ids','numberposts'=>-1));$models=array();
foreach($ids as $id){
 $d=AspireCRE_Property_Dossier::data($id);$models[$id]=$d;
 check($d['title']!==''&&!str_contains($d['title'],$d['locality']),'Clean display title '.$id);
 $response=wp_remote_get(str_replace('localhost:8080','localhost',get_permalink($id)),array('headers'=>array('Host'=>'localhost:8080'),'redirection'=>0));
 check(!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200,'Existing permalink HTTP 200 '.$id);
 $html=wp_remote_retrieve_body($response);
 check(str_contains($html,'class="property-dossier"')&&str_contains($html,'id="property-title"'),'Single dossier template '.$id);
 check(str_contains($html,'srcset=')&&str_contains($html,'fetchpriority="high"'),'Responsive featured image '.$id);
 check(str_contains($html,'tel:+17139332001')&&str_contains($html,'INTERESTED IN THIS PROPERTY?'),'Genuine actions '.$id);
 check(!str_contains($html,'aspire-atlas-view-js')&&!str_contains($html,'aspire-directory-view-js'),'No application runtime '.$id);
 check(!preg_match('/<link[^>]+href=["\'][^"\']*\/map\.css/',$html),'Map stylesheet not eager '.$id);
 check(str_contains($html,'class="dossier-viewer"')===(bool)$d['photos'],'Gallery follows local attachment data '.$id);
 check(str_contains($html,'VIEW BROCHURE')===(bool)$d['brochure'],'Brochure follows local PDF field '.$id);
 check(str_contains($html,'Assigned Aspire advisors')===(bool)$d['brokers'],'Only assigned advisors render '.$id);
 check(!str_contains($html,'PROPERTY OVERVIEW</h2>'),'Empty editor body omitted '.$id);
 check(str_contains($html,esc_html(Aspire_Property_Dossier::workspace($d)['summary'])),'Core factual synthesis '.$id);
 check(substr_count($html,'role="tabpanel"')===4&&substr_count($html,'tabindex="0" hidden')===3,'Four modes with one initially visible '.$id);
 check(!str_contains($html,'comments-area')&&!str_contains($html,'post-navigation')&&!str_contains($html,'entry-meta'),'No blog UI '.$id);
 check($d['map']&&$d['map']['id']===$id&&count($d['map']['geometry']['coordinates'])===2,'Single property coordinates '.$id);
}
$clay=$models[120];$retail=$models[119];$office=$models[121];$land=$models[122];
check($clay['title']==='16840 Clay Road'&&$clay['hero']['AVAILABLE']==='11,273 SF','Clay Road title and availability');
check($clay['hero']['LEASE RATE']==='$9.75–$11.50/SF/YR'&&$clay['hero']['BUILDING']==='37,309 SF','Clay Road real rate/building');
check($retail['suites'][0]['suite_name']==='Suite F'&&$retail['suites'][0]['square_feet']==='4,361','Atascocita actual suite');
check($retail['suites'][0]['rate']==='$18'&&$retail['suites'][0]['notes']==='$5.84/SF NNN','Atascocita actual suite pricing');
check(!$clay['suites']&&!$office['suites']&&!$land['suites'],'Availability omitted elsewhere');
check($office['groups']['Building']['Year built']==='2014'&&$office['groups']['Building']['Building class']==='B'&&$office['groups']['Building']['Stories']==='3','Office building facts');
check($office['groups']['Parking']['Parking spaces']==='160'&&$office['groups']['Parking']['Parking ratio']==='3.95 spaces per 1,000 SF','Office parking facts');
check($land['hero']===array('SITE'=>'2.21 AC')&&!isset($land['groups']['Pricing']),'Land is acreage-only and price suppressed');
check(get_post_meta(122,'_aspire_sale_price',true)==='3410000','Stored suppressed price preserved');
// Intercept reads only to exercise missing/optional data, without editing any record.
$override=array();$read=static function($value,$id,$key,$single)use(&$override){if($id===120&&array_key_exists($key,$override))return $single?array($override[$key]):$override[$key];return $value;};add_filter('get_post_metadata',$read,10,4);
$override=array('_aspire_latitude'=>'','_aspire_longitude'=>'','_aspire_available_sf'=>'0','_aspire_building_sf'=>'','_aspire_lot_acres'=>'NaN','_aspire_year_built'=>'0','_aspire_lease_rate_display'=>'','_aspire_gallery_attachment_ids'=>array(115,115,999999), '_aspire_brochure_attachment_id'=>115);
$missing=AspireCRE_Property_Dossier::data(120);
check($missing['map']===null,'Missing coordinates omit map configuration');
check(!$missing['hero']&&!$missing['groups'],'Empty/zero/non-numeric details omitted');
check($missing['title']==='16840 Clay Road','Unmapped title suffix fallback');
check($missing['gallery']===array(115),'Gallery accepts actual local image, deduplicates and rejects missing media');
check($missing['brochure']===0,'Non-PDF cannot become brochure');
$override['_aspire_latitude']='91';$override['_aspire_longitude']='-95';check(AspireCRE_Property_Dossier::data(120)['map']===null,'Invalid coordinate range omitted');
$brokers=get_posts(array('post_type'=>'team_member','post_status'=>'publish','fields'=>'ids','numberposts'=>1));
if($brokers){$override['_aspire_listing_broker_id']=array($brokers[0],120,999999);check(AspireCRE_Property_Dossier::data(120)['brokers']===array($brokers[0]),'Only assigned published real Team Members');}
$override['_aspire_suites']=array(array('suite_name'=>'','square_feet'=>0,'rate'=>0,'availability_status'=>''));check(!AspireCRE_Property_Dossier::data(120)['suites'],'Empty suite row omitted');
remove_filter('get_post_metadata',$read,10);
check(fingerprint()===$before,'Property/team titles, slugs, content and metadata unchanged');
echo "SUCCESS: $count read-only property dossier checks.\n";
