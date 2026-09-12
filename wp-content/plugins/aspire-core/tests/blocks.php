<?php
/** Read-only native block registration, live preview and compatibility checks. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
define( 'REST_REQUEST', true );
$checks = 0;
function aspire_block_assert( $ok, $label ): void {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	++$checks; echo "PASS: $label\n";
}
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $admin[0]->ID );
foreach ( array( 'property-finder', 'featured-properties', 'team-grid' ) as $slug ) {
	$type = WP_Block_Type_Registry::get_instance()->get_registered( 'aspire-core/' . $slug );
	aspire_block_assert( $type && 3 === $type->api_version && 'aspirecre' === $type->category && $type->is_dynamic(), "$slug modern dynamic registration" );
	$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/aspire-core/' . $slug );
	$request->set_param( 'context', 'edit' );
	$request->set_param( 'attributes', 'property-finder' === $slug ? array() : array( 'count' => 4 ) );
	$response = rest_do_request( $request );
	aspire_block_assert( 200 === $response->get_status(), "$slug authenticated REST preview succeeds" );
	$html = $response->get_data()['rendered'];
	if ( 'property-finder' === $slug ) {
		aspire_block_assert( str_contains( $html, 'Find a property' ) && str_contains( $html, 'method="get"' ), 'Finder keeps its native form preview' );
	} else {
		$records = 'featured-properties' === $slug ? aspire_core_block_featured_properties( 4 ) : aspire_core_block_team_members( 4 );
		if ( $records ) {
			foreach ( $records as $record ) { aspire_block_assert( str_contains( $html, esc_url( get_permalink( $record->ID ) ) ), "$slug preview links to real published record {$record->ID}" ); }
		} else {
			$empty = 'featured-properties' === $slug ? 'No featured properties are available yet.' : 'No team members have been published yet.';
			aspire_block_assert( str_contains( $html, $empty ), "$slug editor explains genuinely empty inventory" );
		}
		aspire_block_assert( 4 === $type->attributes['count']['default'], "$slug legacy count default remains four" );
		$request->set_param( 'attributes', array( 'count' => 9 ) );
		aspire_block_assert( 400 === rest_do_request( $request )->get_status(), "$slug REST rejects out-of-range count" );
		$request->set_param( 'attributes', array( 'count' => 3, 'presentation' => 'editorial' ) );
		aspire_block_assert( 200 === rest_do_request( $request )->get_status(), "$slug supports the corporate editorial presentation" );
	}
}
// Cover empty previews without creating, hiding or modifying real records.
$empty_scope = static function ( $query ): void {
	if ( in_array( $query->get( 'post_type' ), array( 'property', 'team_member' ), true ) ) { $query->set( 'post__in', array( -1 ) ); }
};
add_action( 'pre_get_posts', $empty_scope );
try {
	aspire_block_assert( str_contains( Aspire_Core_Blocks::render( 'properties', array(), true ), 'No featured properties are available yet.' ), 'Property editor empty state remains useful' );
	aspire_block_assert( str_contains( Aspire_Core_Blocks::render( 'team', array( 'hideWhenEmpty' => true ), true ), 'No team members have been published yet.' ), 'Hidden public Team component still has an editor preview' );
	$empty_public = Aspire_Core_Blocks::render( 'team', array( 'hideWhenEmpty' => true ), false );
	aspire_block_assert( '' === trim( wp_strip_all_tags( $empty_public ) ), 'Corporate Team component adds no fictional public cards or placeholder text' );
} finally { remove_action( 'pre_get_posts', $empty_scope ); }
$home = get_post( (int) get_option( 'page_on_front' ) );
aspire_block_assert( $home && has_block( 'aspire/atlas', $home ) && has_block( 'aspire-core/featured-properties', $home ) && has_block( 'aspire-core/team-grid', $home ), 'Home composes the existing dynamic blocks with normal Gutenberg content' );
aspire_block_assert( ! has_block( 'core/shortcode', $home ), 'Home exposes no shortcodes to editors' );
echo "SUCCESS: $checks native block checks\n";
