<?php
/** Explicit one-time CLI migration. Never executes on a web request. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$existing = get_page_by_path( 'home', OBJECT, 'page' );
if ( ! $existing ) {
	$matches = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'title' => 'Home', 'numberposts' => 1 ) );
	$existing = $matches[0] ?? null;
}
if ( $existing && get_post_meta( $existing->ID, '_aspirecre_block_home_v1', true ) ) {
	$id = $existing->ID;
} else {
	$content = '';
	foreach ( array( 'hero', 'finder', 'properties', 'services', 'houston', 'team', 'contact' ) as $section ) {
		ob_start(); include dirname( __DIR__ ) . '/patterns/home-' . $section . '.php'; $content .= ob_get_clean() . "\n";
	}
	// Keep a restorable copy before replacing any existing page content.
	if ( $existing && $existing->post_content ) { add_post_meta( $existing->ID, '_aspirecre_before_block_migration', $existing->post_content, true ); wp_save_post_revision( $existing->ID ); }
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	$data = array( 'post_author' => $existing ? $existing->post_author : ( $admins[0]->ID ?? 0 ), 'post_title' => 'Home', 'post_name' => 'home', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => wp_slash( $content ) );
	if ( $existing ) { $data['ID'] = $existing->ID; }
	$id = wp_insert_post( $data, true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	update_post_meta( $id, '_aspirecre_block_home_v1', 1 );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $id );
echo "Home page ID: $id; static front page configured.\n";
