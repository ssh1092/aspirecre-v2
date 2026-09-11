<?php
/** Local-only, explicit, reversible Atlas Task 1 migration. */
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
if(wp_get_environment_type()!=='local'){throw new RuntimeException('Local prototype only.');}
$home=get_post((int)get_option('page_on_front'));
if(!$home||$home->post_type!=='page'||$home->post_title!=='Home'){throw new RuntimeException('Expected existing static Home page.');}
if(in_array('--restore',$argv,true)){
 $backup=get_post_meta($home->ID,'_aspire_before_atlas_task1',true);
 if(!$backup){throw new RuntimeException('No pre-Atlas backup found.');}
 wp_save_post_revision($home->ID);
 $result=wp_update_post(array('ID'=>$home->ID,'post_content'=>wp_slash($backup)),true);
 if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}
 echo "Restored conventional Home {$home->ID}.\n";exit;
}
$records=array(
 '7506-e-fm-1960-humble-texas-77346'=>array(29.9981,-95.1616,false),
 '16840-clay-road-houston-tx-77084'=>array(29.8344342,-95.6575678,false),
 '14602-presidio-square-boulevard-houston-tx-77083'=>array(29.7076,-95.6420,true),
 '21617-fm-1093-richmond-tx-77407'=>array(29.6773,-95.7195,true),
);
$provisional=array();$suppressed=array();
foreach($records as $slug=>$coordinate){
 $post=get_page_by_path($slug,OBJECT,'property');
 if(!$post){throw new RuntimeException('Missing existing property '.$slug);}
 update_post_meta($post->ID,'_aspire_latitude',$coordinate[0]);
 update_post_meta($post->ID,'_aspire_longitude',$coordinate[1]);
 if($coordinate[2]){$provisional[]=$post->ID;}
 if($slug==='21617-fm-1093-richmond-tx-77407'){$suppressed[]=$post->ID;}
 echo "Property {$post->ID}: ".($coordinate[2]?'PROVISIONAL — needs final visual verification':'supplied prototype coordinates')."\n";
}
// Atlas display policy only. Do not modify any stored property pricing.
update_option('aspire_atlas_provisional_coordinate_ids',$provisional,false);
update_option('aspire_atlas_suppressed_price_ids',$suppressed,false);
if(!has_block('aspire/atlas',$home)){
 $backup=get_post_meta($home->ID,'_aspire_before_atlas_task1',true);
 if($backup && $home->post_content!==$backup){throw new RuntimeException('Home changed since backup; preserve those edits before migrating again.');}
 add_post_meta($home->ID,'_aspire_before_atlas_task1',wp_slash($home->post_content),true);
 wp_save_post_revision($home->ID);
 $result=wp_update_post(array('ID'=>$home->ID,'post_content'=>'<!-- wp:aspire/atlas {"metadata":{"name":"Aspire Atlas"}} /-->'),true);
 if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}
}
echo "Home {$home->ID} is the existing static front page, with Aspire Atlas. Conventional content preserved in metadata and revisions.\n";
