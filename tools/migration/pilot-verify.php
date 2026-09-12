<?php
/** Read-only pilot validation, supplied the pre-pilot SQL snapshot. */
if(PHP_SAPI!=='cli')exit;
require '/var/www/html/wp-load.php';
$before=json_decode(file_get_contents('/tmp/aspire-pilot-before.json'),true);
$checks=0;
function check_pilot($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
$ids=get_posts(array('post_type'=>'property','post_status'=>'any','fields'=>'ids','numberposts'=>-1));check_pilot(count($ids)===6,'Exactly six Properties');
foreach($before['properties'] as $post){
 $id=(int)$post['ID'];foreach(array('post_title','post_name','post_status','post_content','post_excerpt') as $field)check_pilot(get_post_field($field,$id)===$post[$field],"Existing $id $field unchanged");
 foreach($before['meta'] as $m){if((int)$m['post_id']!==$id||!str_starts_with($m['meta_key'],'_aspire_'))continue;check_pilot(get_post_meta($id,$m['meta_key'],true)===maybe_unserialize($m['meta_value']),"Existing $id {$m['meta_key']} unchanged");}
 global $wpdb;$rels=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->term_relationships} WHERE object_id=%d ORDER BY term_taxonomy_id",$id),ARRAY_A);$old=array_values(array_filter($before['relationships'],static fn($x)=>(int)$x['object_id']===$id));check_pilot($rels===$old,"Existing $id taxonomy unchanged");
}
$sources=array();$media=array();$report=array();
foreach($ids as $id){
 $source=get_post_meta($id,Aspire_Property_Intelligence::SOURCE,true);$intel=get_post_meta($id,Aspire_Property_Intelligence::META,true);
 check_pilot(is_array($source)&&!empty($source['manifest_sha256']),'Protected migration provenance');$sources[]=$source['source_url'];
 check_pilot($intel===Aspire_Property_Intelligence::sanitize($intel),'Deterministic sanitized intelligence');
 $gallery=get_post_meta($id,'_aspire_gallery_attachment_ids',true);check_pilot(is_array($gallery)&&count($gallery)===count(array_unique($gallery)),'Unique gallery IDs');
 check_pilot(!in_array(get_post_thumbnail_id($id),$gallery,true),'Featured image excluded from gallery');
 $ordered=array();foreach($source['review_evidence']['gallery_assets'] as $url){
  $matches=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','numberposts'=>2,'fields'=>'ids','meta_query'=>array('relation'=>'OR',array('key'=>'_aspire_legacy_asset_url','value'=>$url),array('key'=>'_aspire_legacy_asset_alias','value'=>$url))));
  check_pilot(count($matches)===1,'Exactly one attachment per source gallery asset');
  if($matches[0]!==get_post_thumbnail_id($id))$ordered[]=$matches[0];
 }
 check_pilot(array_values(array_unique($ordered))===$gallery,'Gallery preserves V4 source order');
 foreach($gallery as $attachment){check_pilot(wp_attachment_is_image($attachment)&&is_file(get_attached_file($attachment)),'Local gallery image');$media[]=$attachment;}
 $pdf=(int)get_post_meta($id,'_aspire_brochure_attachment_id',true);
 if($id!==122){check_pilot($pdf&&get_post_mime_type($pdf)==='application/pdf'&&file_get_contents(get_attached_file($pdf),false,null,0,5)==='%PDF-','Local PDF brochure');$media[]=$pdf;}else check_pilot(!$pdf,'FM brochure held privately by user instruction');
 if(!in_array($id,array(119,120,121,122),true)){
  check_pilot(get_post_status($id)==='draft','New property remains draft');check_pilot(!metadata_exists('post',$id,'_aspire_listing_status'),'New listing status remains unset');
  $terms=wp_get_object_terms($id,'transaction_type',array('fields'=>'names'));sort($terms);
  if($source['migration_id']==='aspire-081'){check_pilot($terms===array('For Lease','For Sale'),'Sienna has both transaction terms');check_pilot($source['primary_image_review_required'],'Sienna image review flag');check_pilot(!get_post_meta($id,'_aspire_suites',true),'Sienna has no invented suites');}
  else{check_pilot($terms===array('For Lease'),'Sugarwell transaction');$s=get_post_meta($id,'_aspire_suites',true);check_pilot(count($s)===4,'Sugarwell four normalized suites');foreach($s as $suite)check_pilot($suite['availability_status']==='','No inferred suite status');}
 }
 check_pilot(!get_post_meta($id,'_aspire_listing_broker_id',false),'No broker assignment');
 $report[]=array('post_id'=>$id,'featured_image_id'=>get_post_thumbnail_id($id),'gallery_count'=>count($gallery),'brochure_id'=>$pdf,'media_urls'=>array_map('wp_get_attachment_url',array_slice($gallery,0,1)),'brochure_url'=>$pdf?wp_get_attachment_url($pdf):null);
}
check_pilot(count($sources)===count(array_unique($sources)),'No duplicate source URLs');
$registered=get_registered_meta_keys('post','property');foreach(array(Aspire_Property_Intelligence::META,Aspire_Property_Intelligence::SOURCE) as $key)check_pilot($registered[$key]['show_in_rest']===false,'Meta excluded from REST');
wp_set_current_user(0);$response=rest_do_request(new WP_REST_Request('GET','/wp/v2/property'));$public=wp_json_encode($response->get_data());check_pilot(!str_contains($public,'_aspire_migration')&&!str_contains($public,'contact_candidates'),'No migration contacts in public REST');
$atlas=Aspire_Atlas::collection();check_pilot(count($atlas['features'])===4,'Drafts excluded from Atlas');
foreach($atlas['features'] as $f)if($f['id']===122)check_pilot($f['properties']['pricing']['salePrice']===null&&$f['properties']['pricing']['priceDisplay']===null,'FM suppression remains active');
check_pilot(count(get_posts(array('post_type'=>'team_member','post_status'=>'any','numberposts'=>-1,'fields'=>'ids')))===0,'No Team Members created');
$provider=new WP_Sitemaps_Posts();$sitemap=wp_json_encode($provider->get_url_list(1,'property'));
foreach(array('Sugarwell','Sienna') as $name){
 $q=new WP_Query(array('post_type'=>'property','post_status'=>'publish','s'=>$name));check_pilot($q->post_count===0,'Draft excluded from public search');
}
check_pilot(!str_contains($sitemap,'14248-bellaire')&&!str_contains($sitemap,'sienna-park'),'Drafts excluded from sitemap');
echo wp_json_encode(array('checks'=>$checks,'properties'=>$report),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
