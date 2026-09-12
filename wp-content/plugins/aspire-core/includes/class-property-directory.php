<?php
/** Directory presentation; existing Property storage and Atlas price policy remain authoritative. */
defined('ABSPATH') || exit;
final class Aspire_Property_Directory {
 public static function register(): void {
  $base=dirname(ASPIRE_CORE_FILE).'/directory/build/';$url=plugins_url('directory/build/',ASPIRE_CORE_FILE);
  $version=file_exists($base.'manifest.json')?json_decode(file_get_contents($base.'manifest.json'),true)['version']:'1';
  wp_register_script('aspire-directory-view',$url.'view.js',array(),$version,array('in_footer'=>true,'strategy'=>'defer'));
  wp_register_style('aspire-directory-map',$url.'map.css',array(),$version);
  wp_register_script('aspire-directory-editor',$url.'editor.js',array('wp-blocks','wp-element','wp-block-editor','wp-components','wp-server-side-render'),$version,true);
  wp_register_style('aspire-directory-editor',$url.'editor.css',array(),$version);
  register_block_type($base,array('render_callback'=>array(self::class,'render')));
 }
 public static function assets(): void {
  if(!is_singular())return;
  $walk=static function(array $blocks) use (&$walk): bool {foreach($blocks as $block){if($block['blockName']==='aspire/property-directory'&&($block['attrs']['showMap']??true))return true;if($walk($block['innerBlocks']??array()))return true;}return false;};
  if($walk(parse_blocks(get_post_field('post_content',get_queried_object_id()))))wp_enqueue_style('aspire-directory-map');
 }
 public static function enrich(array $data): array {
  foreach($data['features'] as &$feature){
   $id=(int)$feature['id'];$detail=array();
   foreach(array('clearHeightFt'=>'clear_height_ft','parkingSpaces'=>'parking_spaces','trafficCount'=>'traffic_count_vpd') as $name=>$key){$v=get_post_meta($id,'_aspire_'.$key,true);$detail[$name]=is_numeric($v)&&is_finite((float)$v)&&(float)$v>0?(float)$v:null;}
   foreach(array('buildingClass'=>'building_class','parkingRatio'=>'parking_ratio') as $name=>$key)$detail[$name]=sanitize_text_field((string)get_post_meta($id,'_aspire_'.$key,true));
   $feature['properties']['directory']=array('featured'=>(bool)get_post_meta($id,'_aspire_featured_property',true),'publishedAt'=>get_post_time('c',true,$id),'details'=>$detail);
  }
  return $data;
 }
 public static function response($response,$server,$request){
  // Opt-in enrichment of the existing endpoint. Its default response is untouched.
  if($request->get_route()==='/aspire/v1/atlas/properties'&&$request->get_param('directory')==='1'&&$response instanceof WP_REST_Response&&$response->get_status()===200){$data=$response->get_data();if(isset($data['features']))$response->set_data(self::enrich($data));}
  return $response;
 }
 public static function is_page(): bool {return is_page()&&get_post_field('post_name',get_queried_object_id())==='properties'&&has_block('aspire/property-directory',get_queried_object_id());}
 public static function robots(array $robots): array {
  if(self::is_page()&&array_intersect(array('type','transaction','size','area','property_type','transaction_type'),array_keys($_GET))){unset($robots['index'],$robots['nofollow']);$robots['noindex']=true;$robots['follow']=true;}
  return $robots;
 }
 public static function canonical($url,$post){return self::is_page()&&$post->ID===get_queried_object_id()?get_permalink($post):$url;}
 public static function render(array $attributes): string {
  $editor=defined('REST_REQUEST')&&REST_REQUEST;
  $show_map=$attributes['showMap']??true;$view=($attributes['defaultView']??'split')==='list'||!$show_map?'list':'split';
  $per_load=in_array((int)($attributes['perLoad']??0),array(0,12,24),true)?(int)($attributes['perLoad']??0):0;
  $uid=wp_unique_id('aspire-directory-');
  $types=get_terms(array('taxonomy'=>'property_type','hide_empty'=>false));$transactions=get_terms(array('taxonomy'=>'transaction_type','hide_empty'=>false));
  if(!$editor){wp_enqueue_script('aspire-directory-view');if($show_map)wp_enqueue_style('aspire-directory-map');}
  ob_start();require dirname(ASPIRE_CORE_FILE).'/directory/template.php';return ob_get_clean();
 }
}
add_action('init',array(Aspire_Property_Directory::class,'register'));
add_filter('rest_post_dispatch',array(Aspire_Property_Directory::class,'response'),10,3);
add_filter('wp_robots',array(Aspire_Property_Directory::class,'robots'));
add_filter('get_canonical_url',array(Aspire_Property_Directory::class,'canonical'),10,2);

add_action('wp_enqueue_scripts',array(Aspire_Property_Directory::class,'assets'));
