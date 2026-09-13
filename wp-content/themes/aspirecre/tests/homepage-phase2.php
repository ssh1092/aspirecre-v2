<?php
/** Read-only presentation, locked journeys, SEO and genuine-content checks. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks = 0;
function phase2_check( bool $ok, string $message ): void {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	++$checks; echo "PASS: $message\n";
}
function phase2_text( DOMNode $node ): string { return trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ); }
wp_set_current_user( 0 );
$home = get_post( (int) get_option( 'page_on_front' ) );
phase2_check( $home instanceof WP_Post && 'publish' === $home->post_status, 'Live Home is published' );
$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $home->ID ) );
$GLOBALS['post'] = $home;
setup_postdata( $home );
$no_runtime_fetch = static function () { throw new RuntimeException( 'Homepage rendering must not fetch external APIs.' ); };
add_filter( 'pre_http_request', $no_runtime_fetch );
try { $html = apply_filters( 'the_content', $home->post_content ); } finally { remove_filter( 'pre_http_request', $no_runtime_fetch ); }
$doc = new DOMDocument();
$previous_errors = libxml_use_internal_errors( true );
$doc->loadHTML( '<?xml encoding="utf-8" ?><main id="phase2-test">' . $html . '</main>' );
libxml_clear_errors(); libxml_use_internal_errors( $previous_errors );
$xpath = new DOMXPath( $doc );
$h1 = $xpath->query( '//h1' );
phase2_check( 1 === $h1->length && 'Houston Commercial Real Estate, Made Clear.' === phase2_text( $h1->item( 0 ) ), 'Exactly one H1 with the locked company headline' );
phase2_check( 1 === $xpath->query( '//*[@data-atlas]' )->length, 'Exactly one Atlas application is rendered' );
phase2_check( str_contains( phase2_text( $doc->documentElement ), 'TELL US WHAT YOU NEED' ), 'The company hero exposes its primary conversion action' );
phase2_check( str_contains( phase2_text( $doc->documentElement ), 'Aspire helps tenants, property owners, investors and developers make better commercial real estate decisions across Greater Houston.' ), 'Company purpose and all four audiences are explicit' );
$outline = array(
	'client-journey' => 'Your real estate journey',
	'current-opportunities' => 'Commercial Real Estate Opportunities Across Houston',
	'property-types' => 'Explore Houston Commercial Properties by Type',
	'human-expertise' => 'Meet the Aspire Commercial Team',
	'in-the-field' => 'In the Field: Houston Commercial Real Estate',
	'talk-to-aspire' => 'What’s Your Next Real Estate Move?',
);
foreach ( $outline as $anchor => $heading ) {
	$section = $xpath->query( '//section[@id="' . $anchor . '"]' );
	phase2_check( 1 === $section->length, 'One semantic region: ' . $anchor );
	$headings = $xpath->query( './/h2', $section->item( 0 ) );
	phase2_check( 1 === $headings->length && $heading === phase2_text( $headings->item( 0 ) ), 'Approved section H2: ' . $anchor );
	phase2_check( 0 === $xpath->query( './/h1|.//h4|.//h5|.//h6', $section->item( 0 ) )->length, 'Corporate section preserves H2/H3 hierarchy: ' . $anchor );
}
$journeys = array(
	'tenant' => array( 'define', 'explore', 'compare', 'validate', 'negotiate', 'execute' ),
	'owner' => array( 'assess', 'position', 'market', 'qualify', 'negotiate', 'operate' ),
	'investor' => array( 'define', 'source', 'underwrite', 'diligence', 'negotiate', 'execute' ),
	'management' => array( 'review', 'stabilize', 'lease', 'plan', 'execute', 'reassess' ),
);
$journey = $xpath->query( '//section[@id="client-journey"]' )->item( 0 );
foreach ( $journeys as $track => $stages ) {
	$track_node = $xpath->query( './/section[@id="journey-' . $track . '"]', $journey )->item( 0 );
	phase2_check( $track_node instanceof DOMElement && ! $track_node->hasAttribute( 'hidden' ), 'Journey is server-rendered and readable without JavaScript: ' . $track );
	$nodes = $xpath->query( './/section[contains(concat(" ",normalize-space(@class)," ")," hp-stage ")]', $track_node );
	phase2_check( 6 === $nodes->length, 'Journey retains six approved stages: ' . $track );
	foreach ( $nodes as $index => $stage ) {
		phase2_check( $track . '-' . $stages[ $index ] === $stage->getAttribute( 'id' ) && ! $stage->hasAttribute( 'hidden' ), 'Approved stage sequence is present in the document: ' . $track . '/' . $stages[ $index ] );
		foreach ( array( 'hp-stage-client', 'hp-stage-advisor', 'hp-stage-outcome', 'hp-stage-technology' ) as $part ) {
			$copy = $xpath->query( './/*[contains(concat(" ",normalize-space(@class)," ")," ' . $part . ' ")]', $stage );
			phase2_check( 1 === $copy->length && strlen( phase2_text( $copy->item( 0 ) ) ) > 25, 'Editable client/advisor/outcome/technology copy: ' . $track . '/' . $stages[ $index ] . '/' . $part );
		}
	}
}
phase2_check( 0 === $xpath->query( '//section[contains(concat(" ",normalize-space(@class)," ")," hp-section ")]//details' )->length, 'Ordinary mobile homepage content is not collapsed into accordions' );
foreach ( array( 'Tenant Representation', 'Landlord Representation', 'Investment Sales', 'Investor & Developer Services', 'CRE Consulting', 'Property Management' ) as $service ) {
	phase2_check( str_contains( phase2_text( $journey ), $service ), 'Service entity remains server-rendered: ' . $service );
}
$opportunities = $xpath->query( '//section[@id="current-opportunities"]' )->item( 0 );
$selected = aspire_core_block_featured_properties( 4 );
phase2_check( 4 === count( $selected ) && 4 === $xpath->query( './/article', $opportunities )->length, 'All four current opportunities come from live local inventory' );
foreach ( $selected as $property ) {
	$url = get_permalink( $property->ID );
	phase2_check( 'publish' === $property->post_status && 'property' === $property->post_type && $property->ID === url_to_postid( $url ), 'Opportunity is published with a real permalink: ' . $property->ID );
	phase2_check( 1 === $xpath->query( './/a[@href="' . esc_url( $url ) . '"]', $opportunities )->length, 'Opportunity has one descriptive property link: ' . $property->ID );
	phase2_check( has_post_thumbnail( $property->ID ) && 'attachment' === get_post_type( get_post_thumbnail_id( $property->ID ) ), 'Opportunity uses real Media Library photography: ' . $property->ID );
}
$property_images = $xpath->query( './/img', $opportunities );
phase2_check( 4 === $property_images->length, 'Each current opportunity has a property photograph' );
foreach ( $property_images as $image ) {
	phase2_check( str_starts_with( $image->getAttribute( 'src' ), home_url( '/' ) ) && 'lazy' === $image->getAttribute( 'loading' ) && $image->hasAttribute( 'alt' ) && $image->hasAttribute( 'srcset' ), 'Below-fold opportunity uses a local responsive, lazy image with alt attribute' );
}
foreach ( array( 351, 392 ) as $id ) {
	phase2_check( 'draft' === get_post_status( $id ) && ! str_contains( $html, get_permalink( $id ) ) && ! in_array( $id, wp_list_pluck( $selected, 'ID' ), true ), 'Pilot draft stays out of homepage inventory: ' . $id );
}
phase2_check( ! str_contains( $html, '$3,410,000' ) && ! str_contains( $html, '$2,232,000' ) && ! str_contains( $html, '3410000' ), 'FM 1093 legacy price remains absent from page and Atlas data' );
$all_opportunities = Aspire_Core_Blocks::render( 'properties', array( 'count' => 8, 'presentation' => 'portfolio' ), false );
phase2_check( str_contains( $all_opportunities, esc_url( get_permalink( 122 ) ) ) && ! str_contains( $all_opportunities, '$3,410,000' ) && ! str_contains( $all_opportunities, '$2,232,000' ), 'Portfolio also preserves price suppression when FM is included' );
$team = aspire_core_block_team_members( 8 );
$human = $xpath->query( '//section[@id="human-expertise"]' )->item( 0 );
if ( ! $team ) {
	phase2_check( 0 === $xpath->query( './/article', $human )->length && ! str_contains( phase2_text( $human ), 'profiles will appear' ) && ! str_contains( phase2_text( $human ), 'Portrait to come' ), 'No fabricated Team cards or public placeholder when real Team is empty' );
} else {
	foreach ( $team as $member ) { phase2_check( 'publish' === $member->post_status && str_contains( $doc->saveHTML( $human ), esc_url( get_permalink( $member->ID ) ) ), 'Human section renders real published Team: ' . $member->ID ); }
}
$territories = $xpath->query( '//section[@id="property-types"]//*[@data-property-type]' );
phase2_check( 4 === $territories->length, 'Four real property-type territories are server-rendered' );
foreach ( $territories as $territory ) {
	phase2_check( 1 === $xpath->query( './/h3', $territory )->length && 1 === $xpath->query( './/img', $territory )->length && (int) $territory->getAttribute( 'data-property-count' ) >= 0, 'Property type has a real name, photo and calculated inventory count' );
}
phase2_check( WP_Block_Type_Registry::get_instance()->is_registered( 'aspire-core/field-feed' ), 'In the Field uses the native server-rendered integration block' );
foreach ( array( 'services', 'about', 'team', 'insights', 'tenant-representation', 'landlord-representation', 'investment-sales', 'investor-developer-services', 'property-management', 'cre-consulting' ) as $destination ) {
	$url = aspirecre_home_destination( $destination );
	$anchor = wp_parse_url( $url, PHP_URL_FRAGMENT );
	$target = $anchor ? 1 === $xpath->query( '//*[@id="' . $anchor . '"]' )->length : 'publish' === get_post_status( url_to_postid( $url ) );
	phase2_check( $target, 'Shared header/footer destination exists: ' . $destination );
}
foreach ( $xpath->query( '//section[starts-with(@class,"wp-block-group")]//a[@href]' ) as $link ) {
	$href = html_entity_decode( $link->getAttribute( 'href' ), ENT_QUOTES, 'UTF-8' );
	$label = phase2_text( $link );
	phase2_check( '' !== $label && ! preg_match( '/^(learn more|read more)$/i', $label ), 'Descriptive corporate anchor: ' . $label );
	if ( str_starts_with( $href, 'tel:' ) ) { phase2_check( 'tel:+17139332001' === $href, 'Contact link uses the approved phone' ); continue; }
	$parts = wp_parse_url( $href );
	if ( str_starts_with( $href, '#' ) || ( str_starts_with( $href, home_url( '/' ) ) && ! empty( $parts['fragment'] ) && ( $parts['path'] ?? '/' ) === '/' ) ) {
		$anchor = $parts['fragment'] ?? substr( $href, 1 );
		phase2_check( 1 === $xpath->query( '//*[@id="' . $anchor . '"]' )->length, 'In-page destination exists: ' . $anchor );
		continue;
	}
	if ( str_starts_with( $href, home_url( '/' ) ) ) {
		$target_id = url_to_postid( $href );
		phase2_check( $target_id > 0 && 'publish' === get_post_status( $target_id ), 'Internal destination is published: ' . $label );
	}
}
phase2_check( (int) get_option( 'blog_public' ) === 1 && empty( apply_filters( 'wp_robots', array() )['noindex'] ), 'Homepage remains indexable' );
phase2_check( home_url( '/' ) === wp_get_canonical_url( $home->ID ), 'Homepage canonical resolves to site root' );
phase2_check( 'Houston Commercial Real Estate | Aspire Commercial' === wp_get_document_title(), 'Existing WordPress title pipeline emits the recommended SEO title' );
phase2_check( str_contains( phase2_text( $human ), 'commercial real estate brokerage, advisory and property management' ) && count( $team ) > 0, 'Human section identifies the brokerage, advisory and management company with real people' );
phase2_check( 1 === $xpath->query( '//section[@id="talk-to-aspire"]//a[starts-with(normalize-space(.),"Talk to Aspire") and @href="tel:+17139332001"]' )->length, 'Final primary CTA reaches Aspire directly' );
foreach ( $xpath->query( '//section[contains(@class,"aspire-corporate-section")]//img' ) as $image ) {
	phase2_check( 'lazy' === $image->getAttribute( 'loading' ) && '' !== $image->getAttribute( 'srcset' ), 'Below-fold corporate image is lazy and responsive' );
}
// Choreography removes redundant scaffolding while retaining native source content.
phase2_check( ! str_contains( $html, 'Commercial Real Estate Services Built Around Your Objective' ), 'Redundant objective headline has been removed from published Home' );
phase2_check( 0 === $xpath->query( '//*[contains(concat(" ",normalize-space(@class)," ")," hp-human-transition ")]' )->length, 'Human statement is integrated into Team rather than a separate transition section' );
phase2_check( 1 === $xpath->query( '//section[@id="human-expertise"]//*[contains(concat(" ",normalize-space(@class)," ")," hp-human-statement ")]' )->length, 'Editable human statement is retained once within Team' );
phase2_check( 4 === $xpath->query( '//*[contains(concat(" ",normalize-space(@class)," ")," hp-type-description ") and normalize-space(.)!=""]' )->length, 'All four property-type descriptions render without JavaScript' );
$escaped_types = Aspire_Core_Blocks::render( 'types', array( 'descriptions' => array( 'office' => '<script>alert(1)</script>', 'land' => array( 'unexpected' ) ) ), false );
phase2_check( ! str_contains( $escaped_types, '<script>' ) && str_contains( $escaped_types, '&lt;script&gt;' ), 'Type descriptions escape markup and safely ignore malformed values' );
$before_choreography = get_post_meta( $home->ID, '_aspirecre_before_home_choreography', true );
$collect_stages = function ( $blocks ) use ( &$collect_stages ): array {
    $result = array();
    foreach ( $blocks as $block ) {
        if ( in_array( 'hp-stage', explode( ' ', $block['attrs']['className'] ?? '' ), true ) ) { $result[ $block['attrs']['anchor'] ] = serialize_block( $block ); }
        $result += $collect_stages( $block['innerBlocks'] );
    }
    return $result;
};
phase2_check( is_string( $before_choreography ) && 24 === count( $collect_stages( parse_blocks( $before_choreography ) ) ) && $collect_stages( parse_blocks( $before_choreography ) ) === $collect_stages( parse_blocks( $home->post_content ) ), 'All 24 approved native journey stages are byte-for-byte preserved' );
wp_reset_postdata();
echo "SUCCESS: $checks homepage presentation checks\n";
