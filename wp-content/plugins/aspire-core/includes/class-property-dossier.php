<?php
/** Read-only property dossier model. No migration evidence is returned. */
defined('ABSPATH') || exit;
final class Aspire_Property_Dossier {
 public static function text(int $id,string $key): string {return sanitize_text_field((string)get_post_meta($id,'_aspire_'.$key,true));}
 public static function number(int $id,string $key,string $suffix=''): string {
  $value=get_post_meta($id,'_aspire_'.$key,true);
  return is_numeric($value)&&is_finite((float)$value)&&(float)$value>0 ? self::format((float)$value).$suffix : '';
 }
 private static function format(float $value): string {return number_format($value,floor($value)===$value?0:2,'.',',');}
 public static function lines(int $id,string $key): array {return array_values(array_filter(array_map('sanitize_text_field',preg_split('/\r\n|\r|\n/',(string)get_post_meta($id,'_aspire_'.$key,true))),static fn($v)=>$v!==''));}
 public static function attachment(int $id,bool $image=false): bool {
  $url=wp_get_attachment_url($id);
  return $id>0&&get_post_type($id)==='attachment'&&$url&&wp_parse_url($url,PHP_URL_HOST)===wp_parse_url(home_url(),PHP_URL_HOST)&&($image?wp_attachment_is_image($id):get_post_mime_type($id)==='application/pdf');
 }
 /** A small, deterministic view composed exclusively from the safe property model. */
 public static function workspace(array $d): array {
  $type=$d['type']->name??'';$facts=$d['lens']['facts'];$snapshot=$d['snapshot'];$rail=array();
  $keys=match($type){'Industrial / Flex'=>array('AVAILABLE','BUILDING','CLEAR HEIGHT'),'Retail'=>array('AVAILABLE','LISTED SUITE SIZES','SITE'),'Office Condo'=>array('UNIT SIZE','CONTIGUOUS'),'Office'=>array('BUILDING','CLASS','PARKING'),default=>array('SITE')};
  foreach($keys as $key)if(isset($snapshot[$key]))$rail[$key]=$snapshot[$key];
  if($type==='Retail'&&$d['suites']){$rail=array_slice($rail,0,1,true)+array('LISTED SPACES'=>(string)count($d['suites']))+array_slice($rail,1,null,true);}
  $rail=array_slice($rail,0,3,true);$labels=array('UNIT SIZE'=>'Per unit','CONTIGUOUS'=>'Contiguous · one building','LISTED SUITE SIZES'=>'Known suite range','LISTED SPACES'=>'Listed spaces');
  $metrics=array();foreach($rail as $label=>$value)$metrics[]=array('label'=>$labels[$label]??ucfirst(strtolower($label)),'value'=>str_replace(' (one building)','',$value));
  $sentences=array();$available=$d['hero']['AVAILABLE']??'';$building=$d['hero']['BUILDING']??'';
  if($type==='Office Condo'&&isset($snapshot['UNIT SIZE'],$snapshot['CONTIGUOUS']))$sentences[]='Listed units are '.$snapshot['UNIT SIZE'].' each and can combine to '.lcfirst($snapshot['CONTIGUOUS']).'.';
  elseif($d['suites']){
   $count=count($d['suites']);$sentence=$count.' listed '.($count===1?'space':'spaces');
   if($available)$sentence.=' with '.$available.' advertised in total';
   $sentences[]=ucfirst($sentence).'.';
   if(isset($snapshot['LISTED SUITE SIZES']))$sentences[]='Known suite sizes range from '.$snapshot['LISTED SUITE SIZES'].(count(array_filter(array_column($d['suites'],'square_feet')))<$count?'; some suite areas are not specified.':'.');
  }elseif($available)$sentences[]=$available.' is listed as available'.($building?' within a '.$building.' building':'').'.';
  elseif($building)$sentences[]='The listing comprises a '.$building.' '.strtolower($type).' building'.(!empty($d['groups']['Building']['Stories'])?' across '.$d['groups']['Building']['Stories'].' stories':'').'.';
  elseif(isset($d['hero']['SITE']))$sentences[]='The listed site comprises '.$d['hero']['SITE'].'.';
  $signals=array();$location=array();
  foreach($facts as $key=>$fact){
   if(in_array($key,array('traffic_counts_by_road','population_radius_facts','average_household_income','nearby_corridors','access_notes','frontage','ingress_egress'),true))$location[$key]=$fact;
   if(!in_array($key,array('availability_range','building_size','acreage','clear_height','average_household_income'),true))$signals[$key]=$fact;
  }
  // A narrowly recognized, already stored configuration; never infer unit inventory.
  if($type==='Office Condo'){
   $parking=$facts['parking_type']['value']??'';$signals=array();
   foreach(array('private front-door entrance'=>'Private front-door entrance','surface parking'=>'Surface parking') as $needle=>$label)if(stripos($parking,$needle)!==false)$signals[$needle]=array('label'=>$label,'value'=>$label);
   if(isset($facts['availability_range']))$signals['combine']=array('label'=>'Combination','value'=>'Units can combine');
   foreach($d['highlights'] as $line)if(preg_match('/floor plans available for medical or standard office use/i',$line))$signals['configuration']=array('label'=>'Configuration','value'=>'Medical / standard office configuration');
  }
  if(count($sentences)<2){
   if(isset($facts['nearby_corridors']))$sentences[]='Source materials place the property along the '.$facts['nearby_corridors']['value'].'.';
   elseif($signals)$sentences[]='Listing materials identify '.implode(' and ',array_map(static fn($f)=>($f['key']??'')==='clear_height'?$f['value'].' clear height':lcfirst($f['value']),array_slice(array_values($signals),0,2))).'.';
   elseif($d['locality'])$sentences[]='The property is located in '.$d['locality'].'.';
  }
  $spaces=array();foreach($d['suites'] as $suite){
   $use=$suite['former_use'];
   if(!$use&&preg_match('/(?:^|\|)\s*(Medical\s*\/\s*Office|Retail|Office|Medical)\s*(?:\||$)/i',$suite['notes'],$m))$use=preg_replace('/\s*\/\s*/',' / ',$m[1]);
   $spaces[]=array('name'=>$suite['suite_name'],'size'=>$suite['square_feet']?$suite['square_feet'].' SF':'','use'=>$use,'rate'=>$suite['rate']?$suite['rate'].'/SF'.($suite['rate_type']?' '.$suite['rate_type']:''):'','status'=>$suite['availability_status']);
  }
  $space_facts=array();foreach(array('Space','Building','Site','Pricing','Parking') as $group)foreach($d['groups'][$group]??array() as $label=>$value)$space_facts[$label]=$value;
  foreach($facts as $key=>$fact)if(in_array($key,array('clear_height','loading_configuration','office_sf','warehouse_sf','grade_level_doors','availability_range','parking_type'),true))$space_facts[$fact['label']]=$fact['value'];
  if($type==='Office Condo'&&isset($snapshot['UNIT SIZE'],$snapshot['CONTIGUOUS'])){unset($space_facts['Unit / contiguous space']);$space_facts=array('Per unit'=>$snapshot['UNIT SIZE'],'Combinable'=>$snapshot['CONTIGUOUS'])+$space_facts;}
  return array('metrics'=>$metrics,'summary'=>implode(' ',array_slice($sentences,0,3)),'signals'=>array_slice(array_values($signals),0,5),'location'=>array_values($location),'spaces'=>$spaces,'space_facts'=>$space_facts,'known'=>array_slice(array_values($facts),0,6));
 }
 public static function data(int $id): array {
  $text=static fn($key)=>self::text($id,$key);$num=static fn($key,$suffix='')=>self::number($id,$key,$suffix);
  $city=$text('city');$state=$text('state');$zip=$text('postal_code');$title=sanitize_text_field(get_post_field('post_title',$id));
  // Suffix-only title cleanup also works in authenticated draft previews.
  if($city!==''&&$state!==''){$suffix='/(?:,\s*|\s+)'.preg_quote($city,'/').'(?:\s*,\s*|\s+)'.preg_quote($state,'/').($zip!==''?'(?:\s+'.preg_quote($zip,'/').')?':'').'\s*$/iu';$title=trim((string)preg_replace($suffix,'',$title)," ,\t\n\r\0\x0B")?:$title;}
  $title_address=$text('address_line_1');if($title_address!=='')$title=preg_replace('/\s+-\s+'.preg_quote($title_address,'/').'\s*$/iu','',$title);
  $terms=static function($taxonomy)use($id){$terms=wp_get_post_terms($id,$taxonomy,array('orderby'=>'term_id','order'=>'ASC'));return !is_wp_error($terms)&&$terms?$terms[0]:null;};
  $type=$terms('property_type');$transaction=$terms('transaction_type');$transactions=wp_get_post_terms($id,'transaction_type',array('orderby'=>'term_id','order'=>'ASC'));if(is_wp_error($transactions))$transactions=array();$intent_order=array('for-sale'=>0,'for-lease'=>1,'for-sale-or-lease'=>2,'ground-lease'=>3);usort($transactions,static fn($a,$b)=>($intent_order[$a->slug]??9)<=>($intent_order[$b->slug]??9));$land=($type->slug??'')==='land';
  $suppressed=in_array($id,array_map('intval',(array)get_option('aspire_atlas_suppressed_price_ids',array())),true);
  $pricing=array('leaseRateDisplay'=>$suppressed?null:$text('lease_rate_display'),'priceDisplay'=>$suppressed?null:$text('price_display'),'salePrice'=>!$suppressed&&has_term(array('for-sale','for-sale-or-lease'),'transaction_type',$id)?get_post_meta($id,'_aspire_sale_price',true):null);
  $rate=$suppressed?'':($pricing['leaseRateDisplay']??'');$price=$suppressed?'':($pricing['priceDisplay']??'');
  if(!$price&&!$suppressed&&is_numeric($pricing['salePrice']??null)&&(float)$pricing['salePrice']>0)$price='$'.self::format((float)$pricing['salePrice']);
  // Numeric lease ranges are only meaningful alongside the saved rate type.
  if(!$suppressed&&!$rate&&$text('lease_rate_type')&&$num('lease_rate_min')){$rate='$'.$num('lease_rate_min');if($num('lease_rate_max')&&get_post_meta($id,'_aspire_lease_rate_max',true)>get_post_meta($id,'_aspire_lease_rate_min',true))$rate.='–$'.$num('lease_rate_max');$rate.=' '.$text('lease_rate_type');}
  $hero=array_filter(array('AVAILABLE'=>$land?'':$num('available_sf',' SF'),'LEASE RATE'=>$rate,'BUILDING'=>$land?'':$num('building_sf',' SF'),'SITE'=>$num('lot_acres',' AC'),'SALE PRICE'=>$price),static fn($v)=>$v!=='');
  $groups=array(
   'Space'=>$land?array():array('Available'=>$num('available_sf',' SF'),'Building'=>$num('building_sf',' SF'),'Minimum available'=>$num('minimum_available_sf',' SF'),'Maximum contiguous'=>$num('maximum_contiguous_sf',' SF')),
   'Building'=>$land?array():array('Year built'=>str_replace(',','',$num('year_built')),'Renovated'=>str_replace(',','',$num('renovated_year')),'Building class'=>$text('building_class'),'Stories'=>$num('stories'),'Clear height'=>$num('clear_height_ft',' FT')),
   'Site'=>array('Land area'=>$num('lot_acres',' AC')),
   'Pricing'=>array('Lease rate'=>$rate,'Sale price'=>$price),
   'Parking'=>$land?array():array('Parking spaces'=>$num('parking_spaces'),'Parking ratio'=>$text('parking_ratio')),
   'Market'=>array('Traffic count'=>$num('traffic_count_vpd',' VPD')),
  );
  foreach($groups as $key=>$rows){$groups[$key]=array_filter($rows,static fn($v)=>$v!=='');if(!$groups[$key])unset($groups[$key]);}
  $notes=array();foreach(array('amenities'=>'Amenities','building_specifications'=>'Building specifications','demographic_notes'=>'Demographic notes') as $key=>$label){$lines=self::lines($id,$key);if($lines&&(!$land||$key!=='building_specifications'))$notes[$label]=$lines;}
  $suites=array();$raw=get_post_meta($id,'_aspire_suites',true);
  foreach(is_array($raw)?$raw:array() as $row){if(!is_array($row))continue;$clean=array();foreach(Aspire_Core_Fields::suites() as $key=>$kind){$v=Aspire_Core_Fields::clean($row[$key]??'',$kind);if(in_array($key,array('square_feet','rate'),true))$v=is_numeric($v)&&(float)$v>0?($key==='rate'?'$':'').self::format((float)$v):'';elseif($key==='availability_status')$v=isset($row[$key])&&in_array($row[$key],(array)$kind,true)?ucwords(str_replace('_',' ',$v)):'';if($suppressed&&in_array($key,array('rate','rate_type','notes'),true))$v='';$clean[$key]=$v;}if(array_filter($clean,static fn($v)=>$v!==''))$suites[]=$clean;}
  $columns=array('suite_name'=>'Suite','square_feet'=>'Square feet','rate'=>'Rate','rate_type'=>'Rate type','former_use'=>'Former use','availability_status'=>'Availability','notes'=>'Notes');foreach($columns as $key=>$label){if(!array_filter(array_column($suites,$key),static fn($v)=>$v!==''))unset($columns[$key]);}
  $gallery=array_values(array_unique(array_filter(array_map('intval',(array)get_post_meta($id,'_aspire_gallery_attachment_ids',true)),static fn($image)=>self::attachment($image,true))));
  $brochure=(int)get_post_meta($id,'_aspire_brochure_attachment_id',true);if(!self::attachment($brochure))$brochure=0;
  $brokers=array();foreach(array_unique(array_map('intval',get_post_meta($id,'_aspire_listing_broker_id',false))) as $broker){if(get_post_type($broker)==='team_member'&&get_post_status($broker)==='publish')$brokers[]=$broker;}
  $lat=get_post_meta($id,'_aspire_latitude',true);$lon=get_post_meta($id,'_aspire_longitude',true);
  $coordinates=is_numeric($lat)&&is_numeric($lon)&&is_finite((float)$lat)&&is_finite((float)$lon)&&abs((float)$lat)<=90&&abs((float)$lon)<=180?array((float)$lon,(float)$lat):null;
  $address=trim($text('address_line_1').' '.$text('address_line_2'));$locality=trim(implode(', ',array_filter(array($city,$state))).' '.$zip);
  // Only the selected public record is passed to the shared map renderer; no REST call or Atlas application.
  $map=$coordinates?array('type'=>'Feature','id'=>$id,'geometry'=>array('type'=>'Point','coordinates'=>$coordinates),'properties'=>array('title'=>$title,'displayTitle'=>$title,'listingStatus'=>metadata_exists('post',$id,'_aspire_listing_status')?$text('listing_status'):'','propertyType'=>array('slug'=>$type->slug??'','label'=>$type->name??''),'transactionType'=>array('slug'=>$transaction->slug??'','label'=>$transaction->name??''),'metrics'=>array(),'pricing'=>array(),'location'=>array('city'=>$city,'state'=>$state))):null;
  $intelligence=get_post_meta($id,Aspire_Property_Intelligence::META,true);
  $meta=array();foreach(Aspire_Core_Fields::groups('property') as $fields)foreach($fields as $key=>$kind)$meta[$key]=get_post_meta($id,'_aspire_'.$key,true);
  $lens=Aspire_Property_Lens::present($type->name??'',is_array($intelligence)?$intelligence:array(),$meta,$suites,$suppressed);
  $snapshot=$hero;
  foreach(array('clear_height'=>'CLEAR HEIGHT','office_sf'=>'OFFICE','warehouse_sf'=>'WAREHOUSE','building_class'=>'CLASS','parking_spaces'=>'PARKING','traffic_counts_by_road'=>'TRAFFIC','availability_range'=>'UNIT / CONTIGUOUS SPACE') as $key=>$label){
   if(isset($lens['facts'][$key])&&count($snapshot)<6)$snapshot[$label]=$lens['facts'][$key]['value'];
  }
  if($type&&$type->name==='Office Condo'&&isset($lens['facts']['availability_range'])){
   $range=$lens['facts']['availability_range']['value'];
   if(preg_match('/Each Unit is ([\d,]+) SF/i',$range,$m)){unset($snapshot['UNIT / CONTIGUOUS SPACE']);$snapshot['UNIT SIZE']=$m[1].' SF';}
   if(preg_match('/up to a total of ([\d,]+) SF contiguous space (\(one building\))/i',$range,$m))$snapshot['CONTIGUOUS']='Up to '.$m[1].' SF '.$m[2];
  }
  if($type&&$type->name==='Retail'){
   $areas=array_map(static fn($suite)=>(float)str_replace(',','',$suite['square_feet']),array_filter($suites,static fn($suite)=>$suite['square_feet']!==''));
   if(count($areas)>1)$snapshot['LISTED SUITE SIZES']=self::format(min($areas)).'–'.self::format(max($areas)).' SF';
  }
  $featured=(int)get_post_thumbnail_id($id);$photos=array_values(array_unique(array_merge(self::attachment($featured,true)?array($featured):array(),$gallery)));
  return compact('id','title','type','transaction','hero','groups','notes','suites','columns','gallery','brochure','brokers','address','locality','map')+array('highlights'=>self::lines($id,'property_highlights'),'transactions'=>$transactions,'photos'=>$photos,'lens'=>$lens,'snapshot'=>$snapshot,'has_body'=>trim((string)get_post_field('post_content',$id))!=='');
 }
}
