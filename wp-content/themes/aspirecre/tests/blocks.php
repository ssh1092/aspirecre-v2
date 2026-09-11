<?php
/** Read-only Gutenberg migration assertions. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
$checks = 0;
function aspirecre_block_check( $ok, $message ) {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	++$checks; echo "PASS: $message\n";
}
$home = get_post( (int) get_option( 'page_on_front' ) );
aspirecre_block_check( $home && 'Home' === $home->post_title && 'page' === $home->post_type && 'publish' === $home->post_status, 'Published Home exists under Pages' );
aspirecre_block_check( 'page' === get_option( 'show_on_front' ), 'Static homepage configured' );
aspirecre_block_check( use_block_editor_for_post( $home ), 'Home uses Gutenberg' );
$top = array_values( array_filter( parse_blocks( $home->post_content ), static fn( $b ) => $b['blockName'] ) );
aspirecre_block_check( array( 'Hero', 'Property Finder', 'Featured Properties', 'Services', 'Houston / Why Aspire', 'Team', 'Final CTA' ) === array_map( static fn( $b ) => $b['attrs']['metadata']['name'], $top ), 'Seven named sections in approved order' );
$flat = array();
$walk = static function ( $blocks ) use ( &$walk, &$flat ) { foreach ( $blocks as $block ) { if ( $block['blockName'] ) { $flat[] = $block; } $walk( $block['innerBlocks'] ); } };
$walk( $top );
foreach ( $flat as $block ) { aspirecre_block_check( ( str_starts_with( $block['blockName'], 'core/' ) || in_array( $block['blockName'], array( 'aspire-core/property-finder', 'aspire-core/featured-properties', 'aspire-core/team-grid' ), true ) ) && ! in_array( $block['blockName'], array( 'core/html', 'core/freeform' ), true ), 'Native editable ' . $block['blockName'] ); }
$types = array_count_values( array_column( $flat, 'blockName' ) );
aspirecre_block_check( 2 === $types['core/image'], 'Both photos are native Image blocks' );
aspirecre_block_check( ! isset( $types['core/shortcode'] ), 'No shortcode blocks remain on Home' );
foreach ( array( 'property-finder', 'featured-properties', 'team-grid' ) as $slug ) { aspirecre_block_check( 1 === $types[ 'aspire-core/' . $slug ] && WP_Block_Type_Registry::get_instance()->is_registered( 'aspire-core/' . $slug ), 'Registered native dynamic block: ' . $slug ); }
foreach ( array( 'aspire_property_finder', 'aspire_featured_properties', 'aspire_team_members' ) as $shortcode ) { aspirecre_block_check( shortcode_exists( $shortcode ) && ! str_contains( do_shortcode( '[' . $shortcode . ']' ), '[' . $shortcode . ']' ), "$shortcode renders" ); }
aspirecre_block_check( str_contains( $home->post_content, 'Your real estate goals.<br>A more direct path.' ) && str_contains( $home->post_content, 'Market-driven' ) && str_contains( $home->post_content, "What's your next real estate move?" ), 'Approved service, Houston and CTA copy stored in page' );
$count = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'title' => 'Home', 'numberposts' => -1 ) );
aspirecre_block_check( 1 === count( $count ), 'No duplicate Home page' );
echo "SUCCESS: $checks checks\n";
