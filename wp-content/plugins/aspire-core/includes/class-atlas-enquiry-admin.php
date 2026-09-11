<?php
/** Dedicated admin UI over existing private enquiry records. */
defined('ABSPATH') || exit;
final class Aspire_Atlas_Enquiry_Admin {
 const STATUSES=array('new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','closed'=>'Closed');
 public static function url(int $id=0): string {return add_query_arg(array_filter(array('page'=>'aspire-atlas-enquiries','enquiry'=>$id)),admin_url('admin.php'));}
 public static function menu(): void {add_menu_page('Atlas Enquiries','Atlas Enquiries','manage_options','aspire-atlas-enquiries',array(self::class,'page'),'dashicons-clipboard',26);}
 public static function legacy(): void {
  global $pagenow;
  $id=isset($_GET['post'])&&is_scalar($_GET['post'])?absint($_GET['post']):0;
  if(($pagenow==='post.php'&&$id&&get_post_type($id)==='atlas_inquiry')||(in_array($pagenow,array('edit.php','post-new.php'),true)&&($_GET['post_type']??'')==='atlas_inquiry')){
   if(!current_user_can('manage_options'))wp_die('You cannot access Atlas enquiries.',403);
   wp_safe_redirect(self::url($id));exit;
  }
 }
 public static function status(int $id): string {$value=get_post_meta($id,'_atlas_internal_status',true);return isset(self::STATUSES[$value])?$value:'new';}
 public static function save(int $id,$status,$notes,$nonce) {
  if(!current_user_can('manage_options'))return new WP_Error('forbidden','You cannot update Atlas enquiries.',array('status'=>403));
  if(!$id||get_post_type($id)!=='atlas_inquiry'||get_post_status($id)==='trash')return new WP_Error('not_found','This enquiry is unavailable.',array('status'=>404));
  if(!is_string($nonce)||!wp_verify_nonce($nonce,'atlas_enquiry_update_'.$id))return new WP_Error('nonce','The security check failed. Reload the enquiry and try again.',array('status'=>403));
  if(!is_string($status)||!isset(self::STATUSES[$status])||!is_string($notes)||mb_strlen($notes)>10000)return new WP_Error('invalid','Choose a valid status and keep notes under 10,000 characters.',array('status'=>400));
  update_post_meta($id,'_atlas_internal_status',$status);
  update_post_meta($id,'_atlas_internal_notes',wp_slash(sanitize_textarea_field($notes)));
  return true;
 }
 public static function handle_save(): void {
  if($_SERVER['REQUEST_METHOD']!=='POST')wp_die('Use the enquiry form to save changes.',405);
  $id=isset($_POST['enquiry'])&&is_scalar($_POST['enquiry'])?absint($_POST['enquiry']):0;
  $result=self::save($id,wp_unslash($_POST['status']??null),wp_unslash($_POST['notes']??null),wp_unslash($_POST['_wpnonce']??null));
  if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),'Enquiry update',array('response'=>$result->get_error_data()['status']));
  wp_safe_redirect(add_query_arg('updated','1',self::url($id)));exit;
 }
 private static function rows(int $id): array {
  $b=get_post_meta($id,'_atlas_brief',true);$s=Aspire_Atlas_Inquiries::schema();if(!is_array($b))return array();
  $labels=static fn($values,$table)=>implode(', ',array_map(static fn($v)=>$table[$v]??$v,array_filter((array)$values,'is_string')));
  $rows=array('Goal'=>$s['goals'][$b['goal']??'']??'Not specified','Property Type'=>$labels($b['propertyTypes']??array(),$s['propertyTypes']),'Area'=>implode(' · ',array_filter(array($b['location']['text']??'',$s['areas'][$b['location']['areaPreset']??'']['label']??'All Houston / flexible'))),'Size'=>$s['sizes'][$b['size']??'']['label']??'Not specified');
  if(in_array($b['goal']??'',array('buy_property','invest'),true))$rows['Budget']=$s['budgets'][$b['budget']??'']['label']??'Not specified';
  if(($b['goal']??'')!=='manage_asset')$rows['Transaction']=array(''=>'All Transactions','for-lease'=>'For Lease','for-sale'=>'For Sale','for-sale-or-lease'=>'For Sale or Lease')[$b['transaction']??'']??'Not specified';
  $rows['Timing']=$s['timing'][$b['timing']??'']??'Not specified';
  if(!empty($b['priorities']))$rows['Priorities']=$labels($b['priorities'],$s['priorities']);
  if(!empty($b['ownerIntent']))$rows['Owner intent']=$s['ownerIntents'][$b['ownerIntent']]??'Not specified';
  if(!empty($b['managementNeeds']))$rows['Management needs']=$labels($b['managementNeeds'],$s['managementNeeds']);
  return $rows;
 }
 private static function dl(array $rows): void {echo '<dl class="atlas-admin-data">';foreach($rows as $label=>$value)echo '<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($value!==''?$value:'—').'</dd></div>';echo '</dl>';}
 public static function received(WP_Post $post): string {
  $stored=get_post_meta($post->ID,'_atlas_received_at',true);
  $timestamp=is_string($stored)&&$stored!==''?strtotime($stored):false;
  if($timestamp===false)$timestamp=get_post_timestamp($post,'date');
  return $timestamp!==false?wp_date('M j, Y · g:i A',$timestamp,wp_timezone()):'—';
 }
 public static function details(WP_Post $post): void {
  $id=$post->ID;$c=(array)get_post_meta($id,'_atlas_contact',true);$received=self::received($post);
  echo '<div class="atlas-admin-grid"><div><section class="atlas-admin-card"><h2>Contact</h2>';
  self::dl(array('Name'=>$c['name']??'','Email'=>$c['email']??'','Phone'=>$c['phone']??'','Company'=>$c['company']??'','Contact requested'=>!empty($c['consent'])?'Yes':'No','Received'=>$received));echo '</section><section class="atlas-admin-card"><h2>Real estate brief</h2>';
  $rows=self::rows($id);if($rows)self::dl($rows);else echo '<p style="white-space:pre-line">'.esc_html(get_post_meta($id,'_atlas_summary',true)?:'Brief details unavailable.').'</p>';
  echo '</section><section class="atlas-admin-card"><h2>Matching Aspire properties</h2><ul class="atlas-admin-properties">';
  $ids=(array)get_post_meta($id,'_atlas_matched_property_ids',true);
  foreach($ids as $property_id){$property=get_post((int)$property_id);if(!$property||$property->post_type!=='property'){echo '<li>Previously matched property no longer available.</li>';continue;}echo '<li><a href="'.esc_url(get_edit_post_link($property->ID)).'">'.esc_html($property->post_title).'</a></li>';}
  if(!$ids)echo '<li>No matching properties were recorded for this requirement.</li>';echo '</ul></section></div>';
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="atlas-admin-card atlas-admin-followup"><h2>Follow-up</h2><input type="hidden" name="action" value="atlas_enquiry_update"><input type="hidden" name="enquiry" value="'.esc_attr($id).'">';wp_nonce_field('atlas_enquiry_update_'.$id);
  echo '<label for="atlas-status">Status</label><select id="atlas-status" name="status">';foreach(self::STATUSES as $key=>$label)echo '<option value="'.esc_attr($key).'" '.selected(self::status($id),$key,false).'>'.esc_html($label).'</option>';echo '</select><label for="atlas-notes">Internal notes</label><p id="atlas-notes-help" class="description">Private notes for Aspire staff.</p><textarea id="atlas-notes" name="notes" rows="10" maxlength="10000" aria-describedby="atlas-notes-help">'.esc_textarea(get_post_meta($id,'_atlas_internal_notes',true)).'</textarea>';submit_button('Save status and notes');echo '</form></div>';
 }
 public static function page(): void {
  if(!current_user_can('manage_options'))wp_die('You cannot access Atlas enquiries.',403);
  echo '<div class="wrap atlas-enquiries"><h1>Atlas Enquiries</h1>';
  $id=isset($_GET['enquiry'])&&is_scalar($_GET['enquiry'])?absint($_GET['enquiry']):0;
  if($id){$post=get_post($id);if(!$post||$post->post_type!=='atlas_inquiry'||$post->post_status==='trash'){echo '<div class="notice notice-error"><p>This enquiry is unavailable.</p></div></div>';return;}
   echo '<p><a href="'.esc_url(self::url()).'">← All enquiries</a></p>';if(($_GET['updated']??'')==='1')echo '<div class="notice notice-success is-dismissible"><p>Enquiry updated.</p></div>';self::details($post);
  }else self::listing();echo '</div>';
 }
 public static function listing(): void {
  $search=isset($_GET['s'])&&is_string($_GET['s'])?sanitize_text_field(wp_unslash($_GET['s'])):'';
  $status=isset($_GET['status'])&&is_string($_GET['status'])&&isset(self::STATUSES[$_GET['status']])?$_GET['status']:'';
  $page=isset($_GET['paged'])&&is_scalar($_GET['paged'])?max(1,absint($_GET['paged'])):1;
  $meta=array('relation'=>'AND');
  if($search!=='')$meta[]=array('relation'=>'OR',array('key'=>'_atlas_contact','value'=>$search,'compare'=>'LIKE'),array('key'=>'_atlas_summary','value'=>$search,'compare'=>'LIKE'));
  if($status==='new')$meta[]=array('relation'=>'OR',array('key'=>'_atlas_internal_status','value'=>'new'),array('key'=>'_atlas_internal_status','compare'=>'NOT EXISTS'));
  elseif($status)$meta[]=array('key'=>'_atlas_internal_status','value'=>$status);
  $query=new WP_Query(array('post_type'=>'atlas_inquiry','post_status'=>'private','posts_per_page'=>25,'paged'=>$page,'orderby'=>'date ID','order'=>'DESC','meta_query'=>count($meta)>1?$meta:array()));
  echo '<p>Review requirements shared through Aspire Atlas and keep track of your follow-up.</p><form method="get" class="atlas-admin-filters"><input type="hidden" name="page" value="aspire-atlas-enquiries"><div><label for="atlas-search">Search contact or requirement</label><input id="atlas-search" name="s" type="search" value="'.esc_attr($search).'" placeholder="Name, email, area or requirement"></div><div><label for="atlas-filter-status">Status</label><select id="atlas-filter-status" name="status"><option value="">All statuses</option>';
  foreach(self::STATUSES as $key=>$label)echo '<option value="'.esc_attr($key).'" '.selected($status,$key,false).'>'.esc_html($label).'</option>';echo '</select></div>';submit_button('Filter','secondary','',false);echo '<a href="'.esc_url(self::url()).'">Clear filters</a></form>';
  echo '<p>'.esc_html($query->found_posts).($query->found_posts===1?' enquiry':' enquiries').'</p><div class="atlas-admin-table"><table class="widefat striped"><thead><tr>';foreach(array('Contact','Requirement','Area','Timing','Contact Requested','Received','Status') as $label)echo '<th scope="col">'.esc_html($label).'</th>';echo '</tr></thead><tbody>';
  foreach($query->posts as $post){$id=$post->ID;$c=(array)get_post_meta($id,'_atlas_contact',true);$r=self::rows($id);echo '<tr><th scope="row"><a href="'.esc_url(self::url($id)).'"><strong>'.esc_html($c['name']??'View enquiry').'</strong></a><small>'.esc_html($c['email']??'').'</small></th><td>'.esc_html(implode(' · ',array_filter(array($r['Goal']??'',$r['Property Type']??'',$r['Size']??'')))).'</td><td>'.esc_html($r['Area']??'').'</td><td>'.esc_html($r['Timing']??'').'</td><td><span class="atlas-admin-request '.(!empty($c['consent'])?'is-requested':'is-not-requested').'">'.(!empty($c['consent'])?'Yes':'No').'</span></td><td class="atlas-admin-received">'.esc_html(self::received($post)).'</td><td><span class="atlas-admin-status is-'.esc_attr(self::status($id)).'">'.esc_html(self::STATUSES[self::status($id)]).'</span></td></tr>';}
  if(!$query->posts)echo '<tr><td colspan="7">No enquiries match this view.</td></tr>';echo '</tbody></table></div>';
  if($query->max_num_pages>1)echo '<div class="tablenav"><div class="tablenav-pages">'.wp_kses_post(paginate_links(array('base'=>str_replace('999999','%#%',add_query_arg(array('page'=>'aspire-atlas-enquiries','s'=>$search,'status'=>$status,'paged'=>999999),admin_url('admin.php'))),'format'=>'','current'=>$page,'total'=>$query->max_num_pages))).'</div></div>';
 }
 public static function assets($hook): void {if($hook==='toplevel_page_aspire-atlas-enquiries')wp_enqueue_style('aspire-atlas-admin',plugins_url('atlas/admin.css',ASPIRE_CORE_FILE),array(),filemtime(dirname(ASPIRE_CORE_FILE).'/atlas/admin.css'));}
}
add_action('admin_menu',array(Aspire_Atlas_Enquiry_Admin::class,'menu'));
add_action('admin_init',array(Aspire_Atlas_Enquiry_Admin::class,'legacy'));
add_action('admin_post_atlas_enquiry_update',array(Aspire_Atlas_Enquiry_Admin::class,'handle_save'));
add_action('admin_enqueue_scripts',array(Aspire_Atlas_Enquiry_Admin::class,'assets'));
add_filter('get_edit_post_link',static function($url,$id){return get_post_type($id)==='atlas_inquiry'?Aspire_Atlas_Enquiry_Admin::url($id):$url;},10,2);
