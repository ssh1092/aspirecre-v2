<?php
/** Workspace contracts and a disposable native-field round trip. Pilot records are read-only. */
require dirname(__DIR__,4).'/wp-load.php';
$count=0;function workspace_check($ok,$message){global $count;if(!$ok)throw new RuntimeException($message);$count++;}
$admin=get_users(array('role'=>'administrator','number'=>1))[0];wp_set_current_user($admin->ID);
foreach(array(119,120,121,122,351,392) as $id){
 $d=Aspire_Property_Dossier::data($id);$w=Aspire_Property_Dossier::workspace($d);
 workspace_check(count($w['metrics'])<=3,'Three-metric limit');workspace_check($w===Aspire_Property_Dossier::workspace($d),'Deterministic factual synthesis');
 workspace_check(!preg_match('/why this matters|important fit factor|ideal|prime|strong demand/i',$w['summary']),'No educational/suitability copy');
 ob_start();Aspire_Property_Workspace::render(get_post($id));$html=ob_get_clean();
 workspace_check(substr_count($html,'role="tabpanel"')===7&&substr_count($html,'tabindex="0" hidden')===6,'Seven modes, one visible');
 workspace_check(!str_contains($html,'name="content"')&&!str_contains($html,'wp-editor-wrap'),'No giant editor');
 workspace_check(str_contains($html,'name="aspire_nonce"')&&str_contains($html,'name="aspire_intelligence_nonce"'),'Existing nonces');
 workspace_check(str_contains($html,'name="_thumbnail_id"')&&str_contains($html,'aspire[gallery_attachment_ids]')&&str_contains($html,'aspire[brochure_attachment_id]'),'Native media contracts');
 workspace_check(str_contains($html,'Adjust coordinates')&&str_contains($html,'data-property-map'),'Location disclosure and actual pin');
 workspace_check(!str_contains($html,'sha256')&&!str_contains($html,'v2_pointer'),'No technical provenance dump');
}
$s=Aspire_Property_Dossier::workspace(Aspire_Property_Dossier::data(351));
workspace_check(array_column($s['metrics'],'value')===array('5,082 SF','4','766–1,630 SF'),'Sugarwell restrained rail');
workspace_check(count($s['spaces'])===4&&$s['spaces'][1]['size']===''&&$s['spaces'][1]['rate']==='$24/SF Base','Unknown area and contextual rate');
workspace_check($s['spaces'][0]['use']==='Medical / Office'&&$s['spaces'][2]['use']==='Retail','Only recognized suite use from saved notes');
$c=Aspire_Property_Dossier::workspace(Aspire_Property_Dossier::data(120));workspace_check(!preg_match('/1,687|2,087/',wp_json_encode($c)),'Conflicting Clay split never asserted');
$f=Aspire_Property_Dossier::workspace(Aspire_Property_Dossier::data(122));workspace_check(!preg_match('/3,410,000|3410000|2,232,000/',wp_json_encode($f)),'Suppressed price excluded');
$sienna=Aspire_Property_Dossier::workspace(Aspire_Property_Dossier::data(392));workspace_check(!$sienna['spaces']&&$sienna['metrics'][0]['value']==='1,225 SF','Real combination model, no invented units');
$fixture=0;
try{
 $fixture=wp_insert_post(array('post_type'=>'property','post_status'=>'draft','post_title'=>'Temporary workspace round-trip fixture'));
 wp_set_object_terms($fixture,array('Retail'),'property_type');
 foreach(get_post_meta(351) as $key=>$values)if(str_starts_with($key,'_aspire_')||$key==='_thumbnail_id')foreach($values as $value)add_post_meta($fixture,$key,wp_slash(maybe_unserialize($value)));
 $before=get_post_meta($fixture,Aspire_Property_Intelligence::META,true);$source=get_post_meta($fixture,Aspire_Property_Intelligence::SOURCE,true);$suites=get_post_meta($fixture,'_aspire_suites',true);$gallery=get_post_meta($fixture,'_aspire_gallery_attachment_ids',true);
 ob_start();Aspire_Property_Workspace::render(get_post($fixture));$html=ob_get_clean();
 $doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xpath=new DOMXPath($doc);$pairs=array();
 foreach($xpath->query('//input[@name]|//select[@name]|//textarea[@name]') as $input){
  if($input->hasAttribute('disabled')||$xpath->evaluate('boolean(ancestor::template)',$input))continue;
  if(in_array($input->getAttribute('type'),array('checkbox','radio'),true)&&!$input->hasAttribute('checked'))continue;
  $value=$input->getAttribute('value');if($input->tagName==='textarea')$value=$input->textContent;
  if($input->tagName==='select'){$selected=$xpath->query('option[@selected]',$input);$value=$selected->length?$selected->item(0)->getAttribute('value'):'';}
  $pairs[]=urlencode($input->getAttribute('name')).'='.urlencode($value);
 }
 parse_str(implode('&',$pairs),$payload);$_POST=wp_slash($payload);
 Aspire_Core_Admin::save($fixture,get_post($fixture));Aspire_Property_Intelligence::save($fixture,get_post($fixture));
 workspace_check(get_post_meta($fixture,'_aspire_suites',true)===$suites,'Collapsed suites round trip unchanged');
 workspace_check(get_post_meta($fixture,'_aspire_gallery_attachment_ids',true)===$gallery,'Gallery order round trip unchanged');
 workspace_check(get_post_meta($fixture,Aspire_Property_Intelligence::META,true)===$before,'Intelligence evidence and unknown statuses round trip unchanged');
 workspace_check(get_post_meta($fixture,Aspire_Property_Intelligence::SOURCE,true)===$source,'Source never written by workspace');
 workspace_check(get_post_status($fixture)==='draft','Field handlers never publish drafts');
 $_POST['aspire']['gallery_attachment_ids']=implode(',',array_reverse($gallery));Aspire_Core_Admin::save($fixture,get_post($fixture));
 workspace_check(get_post_meta($fixture,'_aspire_gallery_attachment_ids',true)===array_reverse($gallery),'Reorder uses existing ordered attachment field');
 $_POST['aspire_nonce']='invalid';$_POST['aspire']['city']='Blocked';Aspire_Core_Admin::save($fixture,get_post($fixture));workspace_check(get_post_meta($fixture,'_aspire_city',true)!=='Blocked','Invalid nonce rejects workspace update');
}finally{$_POST=array();if($fixture)wp_delete_post($fixture,true);}
echo "SUCCESS: $count property workspace assertions\n";
