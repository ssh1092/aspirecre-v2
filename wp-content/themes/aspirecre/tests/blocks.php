<?php
/** Read-only assertions for the approved presentation-led Gutenberg homepage. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
$checks = 0;
function aspirecre_block_check( $ok, $message ): void {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	++$checks; echo "PASS: $message\n";
}
$home = get_post( (int) get_option( 'page_on_front' ) );
aspirecre_block_check( $home && 'Home' === $home->post_title && 'page' === $home->post_type && 'publish' === $home->post_status, 'Published Home exists under Pages' );
aspirecre_block_check( 'page' === get_option( 'show_on_front' ), 'Static homepage configured' );
aspirecre_block_check( use_block_editor_for_post( $home ), 'Home uses Gutenberg' );
$top = array_values( array_filter( parse_blocks( $home->post_content ), static fn( $b ) => $b['blockName'] ) );
aspirecre_block_check( 7 === count( $top ) && 'aspire/atlas' === $top[0]['blockName'], 'One Atlas hero followed by six presentation sections' );
aspirecre_block_check( true === ( $top[0]['attrs']['corporateHero'] ?? false ), 'Atlas hero uses company-first presentation' );
$anchors = array( 'client-journey', 'current-opportunities', 'property-types', 'human-expertise', 'in-the-field', 'talk-to-aspire' );
aspirecre_block_check( $anchors === array_map( static fn( $b ) => $b['attrs']['anchor'] ?? '', array_slice( $top, 1 ) ), 'Corporate section order matches the approved narrative' );
foreach ( array_slice( $top, 1 ) as $section ) {
	aspirecre_block_check( 'core/group' === $section['blockName'] && 'section' === ( $section['attrs']['tagName'] ?? '' ) && ! empty( $section['attrs']['metadata']['name'] ), 'Named native section: ' . $section['attrs']['anchor'] );
}
$flat = array();
$walk = static function ( $blocks ) use ( &$walk, &$flat ): void {
	foreach ( $blocks as $block ) { if ( $block['blockName'] ) { $flat[] = $block; } $walk( $block['innerBlocks'] ); }
};
$walk( $top );
$allowed_dynamic = array( 'aspire/atlas', 'aspire-core/featured-properties', 'aspire-core/property-types', 'aspire-core/team-grid', 'aspire-core/field-feed' );
$invalid = array_filter( $flat, static fn( $b ) => ( ! str_starts_with( $b['blockName'], 'core/' ) && ! in_array( $b['blockName'], $allowed_dynamic, true ) ) || in_array( $b['blockName'], array( 'core/html', 'core/freeform', 'core/shortcode' ), true ) );
aspirecre_block_check( ! $invalid, 'Static content uses native editable blocks, without HTML, freeform or shortcodes' );
$types = array_count_values( array_column( $flat, 'blockName' ) );
foreach ( $allowed_dynamic as $name ) {
	aspirecre_block_check( 1 === ( $types[ $name ] ?? 0 ) && WP_Block_Type_Registry::get_instance()->is_registered( $name ), 'One registered dynamic block: ' . $name );
}
$featured = current( array_filter( $flat, static fn( $b ) => 'aspire-core/featured-properties' === $b['blockName'] ) );
$team = current( array_filter( $flat, static fn( $b ) => 'aspire-core/team-grid' === $b['blockName'] ) );
aspirecre_block_check( 4 === $featured['attrs']['count'] && 'portfolio' === $featured['attrs']['presentation'], 'Opportunities reuse the featured block with all four portfolio listings' );
aspirecre_block_check( true === $team['attrs']['hideWhenEmpty'] && 'portraits' === $team['attrs']['presentation'], 'Real Team component is present and safely hides empty public inventory' );
aspirecre_block_check( ( $types['core/image'] ?? 0 ) >= 1, 'Corporate photography is editable through native Image blocks' );
$stages = array_filter( $flat, static fn( $b ) => 'core/group' === $b['blockName'] && in_array( 'hp-stage', explode( ' ', $b['attrs']['className'] ?? '' ), true ) );
aspirecre_block_check( 24 === count( $stages ), 'All 24 approved journey stages are native editable Groups' );
foreach ( array( 'tenant', 'owner', 'investor', 'management' ) as $track ) {
	$tracks = array_filter( $flat, static fn( $b ) => in_array( 'hp-track-' . $track, explode( ' ', $b['attrs']['className'] ?? '' ), true ) );
	aspirecre_block_check( 1 === count( $tracks ), 'One native journey track: ' . $track );
}
aspirecre_block_check( ! isset( $types['core/details'] ), 'Ordinary homepage sections are not mobile accordions' );
$headings = array_values( array_filter( $flat, static fn( $b ) => 'core/heading' === $b['blockName'] ) );
aspirecre_block_check( count( $headings ) >= 30 && ! array_filter( $headings, static fn( $b ) => ! in_array( $b['attrs']['level'] ?? 2, array( 2, 3 ), true ) ), 'Editable marketing headings use H2 and H3; Atlas owns the single H1' );
foreach ( array( 'aspire_property_finder', 'aspire_featured_properties', 'aspire_team_members' ) as $shortcode ) {
	aspirecre_block_check( shortcode_exists( $shortcode ) && ! str_contains( do_shortcode( '[' . $shortcode . ']' ), '[' . $shortcode . ']' ), 'Legacy rendering remains compatible: ' . $shortcode );
}
aspirecre_block_check( ! str_contains( $home->post_content, 'Atascocita Retail Center' ) && ! str_contains( $home->post_content, 'Sugarwell Plaza' ) && ! str_contains( $home->post_content, 'Sienna Park Office Condos' ), 'Property records are not hardcoded in marketing content' );
$count = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'title' => 'Home', 'numberposts' => -1 ) );
aspirecre_block_check( 1 === count( $count ), 'No duplicate Home page' );
$template = file_get_contents( get_theme_file_path( '/front-page.php' ) );
aspirecre_block_check( str_contains( $template, 'the_content()' ) && ! str_contains( $template, 'template-parts/home/' ) && ! str_contains( $template, 'Houston Commercial Real Estate' ), 'Front page remains a normal content template' );
echo "SUCCESS: $checks Gutenberg checks\n";
