<?php
/** Deterministic, read-only public projection. Never reads migration provenance. */
defined('ABSPATH') || exit;
final class Aspire_Property_Lens {
 public static function rules(string $type): array {
  $groups=array(
   'Industrial / Flex'=>array('Space'=>array('office_sf','warehouse_sf','clear_height'),'Operations'=>array('construction_type','loading_configuration','dock_high_doors','grade_level_doors','sprinklered','yard','outdoor_storage','power_capacity','HVAC','parking'),'Access'=>array('access_notes')),
   'Retail'=>array('Space & availability'=>array('availability_range'),'Access & visibility'=>array('traffic_counts_by_road','frontage','signalized_intersection','turn_lane','ingress_egress','visibility','signage'),'Trade area'=>array('population_radius_facts','average_household_income','tenant_mix','nearby_retailers'),'Retail infrastructure'=>array('drive_thru','grease_trap','restaurant_infrastructure')),
   'Office'=>array('Building'=>array('building_class','building_size','availability_range','office_configuration','elevator','furnished','move_in_ready'),'Access & parking'=>array('parking_ratio','parking_type','covered_parking','parking_spaces'),'Operations'=>array('24_7_access','security_access','on_site_management','amenities','building_signage','monument_signage')),
   'Land'=>array('Site'=>array('acreage','frontage','visibility'),'Development'=>array('utilities','detention','zoning','floodplain','restrictions','development_ready','shovel_ready','ETJ'),'Access'=>array('access_points','signalized_access','cross_access','nearby_corridors')),
  );
  $rules=array();foreach($groups[$type==='Office Condo'?'Office':$type]??array() as $group=>$fields)foreach($fields as $field)$rules[$field]=array('label'=>ucwords(str_replace('_',' ',$field)),'group'=>$group,'priority'=>50,'formatter'=>$field,'implication'=>'Consider this detail alongside the requirements of your proposed use.','question'=>'What should be confirmed about '.str_replace('_',' ',$field).'?');
  $specific=array(
   'clear_height'=>array('Clear height',1,'Clear height is an important fit factor when evaluating storage, racking or equipment requirements.'),
   'loading_configuration'=>array('Loading configuration',2,'The loading arrangement is worth evaluating against vehicle movements and day-to-day service activity.'),
   'grade_level_doors'=>array('Grade-level loading',3,'Grade-level access may simplify loading for operations that do not require dock-high service.'),
   'access_notes'=>array('Access',4,'The listed access connections are relevant when evaluating regional connectivity.'),
   'construction_type'=>array('Construction',8,'Construction type is a useful starting point when evaluating potential alterations and maintenance.'),
   'traffic_counts_by_road'=>array('Traffic counts',1,'Traffic volume is one input when evaluating exposure. It does not establish customer demand.'),
   'turn_lane'=>array('Turn lane',2,'Turn-lane access is worth considering when reviewing arrival and departure movements.'),
   'signalized_intersection'=>array('Signalized access',2,'A signalized intersection is relevant when evaluating how vehicles reach the property.'),
   'population_radius_facts'=>array('Population context',3,'The stated radius provides a defined geographic context for evaluating the surrounding trade area.'),
   'average_household_income'=>array('Household income',4,'Household income is a trade-area input to consider alongside your own customer research.'),
   'building_class'=>array('Building class',1,'Building class provides a reference point for comparing specifications, services and finish expectations.'),
   'building_size'=>array('Building size',2,'Building size provides scale; confirm how much space is part of the current offering.'),
   'parking_spaces'=>array('Parking spaces',3,'The stated parking quantity provides context; allocation and rights should be checked against your needs.'),
   'parking_ratio'=>array('Parking ratio',4,'Compare the stated ratio with your expected occupancy and visitor parking requirements.'),
   'parking_type'=>array('Parking',3,'Parking type helps frame arrival, access and day-to-day use of the property.'),
   'availability_range'=>array('Unit / contiguous space',2,'Compare the stated configuration with your space requirement and confirm which combinations are currently offered.'),
   'acreage'=>array('Site area',1,'Site area sets the scale for evaluation. Usable area depends on site conditions and applicable requirements.'),
   'nearby_corridors'=>array('Corridor context',2,'The listed corridor connections provide context for evaluating access. Confirm permitted site access separately.'),
   'office_sf'=>array('Office area',5,'Compare the office allocation with the needs of your proposed operation.'),
   'warehouse_sf'=>array('Warehouse area',6,'Compare the warehouse allocation with your storage and operational requirements.'),
   'HVAC'=>array('HVAC',20,'Review HVAC coverage and operating terms against your intended use.'),
  );
  foreach($specific as $key=>[$label,$priority,$implication])if(isset($rules[$key]))$rules[$key]=array_replace($rules[$key],compact('label','priority','implication'));
  return $rules;
 }
 public static function known(array $intel,string $key): bool {
  $fact=$intel['facts'][$key]??array();
  return ($intel['decision_field_status'][$key]??'unknown')==='known'&&is_array($fact)&&empty($fact['field_conflict'])&&empty($fact['review_required'])&&(!isset($fact['status'])||in_array($fact['status'],array('known','accepted'),true));
 }
 public static function format(string $key,$value): string {
  if(is_string($value)&&in_array(strtolower(trim($value)),array('null','undefined','nan'),true))return '';

  if(is_bool($value))return $value?'Yes':'No';
  if(is_numeric($value)){if(!is_finite((float)$value)||(float)$value<=0)return '';return number_format((float)$value,floor((float)$value)==(float)$value?0:2,'.',',').(array('clear_height'=>'′','office_sf'=>' SF','warehouse_sf'=>' SF','building_size'=>' SF','acreage'=>' acres','parking_spaces'=>' spaces')[$key]??'');}
  $lines=array();foreach(is_array($value)?$value:array($value) as $v){
   if(is_scalar($v))$lines[]=sanitize_text_field((string)$v);
   elseif($key==='traffic_counts_by_road'&&is_array($v)&&empty($v['field_conflict'])&&is_numeric($v['vpd']??null)&&$v['vpd']>0)$lines[]=number_format($v['vpd']).' VPD'.(!empty($v['road'])?' · '.sanitize_text_field($v['road']):' · road not specified');
  }
  $lines=array_values(array_unique(array_filter($lines,static fn($v)=>$v!=='')));
  // Preserve the fullest statement; omit exact/contained duplicate source phrases.
  $lines=array_values(array_filter($lines,static function($line)use($lines){foreach($lines as $other)if(strlen($other)>strlen($line)&&stripos($other,$line)!==false)return false;return true;}));
  $text=implode(' · ',$lines);
  if($key==='signalized_intersection'&&preg_match('/signalized intersection/i',$text))return 'Signalized intersection';
  if($key==='nearby_corridors'&&preg_match('/along the (.+?) corridor/i',$text,$m))return $m[1].' corridor';
  if($key==='access_notes'&&preg_match('/convenient access to (Highway [0-9]+)/i',$text,$m))return $m[1].' access';
  if($key==='loading_configuration'&&preg_match('/rear[ -]load/i',$text))return 'Rear-load configuration';
  if($key==='building_class'&&$text)return 'Class '.$text;
  if(in_array($key,array('population_radius_facts','average_household_income'),true)){
   $radius='';if(preg_match('/((?:Three|Two|One|Five|[0-9.]+)[ -]mile radius)/i',$text,$m))$radius=$m[1];
   $pattern=$key==='population_radius_facts'?'/population\s+(?:is\s+)?([\d,]*\d)/i':'/(?:AHHI|household income)\s+(?:is\s+)?\$([\d,]*\d)/i';
   if(preg_match($pattern,$text,$m))return ($key==='average_household_income'?'$':'').$m[1].($radius?' · '.strtolower($radius):'');
  }
  return $text;
 }
 /** Optional contexts are explicit reviewed objects, never extracted from provenance. */
 public static function contexts(array $contexts,bool $suppressed): array {
  $out=array();foreach($contexts as $context){if(!is_array($context)||($context['status']??'')!=='known'||empty($context['label']))continue;
   $rows=array();foreach($context['facts']??array() as $key=>$fact){if(!is_array($fact)||($fact['status']??'')!=='known'||($suppressed&&preg_match('/price|rate|econom|cost/i',$key)))continue;$value=self::format($key,$fact['value']??'');if($value!=='')$rows[]=array('label'=>sanitize_text_field($fact['label']??ucwords(str_replace('_',' ',$key))),'value'=>$value);}
   if($rows)$out[]=array('label'=>sanitize_text_field($context['label']),'facts'=>$rows);
  }return $out;
 }
 public static function present(string $type,array $intel,array $meta,array $suites=[],bool $suppressed=false): array {
  $rules=self::rules($type);$facts=array();
  foreach($rules as $key=>$rule){
   if(!self::known($intel,$key))continue;
   $value=self::format($key,$intel['facts'][$key]['value']??'');if($value!=='')$facts[$key]=$rule+array('key'=>$key,'value'=>$value);
  }
  // Current curated fields override source intelligence, including withheld legacy comparisons.
  foreach(array('building_class'=>'building_class','building_size'=>'building_sf','clear_height'=>'clear_height_ft','parking_spaces'=>'parking_spaces','parking_ratio'=>'parking_ratio','acreage'=>'lot_acres') as $key=>$field){
   if(!isset($rules[$key]))continue;$value=self::format($key,$meta[$field]??'');if($value!=='')$facts[$key]=$rules[$key]+array('key'=>$key,'value'=>$value);
  }
  uasort($facts,static fn($a,$b)=>($a['priority']<=>$b['priority'])?:strcmp($a['key'],$b['key']));
  $standouts=array_slice(array_values($facts),0,4);$selected=array_column($standouts,'key');$groups=array();
  foreach($facts as $key=>$fact)if(!in_array($key,$selected,true))$groups[$fact['group']][]=$fact;
  $space_group=$type==='Retail'?'Space & availability':($type==='Land'?'Site':'Space');
  foreach(array('available_sf'=>'Listed space','minimum_available_sf'=>'Minimum available','maximum_contiguous_sf'=>'Maximum contiguous') as $key=>$label){
   if($type==='Land'||empty($meta[$key])||!is_numeric($meta[$key])||$meta[$key]<=0)continue;
   $groups[$space_group][]=array('label'=>$label,'value'=>self::format('office_sf',$meta[$key]));
  }
  if(!$suppressed)foreach(array('lease_rate_display'=>'Lease rate','price_display'=>'Price') as $key=>$label){
   if(!empty($meta[$key])&&is_scalar($meta[$key]))$groups[$type==='Land'?'Offering':'Economics'][]=array('label'=>$label,'value'=>sanitize_text_field($meta[$key]));
  }
  $questions=self::questions($type,$facts,$suites,$intel,$meta);
  return array('facts'=>$facts,'standouts'=>$standouts,'groups'=>$groups,'questions'=>$questions,'availability_contexts'=>self::contexts($intel['availability_contexts']??array(),$suppressed),'offering_contexts'=>self::contexts($intel['offering_contexts']??array(),$suppressed));
 }
 public static function questions(string $type,array $facts,array $suites,array $intel,array $meta=[]): array {
  $sets=array(
   'Industrial / Flex'=>array('power_capacity'=>'What electrical service and power capacity are available?','loading_dimensions'=>'What are the loading-door dimensions and exact door count?','parking'=>'How is parking allocated?','yard'=>'Are there yard or outdoor-storage restrictions?','HVAC'=>'What HVAC coverage serves the warehouse and office areas?','space_split'=>'How is the current space divided between office and warehouse use?'),
   'Retail'=>array('nnn_cam'=>'What are the current NNN/CAM charges?','signage'=>'What signage rights are included?','ingress_egress'=>'How is delivery and service access handled?','tenant_improvements'=>'Are tenant-improvement allowances available?','use_restrictions'=>'Are there use restrictions or exclusives relevant to the space?'),
   'Office'=>array('operating_expenses'=>'What operating expenses are currently passed through?','parking_allocation'=>'How is parking allocated to tenants?','HVAC'=>'What HVAC hours and after-hours terms apply?','building_signage'=>'What signage opportunities are available?','tenant_improvements'=>'What tenant-improvement package is available?'),
   'Land'=>array('utilities'=>'What utilities are currently available at the site?','detention'=>'What detention requirements apply?','floodplain'=>'What floodplain conditions should be considered?','zoning'=>'What zoning or entitlement restrictions apply?','access_points'=>'What access points are permitted?','due_diligence'=>'What due-diligence materials are available?'),
  );
  $out=array();foreach($sets[$type==='Office Condo'?'Office':$type]??array() as $key=>$question){
   $answered=isset($facts[$key]);
   if(self::known($intel,$key)&&self::format($key,$intel['facts'][$key]['value']??'')!=='')$answered=true;
   if($key==='space_split')$answered=isset($facts['office_sf'],$facts['warehouse_sf']);
   if($key==='nnn_cam'){
    $cost_pattern='/\$[\d,.]+\s*(?:\/SF\s*)?(?:NNN|CAM)\b|\b(?:NNN|CAM)\s*[:=]?\s*\$[\d,.]+/i';
    if(preg_match($cost_pattern,$meta['lease_rate_display']??''))$answered=true;
    elseif($suites){$answered=true;foreach($suites as $suite)if(!preg_match($cost_pattern,$suite['notes']??''))$answered=false;}
   }
   if(!$answered)$out[]=array('key'=>$key,'text'=>$question);
  }return array_slice($out,0,6);
 }
}
