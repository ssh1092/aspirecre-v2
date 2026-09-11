<?php
/** Local prototype data import. Run only with: docker compose exec -T wordpress php wp-content/local-import/approved-samples.php */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 2 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Local environment only.' ); }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$data = json_decode( <<<'JSON'
{
  "media": [
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/57793268-5d09-4651-a66a-8a6b5b117559/Aspire%2BLogo%2BHorizontal%2BWhite.png",
      "title": "Aspire Commercial logo",
      "alt": "Aspire Commercial",
      "filename": "aspire-commercial-logo.png"
    },
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/9fd522d5-6ba2-4a05-85f7-ec90a0f8046d/Texas%2B249%2B20333%2BIMG%2B93_1_1.jpg",
      "title": "Chasewood Technology Park",
      "alt": "Chasewood Technology Park commercial property in Houston",
      "filename": "chasewood-technology-park.jpg"
    },
    {
      "url": "https://upload.wikimedia.org/wikipedia/commons/2/2e/Houston_daytime_skyline.jpg",
      "title": "Downtown Houston skyline",
      "alt": "Downtown Houston skyline",
      "filename": "houston-skyline.jpg"
    },
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/e3566d9f-a0f8-4716-9322-f65f0a2c914e/cover.jpg",
      "title": "Atascocita Retail Center",
      "alt": "Atascocita Retail Center in Humble Texas",
      "filename": "atascocita-retail-center.jpg"
    },
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/1699323166123-BTJXOMNXXY653TWC9FAL/Clay%2BRd.%2B2.jpg",
      "title": "16840 Clay Road",
      "alt": "16840 Clay Road industrial flex property in Houston",
      "filename": "16840-clay-road.jpg"
    },
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/706db0e6-0f2e-4214-9ec6-ce533ba2fbad/DJI_20251006152303_0288_D%2Bcopy.jpg",
      "title": "14602 Presidio Square Boulevard",
      "alt": "14602 Presidio Square Boulevard office building in Houston",
      "filename": "presidio-square.jpg"
    },
    {
      "url": "https://images.squarespace-cdn.com/content/v1/6526f629b7c3436641af8bc2/1788383973875-IQ9KSJ3XIPPK2YL40NW6/1.png",
      "title": "21617 FM 1093",
      "alt": "21617 FM 1093 commercial land in Richmond Texas",
      "filename": "21617-fm-1093.png"
    }
  ],
  "properties": [
    {
      "title": "Atascocita Retail Center - 7506 E FM-1960 Humble, TX 77346",
      "slug": "7506-e-fm-1960-humble-texas-77346",
      "property_type": "Retail",
      "transaction_type": "For Lease",
      "meta": {
        "listing_status": "available",
        "featured_property": true,
        "address_line_1": "7506 E FM-1960",
        "city": "Humble",
        "state": "TX",
        "postal_code": "77346",
        "available_sf": "4361",
        "lot_acres": "5.49",
        "traffic_count_vpd": "23967",
        "lease_rate_min": "18",
        "lease_rate_display": "$18.00/SF Base + $5.84/SF NNN",
        "property_highlights": "5.49 acres\n23,967 VPD\nThree-mile population of 73,769 with average household income of $126,871\nNational tenant mix including AutoZone and Bike Barn\nLocated within Atascocita's major retail corridor\nSignalized FM-1960 location\nSurrounded by major national retailers",
        "suites": [
          {
            "suite_name": "Suite F",
            "square_feet": 4361,
            "rate": 18,
            "rate_type": "Base + NNN",
            "former_use": "Second Gen Retail",
            "availability_status": "available",
            "notes": "$5.84/SF NNN"
          }
        ]
      },
      "media": 3
    },
    {
      "title": "16840 Clay Road Houston, TX 77084",
      "slug": "16840-clay-road-houston-tx-77084",
      "property_type": "Industrial / Flex",
      "transaction_type": "For Lease",
      "meta": {
        "listing_status": "available",
        "featured_property": true,
        "address_line_1": "16840 Clay Road",
        "city": "Houston",
        "state": "TX",
        "postal_code": "77084",
        "available_sf": "11273",
        "lot_acres": "1.82",
        "building_sf": "37309",
        "year_built": "1983",
        "lease_rate_display": "$9.75–$11.50/SF/YR",
        "property_highlights": "Masonry construction\nGrade-level loading doors\nRear-load configuration\nProfessionally leased and managed\nAmple parking\nConvenient access to Highway 6"
      },
      "media": 4
    },
    {
      "title": "14602 Presidio Square Boulevard, Houston, TX 77083",
      "slug": "14602-presidio-square-boulevard-houston-tx-77083",
      "property_type": "Office",
      "transaction_type": "For Sale",
      "meta": {
        "listing_status": "available",
        "featured_property": true,
        "address_line_1": "14602 Presidio Square Boulevard",
        "city": "Houston",
        "state": "TX",
        "postal_code": "77083",
        "lot_acres": "2.48",
        "building_sf": "42716",
        "year_built": "2014",
        "building_class": "B",
        "stories": "3",
        "parking_spaces": "160",
        "parking_ratio": "3.95 spaces per 1,000 SF",
        "property_highlights": "Located along two high-traffic corridors with connectivity to the Houston metro area\n42,716 SF office building\nClass B\nBuilt in 2014\nThree stories\n2.48-acre lot\n160 parking spaces"
      },
      "media": 5
    },
    {
      "title": "21617 FM 1093 Richmond, TX 77407",
      "slug": "21617-fm-1093-richmond-tx-77407",
      "property_type": "Land",
      "transaction_type": "For Sale",
      "meta": {
        "listing_status": "available",
        "featured_property": true,
        "address_line_1": "21617 FM 1093",
        "city": "Richmond",
        "state": "TX",
        "postal_code": "77407",
        "lot_acres": "2.21",
        "sale_price": "3410000",
        "price_display": "$3,410,000",
        "property_highlights": "2.21-acre commercial tract along the FM 1093 / Westpark Tollway corridor\nPositioned between Grand Parkway and the Richmond, Katy, Fulshear and West Houston growth areas\nWestpark Tollway frontage and regional visibility\nThree-mile population of 93,483\nThree-mile average household income of $147,801"
      },
      "media": 6
    }
  ]
}
JSON
, true, 512, JSON_THROW_ON_ERROR );
function import_check( $value ) {
 if ( is_wp_error( $value ) ) { throw new RuntimeException( $value->get_error_message() ); }
 return $value;
}
$media_ids = array();
foreach ( $data['media'] as $asset ) {
 $found = get_posts( array( 'post_type'=>'attachment', 'post_status'=>'inherit', 'meta_key'=>'_source_url', 'meta_value'=>$asset['url'], 'numberposts'=>2, 'fields'=>'ids' ) );
 if ( count( $found ) > 1 ) { throw new RuntimeException( 'Duplicate source attachments; resolve before importing.' ); }
 $id = $found[0] ?? 0;
 if ( ! $id ) {
  $tmp = import_check( download_url( $asset['url'], 90 ) );
  try { $id = import_check( media_handle_sideload( array( 'name'=>$asset['filename'], 'tmp_name'=>$tmp ), 0, $asset['title'] ) ); }
  finally { if ( file_exists( $tmp ) ) { unlink( $tmp ); } }
  update_post_meta( $id, '_source_url', $asset['url'] );
 }
 import_check( wp_update_post( array( 'ID'=>$id, 'post_title'=>$asset['title'] ), true ) );
 update_post_meta( $id, '_wp_attachment_image_alt', $asset['alt'] );
 $media_ids[] = $id;
 echo "Media $id: {$asset['title']}\n";
}
$definitions = array_merge( ...array_values( Aspire_Core_Fields::groups( 'property' ) ) );
foreach ( $data['properties'] as $record ) {
 $existing = get_page_by_path( $record['slug'], OBJECT, 'property' );
 $id = import_check( wp_insert_post( wp_slash( array( 'ID'=>$existing ? $existing->ID : 0, 'post_type'=>'property', 'post_status'=>'publish', 'post_title'=>$record['title'], 'post_name'=>$record['slug'] ) ), true ) );
 foreach ( $record['meta'] as $name=>$value ) {
  if ( 'suites' !== $name && ! isset( $definitions[$name] ) ) { throw new RuntimeException( "Unknown field $name" ); }
  update_post_meta( $id, '_aspire_'.$name, wp_slash( $value ) );
 }
 foreach ( array( 'property_type', 'transaction_type' ) as $tax ) {
  $term = get_term_by( 'name', $record[$tax], $tax );
  if ( ! $term ) { throw new RuntimeException( "Missing existing taxonomy term {$record[$tax]}" ); }
  import_check( wp_set_object_terms( $id, array( $term->term_id ), $tax ) );
 }
 set_post_thumbnail( $id, $media_ids[$record['media']] );
 echo "Property $id: {$record['title']}\n";
}
update_option( 'blogname', 'Aspire Commercial' );
set_theme_mod( 'aspirecre_contact_phone', '713-933-2001' );
set_theme_mod( 'aspirecre_contact_address', "10777 Westheimer Road\nSuite 800\nHouston, TX 77042" );
set_theme_mod( 'custom_logo', $media_ids[0] );
// The supplied white logo needs a dark-on-light treatment in the existing header/footer.
// Store this through WordPress Additional CSS; the source asset is unaltered.
$css = wp_get_custom_css();
$logo_css = '.custom-logo { filter: brightness(0); }';
if ( ! str_contains( $css, $logo_css ) ) { import_check( wp_update_custom_css_post( $css . "\n/* Approved white logo on the existing light background. */\n" . $logo_css ) ); }
$home = get_post( (int) get_option( 'page_on_front' ) );
if ( ! $home || 'Home' !== $home->post_title || 'page' !== $home->post_type ) { throw new RuntimeException( 'Expected existing Home page.' ); }
$content = $home->post_content;
$index = 0;
$content = preg_replace_callback( '/<!-- wp:image(?:\s+\{.*?\})?\s*-->.*?<!-- \/wp:image -->/s', function ( $match ) use ( &$index, $media_ids, $data ) {
 ++$index;
 if ( $index > 2 ) { throw new RuntimeException( 'Unexpected extra Home image; refusing replacement.' ); }
 $id = $media_ids[$index];
 $attrs = array( 'id'=>$id, 'sizeSlug'=>'full', 'linkDestination'=>'none' );
 return '<!-- wp:image '.wp_json_encode( $attrs ).' -->' . "\n" . '<figure class="wp-block-image size-full"><img src="'.esc_url( wp_get_attachment_url( $id ) ).'" alt="'.esc_attr( $data['media'][$index]['alt'] ).'" class="wp-image-'.$id.'"/></figure>' . "\n<!-- /wp:image -->";
}, $content );
if ( 2 !== $index ) { throw new RuntimeException( 'Expected exactly two existing Home Image blocks.' ); }
if ( $content !== $home->post_content ) {
 add_post_meta( $home->ID, '_aspire_before_approved_sample_media', wp_slash( $home->post_content ), true );
 wp_save_post_revision( $home->ID );
 import_check( wp_update_post( array( 'ID'=>$home->ID, 'post_content'=>wp_slash( $content ) ), true ) );
}
echo "Home {$home->ID}: two local native Image blocks configured.\n";
// Verify stored content after each run, including preservation of all non-image Home blocks.
$checks = 0;
$check = static function ( $ok, $label ) use ( &$checks ) {
 if ( ! $ok ) { throw new RuntimeException( 'Verification failed: '.$label ); }
 ++$checks;
};
foreach ( $data['properties'] as $record ) {
 $post = get_page_by_path( $record['slug'], OBJECT, 'property' );
 $check( $post && 'publish' === $post->post_status, $record['slug'].' published' );
 foreach ( $record['meta'] as $key=>$expected ) {
  $stored = get_post_meta( $post->ID, '_aspire_'.$key, true );
  $check( $stored == $expected, $record['slug'].' '.$key );
 }
 foreach ( array( 'property_type','transaction_type' ) as $tax ) {
  $check( has_term( $record[$tax], $tax, $post ), $record['slug'].' '.$tax );
 }
 $check( get_post_thumbnail_id( $post ) === $media_ids[$record['media']], 'Local featured image' );
}
foreach ( $media_ids as $i=>$id ) {
 $check( file_exists( get_attached_file( $id ) ), 'Local attachment file' );
 $check( get_post_meta( $id, '_wp_attachment_image_alt', true ) === $data['media'][$i]['alt'], 'Attachment alt' );
 $same_source = get_posts( array( 'post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_source_url','meta_value'=>$data['media'][$i]['url'],'numberposts'=>-1 ) );
 $check( 1 === count( $same_source ), 'No duplicate media' );
}
$check( 4 === count( get_posts( array( 'post_type'=>'property','post_status'=>'any','numberposts'=>-1 ) ) ), 'Exactly four properties' );
$check( ! get_posts( array( 'post_type'=>'team_member','post_status'=>'any','numberposts'=>1 ) ), 'Team remains empty' );
$check( ! metadata_exists( 'post', get_page_by_path( '16840-clay-road-houston-tx-77084', OBJECT, 'property' )->ID, '_aspire_clear_height_ft' ), 'Clear height left unset' );
$check( 'page' === get_option( 'show_on_front' ) && 74 === (int) get_option( 'page_on_front' ), 'Home remains static front page' );
$strip = static fn( $s ) => preg_replace( '/<!-- wp:image(?:\s+\{.*?\})?\s*-->.*?<!-- \/wp:image -->/s', '', $s );
$check( $strip( get_post_meta( $home->ID, '_aspire_before_approved_sample_media', true ) ) === $strip( get_post( $home->ID )->post_content ), 'All non-image Home content unchanged' );
$check( 4 === count( aspire_core_block_featured_properties() ), 'Existing block queries four properties' );
echo "Verified $checks stored content/media checks.\n";
