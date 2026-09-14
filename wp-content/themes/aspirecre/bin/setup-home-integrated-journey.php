<?php
/** Replace only Home's journey composition with the integrated native-block version. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';

$migration_option = 'aspire_home_integrated_journey_v1';
if ( get_option( $migration_option, false ) ) {
	echo "Integrated homepage journey migration v1 already completed; no changes made.\n";
	exit;
}

$id = (int) get_option( 'page_on_front' );
$home = get_post( $id );
if ( $id < 1 || 'page' !== get_option( 'show_on_front' ) || ! $home || $id !== (int) $home->ID || 'page' !== $home->post_type || 'publish' !== $home->post_status ) {
	throw new RuntimeException( 'Expected the published Home front page. No changes made.' );
}

$blocks = parse_blocks( $home->post_content );
$matches = array();
foreach ( $blocks as $index => $block ) {
	if ( 'client-journey' === ( $block['attrs']['anchor'] ?? '' ) ) { $matches[] = $index; }
}
if ( 1 !== count( $matches ) ) { throw new RuntimeException( 'Expected exactly one client-journey block. No changes made.' ); }

ob_start();
require get_theme_file_path( '/patterns/presentation-journey.php' );
$replacement = array_values( array_filter( parse_blocks( trim( ob_get_clean() ) ), static fn( $block ) => null !== $block['blockName'] ) );
if ( 1 !== count( $replacement ) || 'client-journey' !== ( $replacement[0]['attrs']['anchor'] ?? '' ) ) {
	throw new RuntimeException( 'Integrated journey pattern is invalid. No changes made.' );
}

add_post_meta( $id, '_aspirecre_before_integrated_journey', wp_slash( $home->post_content ), true );
wp_save_post_revision( $id );
$blocks[ $matches[0] ] = $replacement[0];
$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( trim( serialize_blocks( $blocks ) ) ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
if ( ! update_option( $migration_option, $id, false ) ) {
	throw new RuntimeException( 'Journey updated, but the migration completion option could not be saved.' );
}
echo "Home $id: replaced only the client journey with the integrated editable experience.\n";
