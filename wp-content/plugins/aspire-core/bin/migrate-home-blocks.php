<?php
/** Explicit, repeatable migration; preserves every unrelated content byte. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$id = (int) get_option( 'page_on_front' );
$page = get_post( $id );
if ( ! $page || 'page' !== $page->post_type ) { throw new RuntimeException( 'No existing static Home page.' ); }
$map = array( 'aspire_property_finder' => 'property-finder', 'aspire_featured_properties' => 'featured-properties', 'aspire_team_members' => 'team-grid' );
$changed = 0;
$content = preg_replace_callback( '/<!-- wp:shortcode(?:\s+(\{.*?\}))?\s*-->\s*\[(aspire_property_finder|aspire_featured_properties|aspire_team_members)\]\s*<!-- \/wp:shortcode -->/s', static function ( $match ) use ( $map, &$changed ) {
	$attrs = ! empty( $match[1] ) ? json_decode( $match[1], true ) : array();
	$attrs = is_array( $attrs ) ? $attrs : array();
	$slug = $map[ $match[2] ];
	if ( 'property-finder' !== $slug ) { $attrs['count'] = 4; }
	++$changed;
	return '<!-- wp:aspire-core/' . $slug . ( $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) : '' ) . ' /-->';
}, $page->post_content );
if ( $changed ) {
	add_post_meta( $id, '_aspire_before_native_dynamic_blocks', wp_slash( $page->post_content ), true );
	wp_save_post_revision( $id );
	$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $content ) ), true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
}
echo "Home $id: migrated $changed Aspire shortcode blocks.\n";
