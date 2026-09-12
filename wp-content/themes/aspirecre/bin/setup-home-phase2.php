<?php
/** One-time Home-only Phase 2 setup. Run explicitly with PHP CLI in WordPress.
 * Re-running preserves all subsequent Gutenberg edits. No Property/media writes.
 */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$home = get_page_by_path( 'home', OBJECT, 'page' );
if ( ! $home ) {
	$matches = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'title' => 'Home', 'numberposts' => 2 ) );
	if ( count( $matches ) > 1 ) { throw new RuntimeException( 'Multiple Home pages found; no changes made.' ); }
	$home = $matches[0] ?? null;
}
if ( $home && get_post_meta( $home->ID, '_aspirecre_home_phase2', true ) ) {
	echo 'Home ' . $home->ID . " already has Phase 2; Gutenberg edits preserved.\n";
	exit;
}
if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'aspire/atlas' ) ) { throw new RuntimeException( 'Aspire Atlas must be active.' ); }
$attributes = array();
if ( $home ) {
	foreach ( parse_blocks( $home->post_content ) as $block ) {
		if ( 'aspire/atlas' === $block['blockName'] ) { $attributes = $block['attrs']; break; }
	}
}
$attributes = array_merge( $attributes, array(
	'corporateHero' => true,
	'anchor' => 'aspire-atlas',
	'headline' => 'Houston Commercial Real Estate, Made Clear.',
	'supportingText' => 'Aspire Commercial helps tenants, property owners, investors and developers navigate leasing, investment, development, property management and commercial real estate decisions across Greater Houston.',
	'productLine' => 'Explore Houston. Find your next move.',
	'showNaturalLanguage' => true,
	'enableBrief' => true,
	'metadata' => array( 'name' => 'Aspire Atlas — Company Hero' ),
) );
$content = '<!-- wp:aspire/atlas ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . " /-->\n\n";
foreach ( array( 'opportunities', 'objectives', 'human', 'expertise', 'process', 'atlas', 'management', 'insights', 'contact' ) as $section ) {
	ob_start(); include dirname( __DIR__ ) . '/patterns/phase2-' . $section . '.php'; $content .= trim( ob_get_clean() ) . "\n\n";
}
$sections = array_values( array_filter( parse_blocks( $content ), static fn( $block ) => null !== $block['blockName'] ) );
if ( count( $sections ) !== 10 ) { throw new RuntimeException( 'Expected Atlas plus nine native sections; no changes made.' ); }
if ( $home ) {
	add_post_meta( $home->ID, '_aspirecre_before_home_phase2', wp_slash( $home->post_content ), true );
	wp_save_post_revision( $home->ID );
}
$data = array( 'post_title' => 'Home', 'post_name' => 'home', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => wp_slash( trim( $content ) ) );
if ( $home ) { $data['ID'] = $home->ID; }
$id = $home ? wp_update_post( $data, true ) : wp_insert_post( $data, true );
if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
update_post_meta( $id, '_aspirecre_home_phase2', 1 );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $id );
echo "Home $id published as static front page with 10 Gutenberg sections.\n";
