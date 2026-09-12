<?php
/** Property editing within the native WordPress form. Existing save handlers remain authoritative. */
defined('ABSPATH') || exit;
final class Aspire_Property_Workspace {
 public static function init() {
  add_action('admin_init',static function(){remove_post_type_support('property','editor');remove_post_type_support('property','excerpt');remove_post_type_support('property','custom-fields');});
  add_action('add_meta_boxes_property',array(__CLASS__,'boxes'),100);
  add_action('edit_form_after_title',array(__CLASS__,'render'));
  add_action('admin_enqueue_scripts',array(__CLASS__,'assets'));
 }
 public static function boxes() {
  foreach(array('postimagediv','property_typediv','transaction_typediv','postexcerpt','postcustom','authordiv','slugdiv','commentstatusdiv','commentsdiv','trackbacksdiv','revisionsdiv') as $id)foreach(array('normal','side','advanced') as $context)remove_meta_box($id,'property',$context);
 }
 public static function assets() {
  $screen=get_current_screen();if(!$screen||$screen->base!=='post'||$screen->post_type!=='property')return;
  wp_enqueue_media();
  foreach(array('property-tabs','property-workspace','property-map') as $file)wp_enqueue_script('aspire-'.$file,plugins_url('assets/'.$file.'.js',ASPIRE_CORE_FILE),$file==='property-workspace'?array('media-views','aspire-property-tabs','post'):array(),(string)filemtime(dirname(__DIR__).'/assets/'.$file.'.js'),true);
  wp_enqueue_style('aspire-property-workspace',plugins_url('assets/property-workspace.css',ASPIRE_CORE_FILE),array(),(string)filemtime(dirname(__DIR__).'/assets/property-workspace.css'));
 }
 public static function fields($post,array $keys) {
  $all=array_merge(...array_values(Aspire_Core_Fields::groups('property')));
  echo '<div class="aspire-fields">';foreach($keys as $key){if(!isset($all[$key]))continue;$value=get_post_meta($post->ID,'_aspire_'.$key,true);if($key!=='state'&&!metadata_exists('post',$post->ID,'_aspire_'.$key))$value='';
   echo '<div><label for="aspire-'.esc_attr($key).'">'.esc_html(array('lease_rate_display'=>'Lease pricing','price_display'=>'Price display','postal_code'=>'ZIP','maximum_contiguous_sf'=>'Maximum contiguous SF','minimum_available_sf'=>'Minimum available SF')[$key]??Aspire_Core_Admin::label($key)).'</label>';Aspire_Core_Admin::control('aspire['.$key.']',$all[$key],$value,'aspire-'.$key);echo '</div>';
  }echo '</div>';
 }
 public static function taxonomy($post,$taxonomy,$label) {
  $selected=wp_get_object_terms($post->ID,$taxonomy,array('fields'=>'ids'));if(is_wp_error($selected))$selected=array();$terms=get_terms(array('taxonomy'=>$taxonomy,'hide_empty'=>false));
  echo '<fieldset class="pw-taxonomy"><legend>'.esc_html($label).'</legend><input type="hidden" name="tax_input['.esc_attr($taxonomy).'][]" value="0">';
  if(!is_wp_error($terms))foreach($terms as $term)echo '<label><input type="checkbox" name="tax_input['.esc_attr($taxonomy).'][]" value="'.esc_attr($term->term_id).'" '.checked(in_array($term->term_id,$selected,true),true,false).'> '.esc_html($term->name).'</label>';
  echo '</fieldset>';
 }
 public static function overview($post) {
  echo '<h2>Property identity</h2><label class="pw-title-label" for="title">Property name</label><div data-native-title></div>';self::taxonomy($post,'property_type','Property type');self::taxonomy($post,'transaction_type','Transaction types');self::fields($post,array('listing_status','featured_property'));
  echo '<h2>Address</h2>';self::fields($post,array('address_line_1','address_line_2','city','state','postal_code'));
  $type=Aspire_Property_Intelligence::type($post->ID);
  $keys=match($type){
   'Industrial / Flex'=>array('available_sf','building_sf','lot_acres','clear_height_ft','lease_rate_display','year_built','parking_spaces'),
   'Retail'=>array('available_sf','minimum_available_sf','maximum_contiguous_sf','lot_acres','traffic_count_vpd','lease_rate_display'),
   'Office Condo'=>array('available_sf','building_sf','maximum_contiguous_sf','parking_spaces','price_display','lease_rate_display'),
   'Land'=>array('lot_acres','sale_price','price_display'),
   default=>array('available_sf','building_sf','building_class','stories','parking_spaces','parking_ratio','price_display','lease_rate_display')};
  echo '<h2>'.esc_html($type).' · core facts</h2>';self::fields($post,$keys);
  if($type==='Office Condo'){$d=Aspire_Property_Dossier::data($post->ID);$w=Aspire_Property_Dossier::workspace($d);foreach($w['metrics'] as $metric)echo '<p class="pw-inline-fact">'.esc_html($metric['value'].' · '.$metric['label']).'</p>';echo '<p class="description">Unit and combination details are managed in Intelligence.</p>';}
  if(in_array((int)$post->ID,array_map('intval',(array)get_option('aspire_atlas_suppressed_price_ids',array())),true))echo '<p class="pw-warning">Public pricing is suppressed for this property. Stored pricing remains private.</p>';
  $all=array_keys(array_merge(...array_values(Aspire_Core_Fields::groups('property'))));$shown=array_merge($keys,array('listing_status','featured_property','address_line_1','address_line_2','city','state','postal_code','latitude','longitude'));
  echo '<details class="pw-disclosure"><summary>Advanced property fields</summary>';self::fields($post,array_values(array_diff($all,$shown)));echo '</details><details class="pw-disclosure"><summary>Publishing options</summary><div data-native-publishing></div></details>';
 }
 public static function suite($index,array $suite) {
  $use=$suite['former_use']??'';if(!$use&&preg_match('/(?:^|\|)\s*(Medical\s*\/\s*Office|Retail|Office|Medical)\s*(?:\||$)/i',$suite['notes']??'',$m))$use=preg_replace('/\s*\/\s*/',' / ',$m[1]);
  $size=is_numeric($suite['square_feet']??null)?number_format((float)$suite['square_feet']).' SF':'';$rate=is_numeric($suite['rate']??null)?'$'.$suite['rate'].'/SF '.($suite['rate_type']??''):'';
  echo '<div class="pw-suite"><details><summary><strong data-suite-name>'.esc_html($suite['suite_name']??'New space').'</strong><span data-suite-info>'.esc_html(implode(' · ',array_filter(array($size,$use,$rate)))).'</span><span data-suite-status>Status: '.esc_html(!empty($suite['availability_status'])?Aspire_Core_Admin::label($suite['availability_status']):'Not confirmed').'</span><span class="pw-edit">Edit</span></summary><div class="aspire-fields">';
  foreach(Aspire_Core_Fields::suites() as $key=>$kind){$id='pw-suite-'.$index.'-'.$key;echo '<div><label for="'.esc_attr($id).'">'.esc_html(Aspire_Core_Admin::label($key)).'</label>';Aspire_Core_Admin::control('aspire[suites]['.$index.']['.$key.']',$kind,$suite[$key]??'',$id);echo '</div>';}
  echo '</div></details><button type="button" class="pw-remove-suite button-link-delete" aria-label="Remove '.esc_attr($suite['suite_name']??'space').'">Remove</button></div>';
 }
 public static function availability($post) {
  echo '<h2>Available spaces</h2><p class="description">Manage listed suites. Unconfirmed availability stays unconfirmed.</p><input type="hidden" name="aspire[suites_present]" value="1"><div id="pw-suites">';$suites=get_post_meta($post->ID,'_aspire_suites',true);foreach(is_array($suites)?$suites:array() as $index=>$suite)self::suite($index,$suite);
  echo '</div><template id="pw-suite-template">';self::suite('__INDEX__',array());echo '</template><button type="button" class="button" id="pw-add-space">+ Add space</button><p class="description">Changes are saved with the property.</p>';
 }
 public static function media_item($id,$kind) {
  echo '<li data-id="'.esc_attr($id).'"'.($kind==='gallery'?' draggable="true"':'').'>';
  if(wp_attachment_is_image($id))echo wp_get_attachment_image($id,$kind==='featured'?'medium_large':'medium',false,array('alt'=>get_post_meta($id,'_wp_attachment_image_alt',true)?:get_the_title($id)));
  else echo '<strong>'.esc_html(basename(get_attached_file($id)?:get_the_title($id))).'</strong><span>PDF</span><a href="'.esc_url(wp_get_attachment_url($id)).'" target="_blank" rel="noopener">View ↗</a>';
  if($kind==='gallery')echo '<div class="pw-photo-controls"><button type="button" data-photo-move="-1" aria-label="Move photo earlier">←</button><button type="button" data-photo-move="1" aria-label="Move photo later">→</button><button type="button" data-photo-remove aria-label="Remove photo">Remove</button></div>';
  echo '</li>';
 }
 public static function media($post) {
  foreach(array('featured'=>'Featured image','gallery'=>'Gallery','brochure'=>'Brochure') as $kind=>$label){
   $ids=$kind==='featured'?array_filter(array(get_post_thumbnail_id($post->ID))):($kind==='gallery'?(array)get_post_meta($post->ID,'_aspire_gallery_attachment_ids',true):array_filter(array(get_post_meta($post->ID,'_aspire_brochure_attachment_id',true))));$ids=array_filter(array_map('intval',$ids));
   $name=match($kind){'featured'=>'_thumbnail_id','gallery'=>'aspire[gallery_attachment_ids]',default=>'aspire[brochure_attachment_id]'};
   echo '<section class="pw-media pw-media-'.esc_attr($kind).'" data-media-kind="'.esc_attr($kind).'"><h2>'.esc_html($label).'</h2><input type="hidden" name="'.esc_attr($name).'" value="'.esc_attr(implode(',',$ids)).'"><ul class="pw-media-grid">';foreach($ids as $id)self::media_item($id,$kind);echo '</ul><button type="button" class="button" data-media-select>'.esc_html(match($kind){'featured'=>'Change featured image','gallery'=>'Add / select photos',default=>'Replace / select PDF'}).'</button>';if($kind!=='gallery')echo ' <button type="button" class="button-link-delete" data-media-clear>Remove</button>';echo '</section>';
  }
  echo '<p class="description">Drag gallery photos to reorder, or use the arrow buttons. Media changes are saved with the property.</p><p class="screen-reader-text" data-media-announcement role="status"></p>';
 }
 public static function intelligence($post) {
  wp_nonce_field('aspire_intelligence_'.$post->ID,'aspire_intelligence_nonce');$data=get_post_meta($post->ID,Aspire_Property_Intelligence::META,true);$data=is_array($data)?$data:array();$type=Aspire_Property_Intelligence::type($post->ID);$rules=Aspire_Property_Lens::rules($type);$groups=array();$unknown=array();
  foreach(Aspire_Property_Intelligence::catalog()[$type] as $field){$status=$data['decision_field_status'][$field]??'unknown';if($status==='unknown')$unknown[]=$field;else $groups[$rules[$field]['group']??'Property facts'][]=$field;}
  foreach($groups as $group=>$fields){echo '<h2>'.esc_html($group).'</h2>';foreach($fields as $field){$review=($data['decision_field_status'][$field]??'unknown')==='conflicting'||!Aspire_Property_Lens::known($data,$field);$value=Aspire_Property_Lens::format($field,$data['facts'][$field]['value']??'');echo '<details class="pw-intelligence'.($review?' pw-conflict':'').'"><summary><span>'.esc_html($rules[$field]['label']??Aspire_Property_Intelligence::label($field)).'</span><strong>'.esc_html($review?'Requires review — conflicting or unverified detail':$value).'</strong><span class="pw-edit">Edit</span></summary>';Aspire_Property_Intelligence::field($field,$data);echo '</details>';}}
  echo '<details class="pw-disclosure"><summary>Additional decision fields ('.count($unknown).')</summary>';foreach($unknown as $field){echo '<details class="pw-intelligence"><summary>'.esc_html(Aspire_Property_Intelligence::label($field)).'<span class="pw-edit">Add detail</span></summary>';Aspire_Property_Intelligence::field($field,$data);echo '</details>';}echo '</details>';
  $d=Aspire_Property_Dossier::data($post->ID);echo '<aside class="pw-confirm"><h2>Details to confirm</h2><ul>';foreach($d['lens']['questions'] as $question)echo '<li>'.esc_html($question['text']).'</li>';echo '</ul></aside><p class="description">One statement per line. For traffic: road name — count VPD. Changing property type updates the available decision fields after saving.</p>';
 }
 public static function location($post) {
  $d=Aspire_Property_Dossier::data($post->ID);$source=get_post_meta($post->ID,Aspire_Property_Intelligence::SOURCE,true);echo '<h2>Location</h2><p>'.esc_html(trim($d['address'].', '.$d['locality'],', ')).'</p>';require __DIR__.'/property-map.php';
  echo '<div class="pw-coordinate-source"><span>Coordinate source <strong>'.esc_html($source['coordinate_source']??'Not recorded').'</strong></span><span>Review <strong>'.esc_html(ucfirst(strtolower($source['coordinate_status']??'Not recorded'))).'</strong></span></div><details class="pw-disclosure"><summary>Adjust coordinates</summary>';self::fields($post,array('latitude','longitude'));echo '<p class="description">Coordinates are saved with the property. Editing this pin does not run a geocoder or change the original source evidence.</p></details>';
 }
 public static function contacts($post) {
  echo '<h2>Assigned Aspire advisors</h2>';$ids=get_post_meta($post->ID,'_aspire_listing_broker_id',false);if(!$ids)echo '<p>No advisors assigned</p>';else foreach($ids as $id)echo '<p>'.esc_html(get_the_title($id)).'</p>';
  echo '<details class="pw-disclosure"><summary>Assign team member</summary>';Aspire_Core_Admin::brokers_box($post);echo '</details><h2>Migration contact candidates</h2><p class="description">Not assigned publicly</p>';$source=get_post_meta($post->ID,Aspire_Property_Intelligence::SOURCE,true);echo '<ul>';foreach($source['contact_candidates']??array() as $contact)echo '<li>'.esc_html(implode(' · ',array_filter(array($contact['name']??'',$contact['email']??'',$contact['phone']??'')))).'</li>';echo '</ul>';
 }
 public static function render($post) {
  if($post->post_type!=='property')return;$d=Aspire_Property_Dossier::data($post->ID);$status=metadata_exists('post',$post->ID,'_aspire_listing_status')?Aspire_Core_Admin::label(get_post_meta($post->ID,'_aspire_listing_status',true)):'Not confirmed';
  $w=Aspire_Property_Dossier::workspace($d);echo '<div class="aspire-property-workspace" data-property-tabs>';wp_nonce_field('aspire_save_'.$post->ID,'aspire_nonce');
  echo '<header class="pw-header"><div><p class="pw-kicker">PROPERTY WORKSPACE</p><h1 data-property-name>'.esc_html($d['title']?:'New property').'</h1><p>'.esc_html(implode(' · ',array_filter(array_merge(array($d['type']->name??''),array_column($d['transactions'],'name'))))).'</p><p>'.esc_html($d['locality']).'</p><span class="pw-status">'.esc_html($status?:'Not confirmed').'</span> <span class="pw-post-status">'.esc_html(get_post_status_object($post->post_status)->label??'Draft').'</span><p class="pw-header-facts">'.esc_html(implode(' · ',array_map(static fn($m)=>$m['value'].' '.strtolower($m['label']),$w['metrics']))).'</p></div><div class="pw-header-actions"><a class="button" href="'.esc_url(get_preview_post_link($post)).'" target="_blank" rel="noopener">Preview property ↗</a><button type="button" class="button button-primary" data-property-save data-published="'.esc_attr(in_array($post->post_status,array('publish','private','future'),true)?'true':'false').'">'.(in_array($post->post_status,array('publish','private','future'),true)?'Update property':(in_array($post->post_status,array('draft','auto-draft'),true)?'Save draft':'Save property')).'</button><span class="description">Changes save across all tabs</span></div></header>';
  $tabs=array('overview'=>'Overview','availability'=>'Availability','media'=>'Media','intelligence'=>'Intelligence','location'=>'Location','contacts'=>'Contacts','source'=>'Source');echo '<nav class="pw-tabs" role="tablist" aria-label="Property workspace">';foreach($tabs as $key=>$label)echo '<a role="tab" id="pw-tab-'.$key.'" href="#pw-'.$key.'" aria-controls="pw-'.$key.'" aria-selected="'.($key==='overview'?'true':'false').'" tabindex="'.($key==='overview'?'0':'-1').'">'.$label.'</a>';echo '</nav>';
  foreach($tabs as $key=>$label){echo '<section class="pw-panel" id="pw-'.$key.'" role="tabpanel" aria-labelledby="pw-tab-'.$key.'" tabindex="0"'.($key==='overview'?'':' hidden').'>';if($key==='source'){echo '<h2>Source & migration</h2>';Aspire_Property_Intelligence::source_box($post);}else self::$key($post);echo '</section>';}
  echo '</div>';
 }
}
Aspire_Property_Workspace::init();
