<?php
/** Real local REST/storage checks. Every created enquiry and rate transient is removed. */
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,4).'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/template.php';
$checks=0;$created=array();$original_user=get_current_user_id();$original_ip=$_SERVER['REMOTE_ADDR']??null;$_SERVER['REMOTE_ADDR']='atlas-contract-'.wp_generate_uuid4();
$key='atlas_inquiry_rate_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR'],wp_salt('nonce'));
function inquiry_check($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);++$checks;echo "PASS: $label\n";}
function inquiry_request($body,$method='POST',$route='/aspire/v1/atlas/inquiries'){$r=new WP_REST_Request($method,$route);$r->set_header('Content-Type','application/json');if($body!==null)$r->set_body(is_string($body)?$body:wp_json_encode($body));return rest_do_request($r);}
wp_set_current_user(0);
$brief=array('goal'=>'lease_space','propertyTypes'=>array('industrial-flex'),'location'=>array('text'=>'<b>West Houston</b>','areaPreset'=>'west'),'size'=>'large','budget'=>'any','transaction'=>'for-lease','timing'=>'3_6_months','priorities'=>array('loading','parking'),'managementNeeds'=>array(),'ownerIntent'=>null);
$body=array('nonce'=>wp_create_nonce('aspire_atlas_submit'),'contact'=>array('name'=>'Atlas Contract Test','email'=>'atlas-test@example.invalid','phone'=>'','company'=>'<b>Test</b>','consent'=>true),'brief'=>$brief,'website'=>'');
try{
 $type=get_post_type_object('atlas_inquiry');inquiry_check($type&&!$type->public&&!$type->publicly_queryable&&!$type->show_in_rest&&$type->exclude_from_search,'Enquiry model private and absent from REST/search');
 inquiry_check(!current_user_can('edit_posts','atlas_inquiry')&&!current_user_can('manage_options'),'Anonymous visitor cannot administer enquiries');
 inquiry_check(inquiry_request(null,'GET')->get_status()===404&&inquiry_request(null,'GET','/wp/v2/atlas_inquiry')->get_status()===404,'No public list/read REST route');
 $invalid=$body;$invalid['nonce']='bad';inquiry_check(inquiry_request($invalid)->get_status()===403,'Invalid nonce rejected');
 inquiry_check(inquiry_request('{bad')->get_status()===400,'Malformed JSON rejected');
 $invalid=$body;$invalid['website']='spam';inquiry_check(inquiry_request($invalid)->get_status()===400,'Honeypot rejected');
 $invalid=$body;$invalid['matchedPropertyIds']=array(122);inquiry_check(inquiry_request($invalid)->get_status()===400,'Client-injected matched IDs rejected');
 $invalid=$body;$invalid['contact']['email']='not an email';inquiry_check(inquiry_request($invalid)->get_status()===400,'Invalid email rejected');
 $invalid=$body;$invalid['contact']['name']=' ';inquiry_check(inquiry_request($invalid)->get_status()===400,'Blank name rejected');
 $invalid=$body;$invalid['contact']['consent']='yes';inquiry_check(inquiry_request($invalid)->get_status()===400,'Consent must be boolean');
 $invalid=$body;$invalid['brief']['size']='acMedium';inquiry_check(inquiry_request($invalid)->get_status()===400,'Cross-unit size rejected');
 $invalid=$body;$invalid['brief']['location']['coordinates']=array(1,2);inquiry_check(inquiry_request($invalid)->get_status()===400,'Unexpected location/coordinate data rejected');
 $invalid=$body;$invalid['brief']['priorities']=array('unsure','loading');inquiry_check(inquiry_request($invalid)->get_status()===400,'Conflicting priority values rejected');
 $invalid=$body;$invalid['brief']['propertyTypes']=array(array('land'));inquiry_check(inquiry_request($invalid)->get_status()===400,'Nested enum payload rejected without PHP error');
 $invalid=$body;$invalid['brief']['transaction']='for-sale';inquiry_check(inquiry_request($invalid)->get_status()===400,'Goal/transaction mismatch rejected');
 $clean=Aspire_Atlas_Inquiries::validate_brief($brief);inquiry_check(!is_wp_error($clean)&&$clean['location']['text']==='West Houston','Location sanitized');
 $collection=Aspire_Atlas::collection()['features'];$expected=Aspire_Atlas_Inquiries::matches($clean,$collection);inquiry_check($expected===array(120),'Deterministic lease match uses current inventory');
 $land=$clean;$land['goal']='invest';$land['transaction']='for-sale';$land['propertyTypes']=array('land');$land['size']='acMedium';$land['location']['areaPreset']='katy';$land['priorities']=array('growth');$land['budget']='two';
 inquiry_check(!is_wp_error(Aspire_Atlas_Inquiries::validate_brief($land))&&Aspire_Atlas_Inquiries::matches($land,$collection)===array(),'Suppressed price cannot match investment budget');
 $land['budget']='any';inquiry_check(Aspire_Atlas_Inquiries::matches($land,$collection)===array(122),'Land matching uses acreage');
 foreach(array('lease_property'=>'lease','sell_property'=>'sell','lease_or_sell'=>'unsure','manage_asset'=>null,'buy_property'=>null) as $goal=>$intent){$b=$clean;$b['goal']=$goal;$b['ownerIntent']=$intent;$b['transaction']=match($goal){'lease_property'=>'for-lease','sell_property','buy_property'=>'for-sale','lease_or_sell'=>'for-sale-or-lease',default=>''};$b['priorities']=$goal==='manage_asset'?array():($goal==='buy_property'?array('parking'):array('maximize_value'));$b['managementNeeds']=$goal==='manage_asset'?array('financial'):array();inquiry_check(!is_wp_error(Aspire_Atlas_Inquiries::validate_brief($b)),"Valid conditional goal: $goal");}
 delete_transient($key);
 $before=get_posts(array('post_type'=>'atlas_inquiry','post_status'=>'any','numberposts'=>-1,'fields'=>'ids'));
 $response=inquiry_request($body);inquiry_check($response->get_status()===201&&$response->get_data()===array('received'=>true),'Anonymous submission saved without email infrastructure or exposing IDs');
 $after=get_posts(array('post_type'=>'atlas_inquiry','post_status'=>'any','numberposts'=>-1,'fields'=>'ids'));$created=array_values(array_diff($after,$before));inquiry_check(count($created)===1,'Exactly one enquiry created');$id=$created[0];
 inquiry_check(get_post_status($id)==='private','Saved enquiry has private status');
 inquiry_check(get_post_meta($id,'_atlas_matched_property_ids',true)===$expected,'Server computes stored matching IDs');
 inquiry_check(get_post_meta($id,'_atlas_brief',true)===$clean,'Normalized data stored');
 inquiry_check(get_post_meta($id,'_atlas_contact',true)['company']==='Test','Contact sanitization stored');
 inquiry_check(get_post_meta($id,'_atlas_source',true)==='Aspire Atlas'&&get_post_meta($id,'_atlas_received_at',true)!=='','Source and UTC timestamp stored');
 inquiry_check(str_contains(get_post_meta($id,'_atlas_summary',true),'Industrial / Flex')&&!str_contains(get_post_meta($id,'_atlas_summary',true),'lease_space'),'Readable summary stored');
 inquiry_check(inquiry_request(null,'GET','/wp/v2/atlas_inquiry/'.$id)->get_status()===404,'Individual enquiry REST read unavailable');
 wp_set_current_user(get_users(array('role'=>'administrator','number'=>1))[0]->ID);inquiry_check(current_user_can('edit_post',$id),'Administrator can view enquiry');ob_start();Aspire_Atlas_Enquiry_Admin::details(get_post($id));$html=ob_get_clean();inquiry_check(str_contains($html,'Atlas Contract Test')&&str_contains($html,'Real estate brief')&&str_contains($html,'16840 Clay Road'),'Readable admin details include contact, summary and property link');inquiry_check(!str_contains($html,'name="post_title"')&&!str_contains($html,'name="content"')&&!post_type_supports('atlas_inquiry','editor')&&!post_type_supports('atlas_inquiry','title'),'No title or content editor in enquiry UI');
 inquiry_check(Aspire_Atlas_Enquiry_Admin::status($id)==='new','Old enquiries without status default to New');
 $snapshot=get_post_meta($id,'_atlas_brief',true);$nonce=wp_create_nonce('atlas_enquiry_update_'.$id);
 inquiry_check(is_wp_error(Aspire_Atlas_Enquiry_Admin::save($id,'qualified','Notes','bad')),'Invalid admin nonce cannot save');
 inquiry_check(is_wp_error(Aspire_Atlas_Enquiry_Admin::save($id,'published','Notes',$nonce)),'Invalid internal status rejected');
 inquiry_check(is_wp_error(Aspire_Atlas_Enquiry_Admin::save($id,'qualified',str_repeat('a',10001),$nonce)),'Oversized internal notes rejected');
 inquiry_check(Aspire_Atlas_Enquiry_Admin::save($id,'qualified',"<b>Call</b> next week.\nPrivate note",$nonce)===true,'Admin status and notes save');
 inquiry_check(Aspire_Atlas_Enquiry_Admin::status($id)==='qualified'&&get_post_meta($id,'_atlas_internal_notes',true)==="Call next week.\nPrivate note",'Notes sanitized and multiline retained');
 inquiry_check(get_post_meta($id,'_atlas_brief',true)===$snapshot&&get_post($id)->post_content===''&&get_post_status($id)==='private','Admin update preserves submitted brief and private storage');
 inquiry_check(str_contains(get_edit_post_link($id),'page=aspire-atlas-enquiries'),'Enquiry links target custom detail screen');
 wp_set_current_user(0);
 inquiry_check(is_wp_error(Aspire_Atlas_Enquiry_Admin::save($id,'closed','Intrusion',$nonce))&&Aspire_Atlas_Enquiry_Admin::status($id)==='qualified','Anonymous update blocked without mutation');
 inquiry_check(!str_contains(wp_json_encode(inquiry_request(null,'GET','/aspire/v1/atlas/properties')->get_data()),'Private note'),'Internal notes absent from public Atlas data');
 set_transient($key,array('attempts'=>20,'saved'=>0,'until'=>time()+600),600);inquiry_check(inquiry_request($body)->get_status()===429,'Attempt rate limit enforced');
 set_transient($key,array('attempts'=>5,'saved'=>5,'until'=>time()+600),600);inquiry_check(inquiry_request($body)->get_status()===429,'Successful submission rate limit enforced');
}finally{foreach($created as $id)wp_delete_post($id,true);delete_transient($key);wp_set_current_user($original_user);if($original_ip===null)unset($_SERVER['REMOTE_ADDR']);else $_SERVER['REMOTE_ADDR']=$original_ip;}
echo "Atlas enquiry tests: $checks assertions passed.\n";
