<?php
/** Atlas presentation API and native block; existing Property storage is authoritative. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Atlas {
 public static function register(): void {
  $base = dirname( ASPIRE_CORE_FILE ) . '/atlas/build/';
  $version = file_exists( $base.'manifest.json' ) ? json_decode( file_get_contents( $base.'manifest.json' ), true )['version'] : '1';
  $url = plugins_url( 'atlas/build/', ASPIRE_CORE_FILE );
  wp_register_script( 'aspire-atlas-view', $url.'view.js', array(), $version, array( 'in_footer'=>true, 'strategy'=>'defer' ) );
  wp_register_style( 'aspire-atlas-view', $url.'view.css', array(), $version );
  wp_register_script( 'aspire-atlas-editor', $url.'editor.js', array( 'wp-blocks','wp-element','wp-block-editor','wp-components','wp-server-side-render' ), $version, true );
  wp_register_style( 'aspire-atlas-editor', $url.'editor.css', array(), $version );
  register_block_type( $base, array( 'render_callback'=>array( self::class,'render' ) ) );
 }
 public static function routes(): void {
  register_rest_route( 'aspire/v1', '/atlas/properties', array( 'methods'=>'GET','permission_callback'=>'__return_true','callback'=>static fn() => rest_ensure_response( self::collection() ) ) );
 }
 private static function number( int $id, string $key ): ?float {
  $v = get_post_meta( $id, '_aspire_'.$key, true );
  return is_numeric( $v ) && is_finite( (float) $v ) && (float) $v >= 0 ? (float) $v : null;
 }
 private static function term( int $id, string $taxonomy ): ?array {
  $terms = wp_get_post_terms( $id, $taxonomy, array( 'orderby'=>'term_id','order'=>'ASC' ) );
  return ! is_wp_error( $terms ) && $terms ? array( 'slug'=>sanitize_title( $terms[0]->slug ),'label'=>sanitize_text_field( $terms[0]->name ) ) : null;
 }
 private static function display_title(string $title, string $city, string $state, string $zip, string $address=''): string {
  if($city==='' || $state==='')return $title;
  $suffix='/(?:,\s*|\s+)'.preg_quote($city,'/').'(?:\s*,\s*|\s+)'.preg_quote($state,'/').($zip!==''?'(?:\s+'.preg_quote($zip,'/').')?':'').'\s*$/iu';
  $display=trim((string)preg_replace($suffix,'',$title)," ,\t\n\r\0\x0B");
  // Same exact stored-address suffix treatment as the public Property presenter.
  if($address!=='')$display=(string)preg_replace('/\s+-\s+'.preg_quote($address,'/').'\s*$/iu','',$display);
  return $display!==''?$display:$title;
 }
 public static function collection(): array {
  $features = array();
  $ids = get_posts( array( 'post_type'=>'property','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','meta_query'=>array( array( 'key'=>'_aspire_latitude','compare'=>'EXISTS' ), array( 'key'=>'_aspire_longitude','compare'=>'EXISTS' ), array( 'relation'=>'OR', array( 'key'=>'_aspire_listing_status','value'=>'available' ), array( 'key'=>'_aspire_listing_status','compare'=>'NOT EXISTS' ) ) ) ) );
  $suppressed = array_map( 'intval', (array) get_option( 'aspire_atlas_suppressed_price_ids', array() ) );
  $provisional = array_map( 'intval', (array) get_option( 'aspire_atlas_provisional_coordinate_ids', array() ) );
  foreach ( $ids as $id ) {
   $lat = get_post_meta( $id, '_aspire_latitude', true ); $lon = get_post_meta( $id, '_aspire_longitude', true );
   if ( ! is_numeric( $lat ) || ! is_numeric( $lon ) || ! is_finite( (float) $lat ) || ! is_finite( (float) $lon ) || abs( (float) $lat ) > 90 || abs( (float) $lon ) > 180 ) { continue; }
   $text = static fn( $key ) => sanitize_text_field( (string) get_post_meta( $id, '_aspire_'.$key, true ) );
   $type = self::term( $id,'property_type' ); $transaction = self::term( $id,'transaction_type' );
   $image_id = get_post_thumbnail_id( $id ); $src = wp_get_attachment_image_src( $image_id, 'large' );
   $local = $src && wp_parse_url( $src[0], PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
   $preview = $local ? wp_get_attachment_image_src( $image_id, 'medium' ) : false;
   $preview_srcset = $preview ? implode(', ',array_filter(explode(', ',wp_get_attachment_image_srcset($image_id,'medium_large') ?: ''),static fn($candidate)=>preg_match('/ (\d+)w$/',$candidate,$match) && (int)$match[1] <= 800)) : '';
   $hide_price = in_array( $id, $suppressed, true );
   $properties = array(
    'id'=>$id,'slug'=>get_post_field( 'post_name',$id ),'title'=>sanitize_text_field( get_post_field( 'post_title',$id ) ),'permalink'=>esc_url_raw( get_permalink( $id ) ),
    'displayTitle'=>self::display_title(sanitize_text_field(get_post_field('post_title',$id)),$text('city'),$text('state'),$text('postal_code'),$text('address_line_1')),
    'propertyType'=>$type,'transactionType'=>$transaction,'listingStatus'=>'available',
    'coordinateStatus'=>in_array( $id,$provisional,true ) ? 'provisional' : 'prototype',
    'location'=>array( 'address'=>trim( $text('address_line_1').' '.$text('address_line_2') ),'city'=>$text('city'),'state'=>$text('state'),'postalCode'=>$text('postal_code') ),
    'metrics'=>array( 'availableSf'=>self::number($id,'available_sf'),'buildingSf'=>self::number($id,'building_sf'),'lotAcres'=>self::number($id,'lot_acres') ),
    'pricing'=>array( 'leaseRateDisplay'=>$hide_price ? null : ( $text('lease_rate_display') ?: null ),'priceDisplay'=>$hide_price ? null : ( $text('price_display') ?: null ),'salePrice'=>! $hide_price && has_term( array('for-sale','for-sale-or-lease'),'transaction_type',$id ) ? self::number($id,'sale_price') : null ),
    'propertyHighlights'=>array_values(array_filter(array_map('sanitize_text_field',preg_split('/\r\n|\r|\n/',(string)get_post_meta($id,'_aspire_property_highlights',true))),static fn($line)=>$line!=='')),
    'image'=>$local ? array('url'=>esc_url_raw($src[0]),'alt'=>sanitize_text_field(get_post_meta($image_id,'_wp_attachment_image_alt',true)),'width'=>(int)$src[1],'height'=>(int)$src[2], 'preview'=>$preview ? array('url'=>esc_url_raw($preview[0]),'srcset'=>$preview_srcset, 'width'=>(int)$preview[1],'height'=>(int)$preview[2]) : null) : null,
   );
   $features[] = array('type'=>'Feature','id'=>$id,'geometry'=>array('type'=>'Point','coordinates'=>array((float)$lon,(float)$lat)),'properties'=>$properties);
  }
  return array( 'type'=>'FeatureCollection','features'=>$features );
 }
 public static function render( array $attributes ): string {
  $editor = defined('REST_REQUEST') && REST_REQUEST;
  // Preserve intentional headline line breaks while keeping all output plain text.
  $headline = sanitize_textarea_field( $attributes['headline'] ?? "Explore Houston.\nFind your next move." );
  $support = sanitize_text_field( $attributes['supportingText'] ?? 'Commercial real estate intelligence. Real opportunities. A stronger view of Houston.' );
  // Opt-in company presentation leaves existing Atlas blocks and shared maps intact.
  $corporate = ! empty($attributes['corporateHero']);
  $anchor = sanitize_title($attributes['anchor'] ?? ($corporate?'aspire-atlas':''));
  $company = sanitize_text_field($attributes['companyName'] ?? 'ASPIRE COMMERCIAL');
  $product = sanitize_text_field($attributes['productName'] ?? 'ASPIRE ATLAS');
  $product_line = sanitize_text_field($attributes['productLine'] ?? 'Explore Houston. Find your next move.');
  $advisor_prompt = sanitize_text_field($attributes['advisorPrompt'] ?? 'Prefer to talk it through?');
  $advisor_label = sanitize_text_field($attributes['advisorLabel'] ?? 'Talk to an Aspire advisor');
  $continue_label = sanitize_text_field($attributes['continueLabel'] ?? 'Discover Aspire Commercial');
  $continue_target = sanitize_title(ltrim((string)($attributes['continueTarget'] ?? 'current-opportunities'),'#')) ?: 'current-opportunities';
  $destination = static fn($key) => function_exists('aspirecre_home_destination') ? aspirecre_home_destination($key) : ($key==='contact'?'tel:+17139332001':home_url('/'.($key==='properties'?'properties/':'#'.($key==='insights'?'insights':($key==='services'?'expertise':'human-expertise')))));
  $natural = $attributes['showNaturalLanguage'] ?? true;
  $brief = $attributes['enableBrief'] ?? false;
  if ( ! $editor ) { wp_enqueue_script('aspire-atlas-view'); wp_enqueue_style('aspire-atlas-view'); }
  $uid = wp_unique_id('aspire-atlas-');
  $tiles = content_url('/uploads/aspire-atlas/houston.pmtiles');
  $glyphs = content_url('/uploads/aspire-atlas/fonts/{fontstack}/{range}.pbf');
  $mapped = self::collection()['features'];
  $count = count($mapped);
  ob_start(); require dirname(ASPIRE_CORE_FILE).'/atlas/template.php'; return ob_get_clean();
 }
}
add_action('init',array(Aspire_Atlas::class,'register'));
add_action('rest_api_init',array(Aspire_Atlas::class,'routes'));

add_action('wp_enqueue_scripts',static function(){
 if(is_singular() && has_block('aspire/atlas',get_queried_object_id())){wp_enqueue_script('aspire-atlas-view');wp_enqueue_style('aspire-atlas-view');}
});
