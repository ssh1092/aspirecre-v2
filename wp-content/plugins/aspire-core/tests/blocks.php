<?php
/** Read-only block registration, REST preview and migration checks. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
define( 'REST_REQUEST', true );
$checks = 0;
function aspire_block_assert( $ok, $label ) {
 global $checks;
 if ( ! $ok ) { throw new RuntimeException( $label ); }
 ++$checks; echo "PASS: $label\n";
}
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $admin[0]->ID );
foreach ( array( 'property-finder' => 'Find a property', 'featured-properties' => 'No featured properties are available yet.', 'team-grid' => 'No team members have been published yet.' ) as $slug => $expected ) {
 $type = WP_Block_Type_Registry::get_instance()->get_registered( 'aspire-core/' . $slug );
 aspire_block_assert( $type && 3 === $type->api_version && 'aspirecre' === $type->category && $type->is_dynamic(), "$slug modern dynamic registration" );
 $request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/aspire-core/' . $slug );
 $request->set_param( 'context', 'edit' );
 $request->set_param( 'attributes', 'property-finder' === $slug ? array() : array( 'count' => 4 ) );
 $response = rest_do_request( $request );
 aspire_block_assert( 200 === $response->get_status(), "$slug authenticated REST preview succeeds" );
 aspire_block_assert( str_contains( $response->get_data()['rendered'], $expected ), "$slug visual preview / useful empty state" );
 if ( 'property-finder' !== $slug ) {
  aspire_block_assert( 4 === $type->attributes['count']['default'], "$slug defaults to four" );
  $request->set_param( 'attributes', array( 'count' => 9 ) );
  aspire_block_assert( 400 === rest_do_request( $request )->get_status(), "$slug REST rejects out-of-range count" );
 }
}
$home = get_post( get_option( 'page_on_front' ) );
$before = get_post_meta( $home->ID, '_aspire_before_native_dynamic_blocks', true );
$strip_old = preg_replace( '/<!-- wp:shortcode(?:\s+\{.*?\})?\s*-->\s*\[(aspire_property_finder|aspire_featured_properties|aspire_team_members)\]\s*<!-- \/wp:shortcode -->/s', '', $before );
$strip_new = preg_replace( '/<!-- wp:aspire-core\/(property-finder|featured-properties|team-grid)(?:\s+\{.*?\})?\s*\/-->/s', '', $home->post_content );
aspire_block_assert( $before && $strip_old === $strip_new, 'All unrelated Home content preserved byte for byte' );
echo "SUCCESS: $checks checks\n";
