<?php
/** Pilot only. Invoked by pilot.py with immutable prepared input; dry-run is default. */
if(PHP_SAPI!=='cli')exit;
require '/var/www/html/wp-load.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('Pilot requires local environment');
$payload=json_decode(file_get_contents('/tmp/aspire-pilot-payload.json'),true,512,JSON_THROW_ON_ERROR);
$apply=in_array('--apply',$argv,true);
$allow=array('aspire-040'=>119,'aspire-015'=>120,'aspire-012'=>121,'aspire-021'=>122,'aspire-011'=>0,'aspire-081'=>0);
function pilot_assert($condition,$message){if(!$condition)throw new RuntimeException($message);}
function pilot_meta($id,$key,$value){if(get_post_meta($id,$key,true)!==$value)update_post_meta($id,$key,wp_slash($value));}
function pilot_attachment($url){$ids=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','numberposts'=>2,'fields'=>'ids','meta_query'=>array('relation'=>'OR',array('key'=>'_aspire_legacy_asset_url','value'=>$url),array('key'=>'_aspire_legacy_asset_alias','value'=>$url))));pilot_assert(count($ids)<2,'Duplicate attachment source: '.$url);return $ids[0]??0;}
function pilot_media($asset,$migration_id,$post_id){
 $id=pilot_attachment($asset['url']);if($id)return $id;
 $path='/tmp/aspire-pilot-media/'.$asset['file'];pilot_assert(is_file($path)&&filesize($path)>0,'Missing staged media');
 pilot_assert(hash_file('sha256',$path)===$asset['sha256'],'Staged media checksum differs');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
 if($asset['kind']==='pdf')pilot_assert($mime==='application/pdf'&&file_get_contents($path,false,null,0,5)==='%PDF-','Invalid PDF');
 else pilot_assert(in_array(wp_get_image_mime($path),array('image/jpeg','image/png','image/webp'),true),'Invalid image');
 // Reuse existing curated originals when byte-identical, before creating an attachment.
 foreach(get_posts(array('post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids')) as $existing){$file=get_attached_file($existing);if($file&&is_file($file)&&hash_file('sha256',$file)===$asset['sha256']){$id=$existing;break;}}
 if(!$id){$temp=wp_tempnam($asset['file']);copy($path,$temp);$id=media_handle_sideload(array('name'=>sanitize_file_name($asset['file']),'tmp_name'=>$temp),$post_id,$asset['title']);if(is_wp_error($id)){@unlink($temp);throw new RuntimeException($id->get_error_message());}}
 if(!get_post_meta($id,'_aspire_legacy_asset_url',true))pilot_meta($id,'_aspire_legacy_asset_url',$asset['url']);
 if(!in_array($asset['url'],get_post_meta($id,'_aspire_legacy_asset_alias',false),true))add_post_meta($id,'_aspire_legacy_asset_alias',$asset['url']);pilot_meta($id,'_aspire_legacy_asset_sha256',$asset['sha256']);
 pilot_meta($id,'_aspire_legacy_media_sources',array('migration_id'=>$migration_id,'source_url'=>$asset['source_url'],'normalized_url'=>$asset['url'],'property_id'=>$post_id));
 return $id;
}
$posts=get_posts(array('post_type'=>'property','post_status'=>'any','numberposts'=>-1,'fields'=>'ids'));
pilot_assert(count($posts)>=4&&count($posts)<=6,'Unexpected Property count');
foreach($posts as $id){
 if(in_array($id,array(119,120,121,122),true))continue;
 $source=get_post_meta($id,Aspire_Property_Intelligence::SOURCE,true);
 pilot_assert(in_array($source['migration_id']??'',array('aspire-011','aspire-081'),true),'Unexpected non-pilot Property');
}
$plans=array();$needed=array();
foreach($payload['records'] as $record){
 $mid=$record['migration_id'];pilot_assert(array_key_exists($mid,$allow),'Property is outside pilot allowlist');$existing=$allow[$mid];$c=$record['import_candidate'];
 $found=get_posts(array('post_type'=>'property','post_status'=>'any','name'=>$c['slug'],'numberposts'=>2,'fields'=>'ids'));
 if($existing){$id=$existing;pilot_assert(get_post_type($id)==='property'&&get_post_field('post_name',$id)===$c['slug'],'Existing identity mismatch');}
 else{$id=$found[0]??0;pilot_assert(count($found)<2,'Slug collision');if($id){$m=get_post_meta($id,Aspire_Property_Intelligence::SOURCE,true);pilot_assert(($m['migration_id']??'')===$mid&&get_post_status($id)==='draft','New draft identity/status mismatch');}}
 pilot_assert($record['import_readiness_v3']!=='BLOCKED','Blocked record');
 if(!$existing)pilot_assert(($c['coordinates']['status']??'')==='accepted','New draft needs accepted coordinate');
 $terms=$mid==='aspire-081'?array('For Sale','For Lease'):array('For Lease');$type=$mid==='aspire-081'?'Office Condo':'Retail';
 if(!$existing){foreach(array('property_type'=>array($type),'transaction_type'=>$terms) as $tax=>$names)foreach($names as $name)pilot_assert((bool)get_term_by('name',$name,$tax),'Required taxonomy term missing');}
 $fill=array();$preserve=array();$groups=Aspire_Core_Fields::groups('property');$kinds=array_merge(...array_values($groups));
 // Existing curated properties receive no source metric/price/coordinate/highlight overwrites.
 // Compatible blank address components alone may be filled; inventory-related blank fields stay blank.
 $values=$existing?($c['address']??array()):array_merge($c['address']??array(),$c['metrics']);
 if(!$existing){$values['latitude']=$c['coordinates']['latitude'];$values['longitude']=$c['coordinates']['longitude'];$values['property_highlights']=implode("\n",$record['clean_highlights']);}
 foreach($values as $field=>$value){if(!isset($kinds[$field])||$value===null||in_array($field,$record['remaining_conflicts'],true))continue;
  if($id&&metadata_exists('post',$id,'_aspire_'.$field)&&get_post_meta($id,'_aspire_'.$field,true)!==''){$preserve[]=$field;continue;}
  $clean=Aspire_Core_Fields::clean($value,$kinds[$field]);if($clean!=='')$fill[$field]=$clean;
 }
 if($existing)$preserve=array_values(array_unique(array_merge($preserve,array('title','slug','post_status','listing_status','featured_property','property_type','transaction_type','coordinates','pricing','metrics','suites','highlights','featured_image'))));
 $facts=$record['decision_facts_import'];$statuses=$record['decision_field_status'];$withheld=array();
 if($existing){
  foreach(array('office_sf','warehouse_sf','availability_range') as $field)if(isset($facts[$field])){unset($facts[$field]);$statuses[$field]='conflicting';$withheld[]=$field;}
  if($mid==='aspire-015'&&isset($facts['grade_level_doors'])){$facts['grade_level_doors']['value']=array('Grade Level Loading Doors');} // Suite-specific door count is not the building inventory.
  foreach(array('building_size'=>'building_sf','clear_height'=>'clear_height_ft','acreage'=>'lot_acres','building_class'=>'building_class','parking_ratio'=>'parking_ratio') as $field=>$meta){
   $curated=get_post_meta($id,'_aspire_'.$meta,true);if(isset($facts[$field])&&$curated!==''){
    $value=$facts[$field]['value'];$same=is_numeric($curated)&&is_numeric($value)?(float)$curated===(float)$value:is_scalar($value)&&strcasecmp(trim($curated),trim((string)$value))===0;
    if(!$same){unset($facts[$field]);$statuses[$field]='conflicting';$withheld[]=$field;}
   }
  }
 }
 $intelligence=Aspire_Property_Intelligence::sanitize(array('property_type'=>$c['property_type'],'facts'=>$facts,'decision_field_status'=>$statuses));
 $current=$id?get_post_meta($id,Aspire_Property_Intelligence::META,true):array();
 if($current){foreach($current['facts']??array() as $key=>$value)$intelligence['facts'][$key]=$value;foreach($current['decision_field_status']??array() as $key=>$value)$intelligence['decision_field_status'][$key]=$value;}
 $assets=$record['assets'];foreach($assets as $asset)if(!pilot_attachment($asset['url']))$needed[$asset['url']]=$asset;
 $plans[]=array('migration_id'=>$mid,'existing'=>$existing,'post_id'=>$id,'title'=>$c['title'],'slug'=>$c['slug'],'fill'=>$fill,'preserve'=>$preserve,'type'=>$type,'transactions'=>$terms,'intelligence'=>$intelligence,'withheld'=>$withheld,'record'=>$record);
}
pilot_assert(count(array_unique(array_column($plans,'migration_id')))===count($plans),'Duplicate pilot selection');
if(!$apply){echo wp_json_encode(array('dry_run'=>true,'safety_checks'=>'passed','properties_before'=>count($posts),'plans'=>array_map(static fn($p)=>array_diff_key($p,array_flip(array('record','intelligence'))),$plans),'needed_assets'=>array_values($needed),'brochures'=>count(array_filter($plans,static fn($p)=>!empty($p['record']['brochure_asset']))),'asset_count'=>count($needed)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);exit;}
pilot_assert(!empty($payload['backup_verified']),'Verified backup required');
// Validate all staged assets before the first post/meta/attachment write.
foreach($needed as $asset){$path='/tmp/aspire-pilot-media/'.$asset['file'];pilot_assert(is_file($path)&&filesize($path)>0&&isset($asset['sha256'])&&hash_file('sha256',$path)===$asset['sha256'],'Invalid staged asset');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);pilot_assert($asset['kind']==='pdf'?($mime==='application/pdf'&&file_get_contents($path,false,null,0,5)==='%PDF-'):in_array(wp_get_image_mime($path),array('image/jpeg','image/png','image/webp'),true),'Invalid media content');if($asset['kind']!=='pdf')pilot_assert((bool)wp_getimagesize($path),'Unreadable image');}
$report=array();
foreach($plans as $plan){
 $r=$plan['record'];$c=$r['import_candidate'];$id=$plan['post_id'];$created=false;
 if(!$id){$id=wp_insert_post(array('post_type'=>'property','post_status'=>'draft','post_title'=>$c['title'],'post_name'=>$c['slug']),true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());$created=true;pilot_meta($id,Aspire_Property_Intelligence::SOURCE,array('migration_id'=>$r['migration_id'],'review_status'=>'IMPORT_IN_PROGRESS'));}
 if($created){wp_set_object_terms($id,$plan['type'],'property_type');wp_set_object_terms($id,$plan['transactions'],'transaction_type');}
 foreach($plan['fill'] as $field=>$value)pilot_meta($id,'_aspire_'.$field,$value);
 if(!$plan['existing']&&!metadata_exists('post',$id,'_aspire_suites')&&$c['suites']){
  $suites=array();foreach($c['suites'] as $s){if($s['field_conflict'])continue;$row=array();foreach(Aspire_Core_Fields::suites() as $field=>$kind)$row[$field]=isset($s[$field])?Aspire_Core_Fields::clean($s[$field],$kind):'';
   if($s['nnn_cam'])$row['notes'].="\n".'$'.$s['nnn_cam']['value'].'/SF '.$s['nnn_cam']['type'];$suites[]=$row;
  }pilot_meta($id,'_aspire_suites',$suites);
 }
 pilot_meta($id,Aspire_Property_Intelligence::META,$plan['intelligence']);
 $media=array();foreach($r['assets'] as $asset)$media[$asset['url']]=pilot_media($asset,$r['migration_id'],$id);
 if(!get_post_thumbnail_id($id)&&!empty($media[$r['primary_asset']]))set_post_thumbnail($id,$media[$r['primary_asset']]);
 $gallery=array();foreach($r['gallery_assets'] as $url)if($media[$url]!==get_post_thumbnail_id($id))$gallery[]=$media[$url];
 $old_gallery=get_post_meta($id,'_aspire_gallery_attachment_ids',true);$gallery=array_values(array_unique(array_merge(is_array($old_gallery)?$old_gallery:array(),$gallery)));
 pilot_meta($id,'_aspire_gallery_attachment_ids',$gallery);
 if($r['brochure_asset']&&!get_post_meta($id,'_aspire_brochure_attachment_id',true))pilot_meta($id,'_aspire_brochure_attachment_id',$media[$r['brochure_asset']]);
 $source=get_post_meta($id,Aspire_Property_Intelligence::SOURCE,true);
 if(empty($source['imported_at'])){$source=array('migration_id'=>$r['migration_id'],'schema_version'=>4,'source_url'=>$r['source_url'],'source_publish_date'=>$r['source_publish_date'],'manifest_sha256'=>$payload['manifest_sha256'],'manifest_reference'=>'docs/migration/aspire-properties-manifest-v4.json','imported_at'=>gmdate('c'),'review_status'=>$r['import_readiness_v3'],'warnings'=>array_values(array_unique(array_merge($r['non_blocking_warnings'],$r['remaining_conflicts'],$plan['withheld']?array('curated_inventory_intelligence_withheld'):array()))),'primary_image_review_required'=>!$plan['existing']&&$r['primary_image_review_required'],'coordinate_source'=>$plan['existing']?'Existing curated WordPress coordinate retained':'US Census Geocoder','coordinate_status'=>$plan['existing']?($r['existing_wp_comparison']['status']??'NOT_COMPARABLE'):'ACCEPTED','coordinate_evidence'=>$r['geocoding'],'protected_brochure_path'=>$r['protected_brochure_path'],'brochure_source_url'=>$c['brochure_url'],'brochure_source_sha256'=>$c['brochure_sha256'],'media_sources'=>$r['assets'],'contact_candidates'=>$r['secondary_contact_candidates'],'review_evidence'=>$r);pilot_meta($id,Aspire_Property_Intelligence::SOURCE,$source);}
 $report[]=array('migration_id'=>$r['migration_id'],'post_id'=>$id,'status'=>get_post_status($id),'created'=>$created,'featured_image_id'=>get_post_thumbnail_id($id),'gallery_count'=>count($gallery),'gallery_ids'=>$gallery,'brochure_id'=>get_post_meta($id,'_aspire_brochure_attachment_id',true),'filled_fields'=>array_keys($plan['fill']),'intelligence_fields'=>array_keys($plan['intelligence']['facts']),'withheld_fields'=>$plan['withheld']);
}
echo wp_json_encode(array('applied'=>true,'records'=>$report,'property_count'=>count(get_posts(array('post_type'=>'property','post_status'=>'any','numberposts'=>-1,'fields'=>'ids')))),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
