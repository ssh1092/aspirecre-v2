<?php
/** Local CLI contract tests. Fixture posts are rolled back; real records stay intact. */
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/post.php';
$checks=0;
function atlas_check($ok,$label){global $checks;if(!$ok){throw new RuntimeException($label);}++$checks;echo "PASS: $label\n";}
wp_set_current_user(0);
$response=rest_do_request(new WP_REST_Request('GET','/aspire/v1/atlas/properties'));
atlas_check($response->get_status()===200,'Public REST endpoint');
$data=$response->get_data();$ids=array_column($data['features'],'id');
atlas_check($data['type']==='FeatureCollection'&&count($ids)===4&&count(array_unique($ids))===4,'Four unique mapped properties');
foreach($data['features'] as $f){
 $p=$f['properties'];
 atlas_check($f['geometry']['type']==='Point'&&count($f['geometry']['coordinates'])===2,'GeoJSON point uses longitude/latitude');
 atlas_check($p['listingStatus']==='available'&&get_post_status($f['id'])==='publish','Only published available properties');
 atlas_check(!str_contains(wp_json_encode($p),'_aspire_')&&!isset($p['listing_broker_id']),'No raw internal meta exposed');
 atlas_check($p['image']&&str_starts_with($p['image']['url'],home_url()),'Local structured image');
 if(str_contains($p['slug'],'21617')){
  atlas_check($p['pricing']===array('leaseRateDisplay'=>null,'priceDisplay'=>null,'salePrice'=>null),'Conflicting FM 1093 pricing suppressed in Atlas');
  atlas_check((float)get_post_meta($f['id'],'_aspire_sale_price',true)===3410000.0 && get_post_meta($f['id'],'_aspire_price_display',true)==='$3,410,000','Stored pricing unchanged');
 }
 if(in_array($f['id'],get_option('aspire_atlas_provisional_coordinate_ids'),true)){atlas_check($p['coordinateStatus']==='provisional','Provisional coordinates labelled');}
}
$home=get_post((int)get_option('page_on_front'));
atlas_check($home->post_title==='Home'&&get_option('show_on_front')==='page','Same Home is static front page');
atlas_check(has_block('aspire/atlas',$home)&&!has_block('core/shortcode',$home),'Atlas is a native block, no shortcode');
atlas_check(use_block_editor_for_post($home),'Gutenberg remains enabled');
atlas_check((bool)get_post_meta($home->ID,'_aspire_before_atlas_task1',true),'Conventional content recoverable');
$type=WP_Block_Type_Registry::get_instance()->get_registered('aspire/atlas');
atlas_check($type&&$type->api_version===3&&$type->category==='aspirecre','API v3 block registered in AspireCRE');
atlas_check(count($type->attributes)>=4&&isset($type->attributes['enableBrief']),'Editorial block controls registered');
$fixtures=array();$wpdb->query('START TRANSACTION');
try{
 foreach(array('draft','private','publish','publish','publish') as $i=>$status){
  $id=wp_insert_post(array('post_type'=>'property','post_status'=>$status,'post_title'=>'Atlas temporary contract fixture '.$i));$fixtures[]=$id;
  update_post_meta($id,'_aspire_listing_status',$i===2?'sold':'available');
  if($i!==3){update_post_meta($id,'_aspire_latitude',29.8);update_post_meta($id,'_aspire_longitude',-95.4);}
  if($i===4){$wpdb->update($wpdb->postmeta,array('meta_value'=>'999'),array('post_id'=>$id,'meta_key'=>'_aspire_latitude'));wp_cache_delete($id,'post_meta');}
 }
 atlas_check(array_column(Aspire_Atlas::collection()['features'],'id')===$ids,'Draft/private/sold/missing/out-of-range coordinates excluded');
 // Equator/prime-meridian are valid numbers, not missing values.
 update_post_meta($fixtures[4],'_aspire_latitude',0);update_post_meta($fixtures[4],'_aspire_longitude',0);
 atlas_check(count(Aspire_Atlas::collection()['features'])===5,'Numeric zero coordinates accepted');
}finally{$wpdb->query('ROLLBACK');foreach($fixtures as $id){clean_post_cache($id);}}
$frontend=Aspire_Atlas::render(array());
atlas_check(str_contains($frontend,'class="atlas-find" hidden'),'Discovery controls hidden in opening state');
atlas_check(str_contains($frontend,'data-filter="transactionType"') && preg_match('/value="for-lease"\s+selected=/', $frontend),'Default For Lease uses actual taxonomy slug');
foreach(get_terms(array('taxonomy'=>'property_type','hide_empty'=>false)) as $term){
 atlas_check(str_contains($frontend,'value="'.esc_attr($term->slug).'"'), 'Filter contains registered property type: '.$term->slug);
}
atlas_check(!str_contains($frontend,'value="ground-lease"'),'Transaction controls limited to approved supported choices');
$admin=get_users(array('role'=>'administrator','number'=>1));wp_set_current_user($admin[0]->ID);
define('REST_REQUEST',true);
$request=new WP_REST_Request('GET','/wp/v2/block-renderer/aspire/atlas');$request->set_param('context','edit');
$request->set_param('attributes',array('headline'=>'Custom editorial headline','showNaturalLanguage'=>false,'enableBrief'=>true));
$preview=rest_do_request($request);$html=$preview->get_data()['rendered']??'';
atlas_check($preview->get_status()===200&&str_contains($html,'Custom editorial headline'),'Editor REST preview accepts headline setting');
atlas_check(str_contains($html,'4 ASPIRE OPPORTUNITIES')&&str_contains($html,'Interactive map renders on the frontend.'),'Useful static editor preview');
atlas_check(!str_contains($html,'data-atlas')&&!str_contains($html,'class="atlas-input"')&&str_contains($html,'Build My Brief'),'Editor no map initialization; toggles affect preview');
atlas_check(!str_contains($html,'class="atlas-find"'),'Editor preview excludes frontend-only discovery controls');
echo "SUCCESS: $checks Atlas checks. Temporary fixtures rolled back.\n";
