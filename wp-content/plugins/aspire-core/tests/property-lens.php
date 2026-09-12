<?php
/** Read-only pilot + pure presenter tests. No post/meta writes. */
require dirname(__DIR__,4).'/wp-load.php';
$n=0;function lens_check($ok,$message){global $n;if(!$ok)throw new RuntimeException($message);$n++;}
$models=array();foreach(array(119,120,121,122,351,392) as $id){
 $d=Aspire_Property_Dossier::data($id);$models[$id]=$d;
 lens_check(count($d['lens']['questions'])<=6,'Question cap');
 lens_check(count($d['lens']['standouts'])<=4,'Standout cap');
 lens_check(wp_json_encode($d)===wp_json_encode(Aspire_Property_Dossier::data($id)),'Deterministic model');
 $json=wp_json_encode($d['lens']);foreach(array('evidence','v2_pointer','contact_candidates','source_url','migration_id','REVIEW_REQUIRED') as $private)lens_check(!str_contains($json,$private),'Private data excluded '.$private);
 foreach($d['lens']['standouts'] as $fact)lens_check(!preg_match('/perfect|ideal for|guarantee|will reduce/i',$fact['implication']),'No suitability promises');
}
$c=$models[120];lens_check($c['lens']['facts']['clear_height']['value']==='14′','Clay clear height');
lens_check(!isset($c['lens']['facts']['office_sf'],$c['lens']['facts']['warehouse_sf']),'Clay source split withheld');
lens_check(!$c['suites']&&$c['hero']['AVAILABLE']==='11,273 SF','Clay inventory preserved');
lens_check(array_column($c['lens']['standouts'],'key')===array('clear_height','loading_configuration','grade_level_doors','access_notes'),'Industrial priorities');
lens_check(isset($c['lens']['groups']['Operations'],$c['lens']['groups']['Economics']),'Useful operation/economic groups');
lens_check($models[119]['suites'][0]['square_feet']==='4,361'&&$models[119]['suites'][0]['notes']==='$5.84/SF NNN','Atascocita curated suite');
lens_check(!in_array('nnn_cam',array_column($models[119]['lens']['questions'],'key'),true),'Known NNN not asked');
lens_check($models[121]['lens']['facts']['building_class']['value']==='Class B','Office class');
lens_check($models[121]['lens']['facts']['parking_spaces']['value']==='160 spaces','Office parking');
lens_check($models[121]['lens']['facts']['parking_ratio']['value']==='3.95 spaces per 1,000 SF','Curated ratio overrides withheld source');
lens_check($models[122]['brochure']===0&&!isset($models[122]['hero']['SALE PRICE']),'FM no price or brochure');
lens_check(!preg_match('/3410000|3,410,000|2,232,000/',wp_json_encode($models[122])),'No suppressed price in any projected field');
lens_check($models[122]['lens']['standouts']&&$models[122]['lens']['questions'],'Land useful sparse behavior');
lens_check(count($models[351]['suites'])===4&&$models[351]['suites'][1]['rate']==='$24'&&$models[351]['suites'][1]['rate_type']==='Base','Sugarwell suite rate context');
lens_check($models[351]['suites'][1]['square_feet']===''&&$models[351]['suites'][1]['availability_status']==='','No invented suite area or status');
lens_check($models[351]['lens']['facts']['traffic_counts_by_road']['value']==='22,246 VPD · road not specified','No invented traffic road');
lens_check(count($models[392]['transactions'])===2,'Both Sienna terms');
lens_check($models[392]['title']==='Sienna Park Office Condos','Clean condo title');
foreach(array(351,392) as $id)lens_check(get_post_status($id)==='draft','Draft remains draft');
$input=array('facts'=>array('power_capacity'=>array('value'=>'480 V'),'HVAC'=>array('value'=>'Entire building'),'yard'=>array('value'=>'Private source conflict')),'decision_field_status'=>array('power_capacity'=>'known','HVAC'=>'known','yard'=>'conflicting'));
$r=Aspire_Property_Lens::present('Industrial / Flex',$input,array());
lens_check(isset($r['facts']['power_capacity'],$r['facts']['HVAC'])&&!isset($r['facts']['yard']),'Known/conflicting gate');
lens_check(!array_intersect(array('power_capacity','HVAC'),array_column($r['questions'],'key')),'Answered questions omitted');
foreach(array('Retail','Office','Office Condo','Land','Industrial / Flex') as $type){$r=Aspire_Property_Lens::present($type,array(),array());lens_check(!$r['standouts']&&!$r['groups'],'No empty intelligence grid');lens_check(count($r['questions'])>=4&&count($r['questions'])<=6,'Type-specific sparse questions');}
$contexts=array(array('label'=>'Parcel A / Ground lease','status'=>'known','facts'=>array('acreage'=>array('status'=>'known','value'=>2),'price'=>array('status'=>'known','value'=>100000))),array('label'=>'Parcel B / Sale','status'=>'known','facts'=>array('acreage'=>array('status'=>'known','value'=>4))),array('label'=>'Review parcel','status'=>'conflicting','facts'=>array()));
$r=Aspire_Property_Lens::contexts($contexts,true);lens_check(count($r)===2&&count($r[0]['facts'])===1&&$r[1]['facts'][0]['value']==='4 acres','Contexts stay distinct, conflict/pricing omitted');
foreach(array(null,0,'0','',array(),NAN) as $value)lens_check(Aspire_Property_Lens::format('office_sf',$value)==='','Missing/invalid number omitted');
lens_check(array_column($models[392]['transactions'],'name')===array('For Sale','For Lease'),'Explicit ordered multi-intent display');
function lens_partial($part,$data){ob_start();get_template_part('template-parts/property/'.$part,null,array('data'=>$data));return ob_get_clean();}
foreach(array(0,1,2,3) as $count){$d=$models[120];$d['photos']=array_slice($d['photos'],0,$count);$html=lens_partial('media',$d);lens_check($count?str_contains($html,'dossier-mosaic-'.$count):!str_contains($html,'dossier-mosaic'),'Adaptive mosaic without blank cells');}
lens_check(!str_contains(lens_partial('details',$models[122]),'PROPERTY DOCUMENTS'),'No FM documents shell');
lens_check(str_contains(lens_partial('details',$models[121]),'PROPERTY DOCUMENTS'),'Real local brochure document area');
foreach(array(351,392) as $id){
 $response=wp_remote_get(str_replace('localhost:8080','localhost',get_permalink($id)),array('headers'=>array('Host'=>'localhost:8080'),'redirection'=>0));
 lens_check(wp_remote_retrieve_response_code($response)!==200,'Draft absent from anonymous permalink');
 $html=lens_partial('lens',$models[$id]);lens_check(str_contains($html,'ASPIRE LENS')&&!str_contains($html,'REVIEW_REQUIRED'),'Draft safe presentation');
}
$q=Aspire_Property_Lens::questions('Retail',array(),array(array('notes'=>'$24/SF Base + NNN')),array());lens_check(in_array('nnn_cam',array_column($q,'key'),true),'Base rate alone does not answer NNN');
$q=Aspire_Property_Lens::questions('Retail',array(),array(),array(),array('lease_rate_display'=>'$18/SF Base + $5.84/SF NNN'));lens_check(!in_array('nnn_cam',array_column($q,'key'),true),'Known curated NNN not asked');
$review=array('decision_field_status'=>array('power_capacity'=>'known'),'facts'=>array('power_capacity'=>array('value'=>'480 V','review_required'=>true)));
$r=Aspire_Property_Lens::present('Industrial / Flex',$review,array());lens_check(!isset($r['facts']['power_capacity'])&&in_array('power_capacity',array_column($r['questions'],'key'),true),'Review-only evidence withheld and remains a question');
echo "SUCCESS: $n read-only presenter assertions\n";
