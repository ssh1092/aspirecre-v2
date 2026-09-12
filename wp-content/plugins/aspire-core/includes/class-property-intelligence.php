<?php
/** Protected property intelligence and human-readable native administration. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Property_Intelligence {
 const META = '_aspire_property_intelligence';
 const SOURCE = '_aspire_migration';
 public static function init() {
  add_action('init',array(__CLASS__,'register'));
  add_action('save_post_property',array(__CLASS__,'save'),20,2);
 }
 public static function catalog() { return json_decode(file_get_contents(__DIR__.'/intelligence-fields.json'),true); }
 public static function type($id) {
  $names=wp_get_object_terms($id,'property_type',array('fields'=>'names'));
  foreach(array('Office Condo','Industrial / Flex','Retail','Land','Office') as $type) if(!is_wp_error($names)&&in_array($type,$names,true))return $type;
  return 'Office';
 }
 public static function sanitize_tree($value,$depth=0) {
  if($depth>18)return null;
  if(is_array($value)) { $out=array();foreach(array_slice($value,0,500,true) as $k=>$v)$out[is_int($k)?$k:sanitize_text_field($k)]=self::sanitize_tree($v,$depth+1);return $out; }
  if(is_bool($value)||is_int($value)||is_float($value)||is_null($value))return $value;
  return sanitize_textarea_field(substr((string)$value,0,12000));
 }
 public static function sanitize($input) {
  if(!is_array($input))return array();
  $type=$input['property_type']??'';$catalog=self::catalog();if(!isset($catalog[$type]))return array();
  $result=array('schema_version'=>1,'property_type'=>$type,'facts'=>array(),'decision_field_status'=>array());
  foreach($catalog[$type] as $field) {
   $status=$input['decision_field_status'][$field]??'unknown';$fact=$input['facts'][$field]??null;
   if(!in_array($status,array('known','unknown','conflicting'),true))$status='unknown';
   if($status==='known'&&is_array($fact)&&isset($fact['value'])&&$fact['value']!==''&&$fact['value']!==array())$result['facts'][$field]=self::sanitize_tree($fact);
   elseif($status==='known')$status='unknown';
   $result['decision_field_status'][$field]=$status;
  }
  return $result;
 }
 public static function register() {
  foreach(array(self::META,self::SOURCE) as $key)register_post_meta('property',$key,array('type'=>'object','single'=>true,'show_in_rest'=>false,'sanitize_callback'=>($key===self::META?array(__CLASS__,'sanitize'):static fn($value)=>self::sanitize_tree($value)),'auth_callback'=>static fn($allowed,$key,$id)=>current_user_can('edit_post',$id)));
  foreach(array('_aspire_legacy_asset_url','_aspire_legacy_asset_sha256','_aspire_legacy_media_sources') as $key)register_post_meta('attachment',$key,array('single'=>true,'show_in_rest'=>false,'sanitize_callback'=>static fn($value)=>self::sanitize_tree($value),'auth_callback'=>static fn($allowed,$key,$id)=>current_user_can('edit_post',$id)));
 }
 public static function label($field) {
  return array('clear_height'=>'Clear Height (ft)','office_sf'=>'Office Area (SF)','warehouse_sf'=>'Warehouse Area (SF)','24_7_access'=>'24/7 Access','HVAC'=>'HVAC','ETJ'=>'ETJ','traffic_counts_by_road'=>'Traffic Counts by Road (VPD)')[$field]??ucwords(str_replace('_',' ',$field));
 }
 public static function display($value) {
  if(is_scalar($value))return (string)$value;
  $lines=array();foreach((array)$value as $v) {
   if(is_scalar($v))$lines[]=(string)$v;
   elseif(is_array($v)&&isset($v['vpd']))$lines[]=($v['road']??'Road not specified').' — '.$v['vpd'].' VPD';
   elseif(is_array($v)&&isset($v['lot_acres']))$lines[]=($v['context']??'Offering').' — '.$v['lot_acres'].' acres';
  }return implode("\n",array_unique($lines));
 }
 public static function field($field,$data) {
  $status=$data['decision_field_status'][$field]??'unknown';$value=$data['facts'][$field]['value']??'';$id='intelligence-'.$field;
  echo '<div class="aspire-intelligence-field" style="margin:16px 0"><label for="'.esc_attr($id).'"><strong>'.esc_html(self::label($field)).'</strong></label> <select aria-label="'.esc_attr(self::label($field).' status').'" name="aspire_intelligence[status]['.esc_attr($field).']">';
  foreach(array('known','unknown','conflicting') as $s)echo '<option value="'.esc_attr($s).'" '.selected($status,$s,false).'>'.esc_html(ucfirst($s)).'</option>';
  echo '</select><textarea class="widefat" rows="2" id="'.esc_attr($id).'" name="aspire_intelligence[value]['.esc_attr($field).']">'.esc_textarea(self::display($value)).'</textarea></div>';
 }
 public static function intelligence_box($post) { Aspire_Property_Workspace::intelligence($post); }
 public static function source_box($post) {
  $data=get_post_meta($post->ID,self::SOURCE,true);if(!$data){echo '<p>No migration source recorded.</p>';return;}
  echo '<dl>';
  foreach(array('source_url'=>'Legacy Aspire URL','migration_id'=>'Migration ID','imported_at'=>'Imported / enriched','review_status'=>'Review status','coordinate_status'=>'Coordinate review','primary_image_review_required'=>'Primary image needs review') as $key=>$label) {
   $value=$data[$key]??'';if(is_bool($value))$value=$value?'Yes — local draft candidate':'No';
   echo '<dt><strong>'.esc_html($label).'</strong></dt><dd>'.($key==='source_url'?'<a href="'.esc_url($value).'" target="_blank" rel="noopener">'.esc_html($value).'</a>':esc_html($value)).'</dd>';
  }echo '</dl><p><strong>Coordinates:</strong> '.esc_html($data['coordinate_source']??'').'</p>';
  $warnings=array_values(array_filter($data['warnings']??array(),static fn($w)=>!($w==='needs_geocoding'&&($data['coordinate_status']??'')==='ACCEPTED')));
  if($warnings){echo '<p><strong>Review notes</strong></p><ul>';foreach($warnings as $w)echo '<li>'.esc_html(ucwords(str_replace('_',' ',$w))).'</li>';echo '</ul>';}
  if(!empty($data['contact_candidates'])){echo '<details><summary>POSSIBLE PROPERTY CONTACTS — Not assigned</summary><ul>';foreach($data['contact_candidates'] as $c)echo '<li>'.esc_html(($c['name']??'').' · '.($c['email']??'').' · '.($c['phone']??'')).'</li>';echo '</ul><p>Review candidates only. No public display or broker assignment.</p></details>';}
 }
 public static function save($id,$post) {
  if(wp_is_post_revision($id)||wp_is_post_autosave($id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
  if(!isset($_POST['aspire_intelligence_nonce'])||!is_string($_POST['aspire_intelligence_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aspire_intelligence_nonce'])),'aspire_intelligence_'.$id))return;
  $input=isset($_POST['aspire_intelligence'])&&is_array($_POST['aspire_intelligence'])?wp_unslash($_POST['aspire_intelligence']):array();$old=get_post_meta($id,self::META,true);
  $data=array('property_type'=>self::type($id),'facts'=>$old['facts']??array(),'decision_field_status'=>$old['decision_field_status']??array());
  foreach(self::catalog()[$data['property_type']] as $field) {
   if(!isset($input['status'][$field])||!is_scalar($input['status'][$field])||!isset($input['value'][$field])||!is_scalar($input['value'][$field]))continue;
   $status=sanitize_key($input['status'][$field]);$text=sanitize_textarea_field($input['value'][$field]);$previous=$old['facts'][$field]['value']??'';
   $data['decision_field_status'][$field]=$status;
   if($text===self::display($previous))continue; // Preserve structured evidence on unchanged form submissions.
   $value=array_values(array_filter(array_map('trim',explode("\n",$text)),'strlen'));
   if(in_array($field,array('clear_height','office_sf','warehouse_sf','building_size','acreage'),true))$value=Aspire_Core_Fields::clean($text,'number');
   if($field==='traffic_counts_by_road') {
    $value=array();foreach(explode("\n",$text) as $line)if(preg_match('/^(.*?)\s*[—–:]\s*([\d,]+)\s*(?:VPD)?$/i',$line,$m))$value[]=array('road'=>trim($m[1])==='Road not specified'?null:trim($m[1]),'vpd'=>(int)str_replace(',','',$m[2]));
   }
   $data['facts'][$field]=array('value'=>$value,'attribution'=>'Reviewed in WordPress admin');
  }
  update_post_meta($id,self::META,wp_slash(self::sanitize($data)));
 }
}
Aspire_Property_Intelligence::init();
