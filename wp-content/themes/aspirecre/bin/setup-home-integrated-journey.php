<?php
/** Replace only Home's journey composition with the integrated native-block version. */
if ( PHP_SAPI !== 'cli' ) { exit; }
if ( ! function_exists( 'get_option' ) ) {
	throw new RuntimeException( 'WordPress is not loaded. Run this migration with wp eval-file.' );
}

$migration_option = 'aspire_home_integrated_journey_v1';
if ( get_option( $migration_option, false ) ) {
	echo "Integrated homepage journey migration v1 already completed; no changes made.\n";
	exit;
}

$front_page_id = (int) get_option( 'page_on_front' );
$home = get_post( $front_page_id );
if ( $front_page_id < 1 || 'page' !== get_option( 'show_on_front' ) || ! $home || $front_page_id !== (int) $home->ID || 'page' !== $home->post_type || 'publish' !== $home->post_status ) {
	throw new RuntimeException( 'Expected the published Home front page. No changes made.' );
}

$blocks = parse_blocks( $home->post_content );
$matches = array();
foreach ( $blocks as $index => $block ) {
	if ( 'client-journey' === ( $block['attrs']['anchor'] ?? '' ) ) { $matches[] = $index; }
}
if ( 1 !== count( $matches ) ) { throw new RuntimeException( 'Expected exactly one client-journey block. No changes made.' ); }

$render_pattern = static function () {
	ob_start();
	require get_theme_file_path( '/patterns/presentation-journey.php' );
	return ob_get_clean();
};
$replacement = array_values( array_filter( parse_blocks( trim( $render_pattern() ) ), static fn( $block ) => null !== $block['blockName'] ) );
if ( 1 !== count( $replacement ) || 'client-journey' !== ( $replacement[0]['attrs']['anchor'] ?? '' ) ) {
	throw new RuntimeException( 'Integrated journey pattern is invalid. No changes made.' );
}

add_post_meta( $front_page_id, '_aspirecre_before_integrated_journey', wp_slash( $home->post_content ), true );
wp_save_post_revision( $front_page_id );
$blocks[ $matches[0] ] = $replacement[0];
$result = wp_update_post( array( 'ID' => $front_page_id, 'post_content' => wp_slash( trim( serialize_blocks( $blocks ) ) ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
if ( ! update_option( $migration_option, $front_page_id, false ) ) {
	throw new RuntimeException( 'Journey updated, but the migration completion option could not be saved.' );
}
echo "Home $front_page_id: replaced only the client journey with the integrated editable experience.\n";
