<?php
/** Sanitization and native-admin roundtrip tests; disposable local fixture only. */
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,4).'/wp-load.php';
$n=0;$id=0;
function intelligence_check($ok,$message){global $n;if(!$ok)throw new RuntimeException($message);$n++;}
try {
 wp_set_current_user(1);
 $id=wp_insert_post(array('post_type'=>'property','post_status'=>'draft','post_title'=>'Temporary intelligence test'));
 wp_set_object_terms($id,'Retail','property_type');
 $data=array('property_type'=>'Retail','facts'=>array('turn_lane'=>array('value'=>array('<script>alert(1)</script>Dedicated turn lane')),'traffic_counts_by_road'=>array('value'=>array(array('road'=>'Main Rd','vpd'=>20900)))),'decision_field_status'=>array('turn_lane'=>'known','traffic_counts_by_road'=>'known','grease_trap'=>'unknown'));
 update_post_meta($id,Aspire_Property_Intelligence::META,$data);
 $clean=get_post_meta($id,Aspire_Property_Intelligence::META,true);
 intelligence_check(!str_contains(wp_json_encode($clean),'<script>'),'No executable HTML stored');
 intelligence_check($clean===Aspire_Property_Intelligence::sanitize($clean),'Sanitization deterministic');
 update_post_meta($id,Aspire_Property_Intelligence::SOURCE,array('source_url'=>'https://www.aspirecre.com/property','contact_candidates'=>array(array('name'=>'Test Person','email'=>'person@example.test'))));
 intelligence_check(is_array(get_post_meta($id,Aspire_Property_Intelligence::SOURCE,true)),'Source sanitizer accepts WordPress callback arguments');
 $_POST=array('aspire_intelligence'=>array('status'=>array('turn_lane'=>'known'),'value'=>array('turn_lane'=>'Changed')));
 Aspire_Property_Intelligence::save($id,get_post($id));
 intelligence_check(get_post_meta($id,Aspire_Property_Intelligence::META,true)===$clean,'Nonce required');
 $_POST['aspire_intelligence_nonce']=wp_create_nonce('aspire_intelligence_'.$id);
 Aspire_Property_Intelligence::save($id,get_post($id));
 intelligence_check(get_post_meta($id,Aspire_Property_Intelligence::META,true)['facts']['turn_lane']['value']===array('Changed'),'Native labelled field saves');
 $_POST['aspire_intelligence']['status']['turn_lane']='conflicting';Aspire_Property_Intelligence::save($id,get_post($id));
 intelligence_check(!isset(get_post_meta($id,Aspire_Property_Intelligence::META,true)['facts']['turn_lane']),'Conflicting value not asserted as known');
 $_POST['aspire_intelligence']=array('status'=>array('traffic_counts_by_road'=>'known'),'value'=>array('traffic_counts_by_road'=>'Main Rd — 20900 VPD'));
 Aspire_Property_Intelligence::save($id,get_post($id));
 intelligence_check(get_post_meta($id,Aspire_Property_Intelligence::META,true)['facts']['traffic_counts_by_road']['value'][0]['vpd']===20900,'Unchanged structured traffic preserved');
 wp_set_current_user(0);$before=get_post_meta($id,Aspire_Property_Intelligence::META,true);Aspire_Property_Intelligence::save($id,get_post($id));
 intelligence_check(get_post_meta($id,Aspire_Property_Intelligence::META,true)===$before,'Capability required');
 intelligence_check(Aspire_Core_Fields::clean('',array('available','leased'))==='','Unknown suite status remains empty');
 ob_start();Aspire_Property_Intelligence::intelligence_box(get_post($id));$html=ob_get_clean();
 intelligence_check(str_contains($html,'Additional decision fields')&&!str_contains($html,'&quot;facts&quot;'),'Readable admin controls, no raw JSON');
 echo "PASS: $n intelligence assertions\n";
}finally{$_POST=array();if($id)wp_delete_post($id,true);}
