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
		$request->set_param( 'attributes', array( 'count' => 4, 'presentation' => 'featured-properties' === $slug ? 'portfolio' : 'portraits' ) );
		aspire_block_assert( 200 === rest_do_request( $request )->get_status(), "$slug supports the presentation-led rail" );
	}
}
$type = WP_Block_Type_Registry::get_instance()->get_registered( 'aspire-core/property-types' );
aspire_block_assert( $type && 3 === $type->api_version && $type->is_dynamic(), 'Property Types is a native API v3 dynamic block' );
$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/aspire-core/property-types' );
$request->set_param( 'context', 'edit' );
$preview = rest_do_request( $request );
aspire_block_assert( 200 === $preview->get_status() && str_contains( $preview->get_data()['rendered'], 'hp-property-types' ), 'Property Types has a real static editor preview' );

function aspire_block_dom( string $html ): DOMXPath {
	$doc = new DOMDocument();
	$old_errors = libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors(); libxml_use_internal_errors( $old_errors );
	return new DOMXPath( $doc );
}
$portfolio = Aspire_Core_Blocks::render( 'properties', array( 'count' => 4, 'presentation' => 'portfolio' ), false );
$portfolio_dom = aspire_block_dom( $portfolio );
$expected_properties = aspire_core_block_featured_properties( 4 );
$articles = $portfolio_dom->query( '//*[@data-property-id]' );
aspire_block_assert( 4 === $articles->length && count( $expected_properties ) === $articles->length, 'Portfolio includes all four real published current properties' );
foreach ( $articles as $position => $article ) {
	$id = (int) $article->getAttribute( 'data-property-id' );
	aspire_block_assert( $expected_properties[ $position ]->ID === $id && 'publish' === get_post_status( $id ), 'Portfolio retains existing featured/current server order: ' . $id );
	$slugs = wp_get_object_terms( $id, 'transaction_type', array( 'fields' => 'slugs' ) );
	$actual_slugs = explode( ' ', $article->getAttribute( 'data-transactions' ) );
	sort( $slugs ); sort( $actual_slugs );
	aspire_block_assert( $slugs === $actual_slugs && (string) $position === $article->getAttribute( 'data-editorial-order' ), 'Intent prioritization has real transaction slugs and recoverable original order: ' . $id );
	aspire_block_assert( 1 === $portfolio_dom->query( './/h3', $article )->length && 1 === $portfolio_dom->query( './/a[@href="' . esc_url( get_permalink( $id ) ) . '"]', $article )->length && $portfolio_dom->query( './/dd', $article )->length <= 1, 'Portfolio has a server-rendered name, real action and at most one metric: ' . $id );
	$image = $portfolio_dom->query( './/img', $article )->item( 0 );
	aspire_block_assert( $image && $image->hasAttribute( 'srcset' ) && 'lazy' === $image->getAttribute( 'loading' ) && str_starts_with( $image->getAttribute( 'src' ), home_url( '/' ) ), 'Portfolio uses local responsive lazy photography: ' . $id );
}
aspire_block_assert( ! str_contains( $portfolio, '$3,410,000' ) && ! str_contains( $portfolio, '$2,232,000' ) && ! str_contains( $portfolio, '3410000' ), 'Portfolio never exposes suppressed FM pricing' );
foreach ( array( 351, 392 ) as $draft ) { aspire_block_assert( ! str_contains( $portfolio, 'data-property-id="' . $draft . '"' ), 'Draft excluded from portfolio: ' . $draft ); }
$territories = aspire_core_block_property_types();
aspire_block_assert( array( 'office', 'industrial-flex', 'land', 'retail' ) === wp_list_pluck( $territories, 'slug' ), 'Four real property-type territories retain the approved order' );
$published = get_posts( array( 'post_type' => 'property', 'post_status' => 'publish', 'numberposts' => -1 ) );
foreach ( $territories as $territory ) {
	$expected = array_filter( $published, static fn( $property ) => has_term( $territory['slug'], 'property_type', $property->ID ) && ( ! metadata_exists( 'post', $property->ID, '_aspire_listing_status' ) || 'available' === get_post_meta( $property->ID, '_aspire_listing_status', true ) ) );
	aspire_block_assert( count( $expected ) === $territory['count'], 'Property-type count uses current published inventory: ' . $territory['slug'] );
	$images = array_map( static fn( $property ) => get_post_thumbnail_id( $property->ID ), $expected );
	aspire_block_assert( in_array( $territory['image'], $images, true ) && wp_attachment_is_image( $territory['image'] ), 'Type territory photograph belongs to a real matching property: ' . $territory['slug'] );
	aspire_block_assert( add_query_arg( 'type', $territory['slug'], home_url( '/properties/' ) ) === $territory['url'], 'Property type links use supported Directory filter URLs: ' . $territory['slug'] );
}
$portraits = Aspire_Core_Blocks::render( 'team', array( 'count' => 8, 'presentation' => 'portraits', 'hideWhenEmpty' => true ), false );
$members = aspire_core_block_team_members( 8 );
if ( $members ) {
	$portrait_dom = aspire_block_dom( $portraits );
	aspire_block_assert( count( $members ) === $portrait_dom->query( '//*[@data-team-id]' )->length, 'Portrait rail uses only published Team records' );
	foreach ( $members as $member ) {
		$article = $portrait_dom->query( '//*[@data-team-id="' . $member->ID . '"]' )->item( 0 );
		aspire_block_assert( $article && str_contains( $article->textContent, get_the_title( $member->ID ) ) && str_contains( $portraits, esc_url( get_permalink( $member->ID ) ) ), 'Portrait identity/profile come from the Team CPT: ' . $member->ID );
		foreach ( array( 'job_title', 'short_bio' ) as $key ) {
			$value = get_post_meta( $member->ID, '_aspire_' . $key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) { aspire_block_assert( str_contains( $article->textContent, $value ), 'Portrait preserves the verified saved ' . $key . ': ' . $member->ID ); }
		}
	}
} else { aspire_block_assert( '' === $portraits, 'No fabricated portraits when no real Team is available' ); }
foreach ( array( $portfolio, Aspire_Core_Blocks::render( 'types', array(), false ), $portraits ) as $rail ) {
	if ( '' === $rail ) { continue; }
	$dom = aspire_block_dom( $rail );
	aspire_block_assert( 1 === $dom->query( '//*[@data-hp-rail-controls and @hidden]' )->length && 2 === $dom->query( '//button[@aria-label and @aria-controls]' )->length, 'Rail controls become available only with the progressive enhancer' );
	aspire_block_assert( 0 === $dom->query( '//*[@data-hp-rail-item and @hidden]|//*[@data-hp-rail-track and @hidden]' )->length && ! str_contains( $rail, '<script' ) && ! str_contains( $rail, 'canvas' ), 'Rail content is readable/indexable without JavaScript or WebGL' );
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
