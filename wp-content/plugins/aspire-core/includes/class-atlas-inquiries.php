<?php
/** Private local Atlas enquiries. No public read API or outbound email. */
defined('ABSPATH') || exit;
final class Aspire_Atlas_Inquiries {
 public static function schema(): array {
  static $schema;
  return $schema ??= json_decode(file_get_contents(dirname(ASPIRE_CORE_FILE).'/atlas/brief-schema.json'),true,512,JSON_THROW_ON_ERROR);
 }
 public static function register(): void {
  register_post_type('atlas_inquiry',array(
   'labels'=>array('name'=>'Atlas Enquiries','singular_name'=>'Atlas Enquiry','edit_item'=>'View Atlas Enquiry','search_items'=>'Search Atlas Enquiries'),
   'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>false,'show_in_menu'=>false,'show_in_rest'=>false,'has_archive'=>false,'rewrite'=>false,'query_var'=>false,
   'supports'=>false,'menu_icon'=>'dashicons-clipboard','map_meta_cap'=>false,
   'capabilities'=>array('edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options','edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'do_not_allow','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','delete_private_posts'=>'manage_options','delete_published_posts'=>'manage_options','delete_others_posts'=>'manage_options','edit_private_posts'=>'manage_options','edit_published_posts'=>'manage_options','create_posts'=>'do_not_allow'),
  ));
 }
 public static function routes(): void {
  register_rest_route('aspire/v1','/atlas/inquiries',array('methods'=>'POST','permission_callback'=>array(self::class,'permission'),'callback'=>array(self::class,'submit')));
 }
 private static function error(string $message,int $status=400): WP_Error {return new WP_Error('atlas_inquiry_invalid',$message,array('status'=>$status));}
 public static function permission(WP_REST_Request $request) {
  // Guest nonces deter cross-site submission, not bots. Cookie-authenticated requests also use WP's REST nonce.
  $nonce=$request->get_param('nonce');
  if(!is_string($nonce)||!wp_verify_nonce($nonce,'aspire_atlas_submit'))return self::error('Your session has expired. Reload Atlas before sending your brief.',403);
  return true;
 }
 private static function keys($value,array $keys): bool {return is_array($value)&&count($value)===count($keys)&&!array_diff(array_keys($value),$keys);}
 private static function text($value,int $max): bool {return is_string($value)&&mb_strlen($value)<=$max;}
 private static function one($value,array $options): bool {return is_string($value)&&array_key_exists($value,$options);}
 private static function many($value,array $options,bool $required=true): bool {
  return is_array($value)&&array_is_list($value)&&(!$required||count($value)>0)&&count($value)<=count($options)&&count($value)===count(array_unique($value,SORT_REGULAR))&&count(array_filter($value,static fn($v)=>self::one($v,$options)))===count($value)&&(!in_array('unsure',$value,true)||count($value)===1);
 }
 public static function validate_brief($b) {
  $s=self::schema();
  if(!self::keys($b,array('goal','propertyTypes','location','size','budget','transaction','timing','priorities','managementNeeds','ownerIntent')))return self::error('The brief has unexpected or missing fields.');
  if(!self::one($b['goal'],$s['goals'])||!self::many($b['propertyTypes'],$s['propertyTypes'])||count($b['propertyTypes'])!==1||!self::one($b['size'],$s['sizes'])||!self::one($b['budget'],$s['budgets'])||!self::one($b['timing'],$s['timing']))return self::error('Choose valid goal, property type, size, budget and timing answers.');
  if(!self::keys($b['location'],array('text','areaPreset'))||!self::text($b['location']['text'],240)||!self::one($b['location']['areaPreset'],array_merge(array(''=>true),$s['areas'])))return self::error('Choose a valid Houston area and a location under 240 characters.');
  $investment=in_array($b['goal'],array('buy_property','invest'),true);$owner=in_array($b['goal'],array('lease_property','sell_property','lease_or_sell'),true);$management=$b['goal']==='manage_asset';$land=$b['propertyTypes']===array('land');
  if($b['propertyTypes']===array('mixed-use')&&!$investment&&!$owner&&!$management)return self::error('Choose a property type appropriate to your goal.');
  $size=$s['sizes'][$b['size']];
  if($size['unit']!==null&&$size['unit']!==($land?'acres':'sf'))return self::error('The size unit must match your property type.');
  if(!$investment&&$b['budget']!=='any')return self::error('Budget is only applicable to buying or investing.');
  $transaction=match($b['goal']){'lease_property'=>'for-lease','sell_property','buy_property','invest'=>'for-sale','lease_or_sell'=>'for-sale-or-lease',default=>''};
  if($b['goal']==='lease_space'?!in_array($b['transaction'],array('','for-lease','for-sale-or-lease'),true):$b['transaction']!==$transaction)return self::error('Transaction does not match your goal.');
  $intents=match($b['goal']){'lease_property'=>array('lease'),'sell_property'=>array('sell'),'lease_or_sell'=>array('both','unsure'),default=>array(null)};
  if(!in_array($b['ownerIntent'],$intents,true))return self::error('Owner intent does not match your goal.');
  $priorityKeys=$s['priorityGroups'][$owner?'owner':'seeker'];
  if(!$owner)$priorityKeys=array_diff($priorityKeys,$s['irrelevantPriorities'][$b['propertyTypes'][0]]??array());
  $priorities=array_intersect_key($s['priorities'],array_flip($priorityKeys));
  if($management){if($b['priorities']!==array()||!self::many($b['managementNeeds'],$s['managementNeeds']))return self::error('Select valid management needs.');}
  elseif($b['managementNeeds']!==array()||!self::many($b['priorities'],$priorities))return self::error('Select valid priorities for your goal.');
  $b['location']['text']=sanitize_text_field($b['location']['text']);
  return $b;
 }
 private static function in_range($value,?array $range): bool {return !$range||(is_numeric($value)&&is_finite((float)$value)&&(float)$value>0&&(float)$value>=$range[0]&&($range[1]===null||(float)$value<$range[1]));}
 public static function matches(array $b,array $features): array {
  if(!in_array($b['goal'],array('lease_space','buy_property','invest'),true))return array();
  $s=self::schema();$investment=$b['goal']!=='lease_space';$range=$s['sizes'][$b['size']]['range'];$budget=$investment?$s['budgets'][$b['budget']]['range']:null;$box=$s['areas'][$b['location']['areaPreset']]['bounds']??null;$matches=array();
  foreach($features as $f){
   $p=$f['properties'];$t=$p['transactionType']['slug']??'';$transaction=$b['transaction'];
   if($transaction&&$transaction!==$t&&!($transaction!=='for-sale-or-lease'&&$t==='for-sale-or-lease'))continue;
   if(!in_array($p['propertyType']['slug']??'',$b['propertyTypes'],true))continue;
   [$x,$y]=$f['geometry']['coordinates'];if($box&&!($x>=$box[0][0]&&$x<=$box[1][0]&&$y>=$box[0][1]&&$y<=$box[1][1]))continue;
   $m=$p['metrics'];$value=$b['propertyTypes']===array('land')?$m['lotAcres']:($investment?$m['buildingSf']:($m['availableSf']??(($p['propertyType']['slug']??'')!=='land'?$m['buildingSf']:null)));
   if(!self::in_range($value,$range)||!self::in_range($p['pricing']['salePrice'],$budget))continue;
   $matches[]=array('id'=>(int)$f['id'],'strength'=>$transaction&&$t===$transaction?2:1);
  }
  usort($matches,static fn($a,$c)=>($c['strength']<=>$a['strength'])?:($a['id']<=>$c['id']));return array_column($matches,'id');
 }
 public static function summary(array $b): string {
  $s=self::schema();$lines=array('Goal: '.$s['goals'][$b['goal']],'Property type: '.implode(', ',array_map(static fn($t)=>$s['propertyTypes'][$t],$b['propertyTypes'])),'Area: '.implode(' · ',array_filter(array($b['location']['text'],$s['areas'][$b['location']['areaPreset']]['label']??'All Houston / flexible'))),'Approximate size: '.$s['sizes'][$b['size']]['label']);
  if(in_array($b['goal'],array('buy_property','invest'),true))$lines[]='Budget: '.$s['budgets'][$b['budget']]['label'];
  if($b['goal']==='lease_space')$lines[]='Transaction: '.array(''=>'All Transactions','for-lease'=>'For Lease','for-sale-or-lease'=>'For Sale or Lease')[$b['transaction']];
  if($b['ownerIntent'])$lines[]='Owner intent: '.$s['ownerIntents'][$b['ownerIntent']];
  $lines[]='Timing: '.$s['timing'][$b['timing']];$management=$b['goal']==='manage_asset';
  $lines[]=($management?'Management needs: ':'Priorities: ').implode(', ',array_map(static fn($k)=>$s[$management?'managementNeeds':'priorities'][$k],$b[$management?'managementNeeds':'priorities']));
  return implode("\n",$lines);
 }
 public static function submit(WP_REST_Request $request) {
  if(strlen($request->get_body())>12000)return self::error('This submission is too large.',413);
  $payload=$request->get_json_params();
  if(!self::keys($payload,array('nonce','contact','brief','website')))return self::error('The submission has unexpected or missing fields.');
  if(!is_string($payload['website'])||$payload['website']!=='')return self::error('The submission could not be accepted.');
  // Ignore spoofable forwarding headers. Hash the connection IP; never store the raw address.
  $key='atlas_inquiry_rate_'.hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'local'),wp_salt('nonce'));
  $rate=get_transient($key);$now=time();if(!is_array($rate)||$rate['until']<=$now)$rate=array('attempts'=>0,'saved'=>0,'until'=>$now+600);
  if($rate['attempts']>=20||$rate['saved']>=5)return self::error('Too many submissions. Please wait ten minutes before trying again.',429);
  ++$rate['attempts'];set_transient($key,$rate,max(1,$rate['until']-$now));
  $c=$payload['contact'];
  if(!self::keys($c,array('name','email','phone','company','consent'))||!self::text($c['name'],120)||!self::text($c['email'],254)||!self::text($c['phone'],50)||!self::text($c['company'],120)||!is_bool($c['consent']))return self::error('Enter valid contact details.');
  $c=array('name'=>sanitize_text_field($c['name']),'email'=>sanitize_email($c['email']),'phone'=>sanitize_text_field($c['phone']),'company'=>sanitize_text_field($c['company']),'consent'=>$c['consent']);
  if($c['name']===''||!is_email($payload['contact']['email'])||!is_email($c['email']))return self::error('Enter your name and a valid email address.');
  $b=self::validate_brief($payload['brief']);if(is_wp_error($b))return $b;
  // Recompute IDs from current publishable Atlas inventory, including price suppression.
  $ids=self::matches($b,Aspire_Atlas::collection()['features']);$summary=self::summary($b);
  $id=wp_insert_post(wp_slash(array('post_type'=>'atlas_inquiry','post_status'=>'private','post_title'=>$c['name'].' — '.self::schema()['goals'][$b['goal']],'meta_input'=>array('_atlas_contact'=>$c,'_atlas_brief'=>$b,'_atlas_summary'=>$summary,'_atlas_received_at'=>gmdate('c'),'_atlas_source'=>'Aspire Atlas','_atlas_matched_property_ids'=>$ids))),true);
  if(is_wp_error($id)||!$id)return self::error('Your brief could not be saved. Please try again.',500);
  ++$rate['saved'];set_transient($key,$rate,max(1,$rate['until']-$now));
  $response=new WP_REST_Response(array('received'=>true),201);$response->header('Cache-Control','no-store');return $response;
 }
}
add_action('init',array(Aspire_Atlas_Inquiries::class,'register'));
add_action('rest_api_init',array(Aspire_Atlas_Inquiries::class,'routes'));
require_once __DIR__.'/class-atlas-enquiry-admin.php';
