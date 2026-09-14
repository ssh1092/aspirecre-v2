<?php
/** Replace only Home's journey composition with the full-width integrated native-block version. */
if ( PHP_SAPI !== 'cli' ) { exit; }
if ( ! function_exists( 'get_option' ) ) {
	throw new RuntimeException( 'WordPress is not loaded. Run this migration with wp eval-file.' );
}

$migration_option = 'aspire_home_integrated_journey_v2_fullwidth';
if ( get_option( $migration_option, false ) ) {
	echo "Integrated homepage journey full-width migration v2 already completed; no changes made.\n";
	exit;
}

$front_page_id = (int) get_option( 'page_on_front' );
$home = get_post( $front_page_id );
if ( $front_page_id < 1 || 'page' !== get_option( 'show_on_front' ) || ! $home || $front_page_id !== (int) $home->ID || 'page' !== $home->post_type || 'publish' !== $home->post_status ) {
	throw new RuntimeException( 'Expected the published static front page. No changes made.' );
}

$blocks = parse_blocks( $home->post_content );
$matches = array();
foreach ( $blocks as $index => $block ) {
	if ( 'core/group' === ( $block['blockName'] ?? '' ) && 'client-journey' === ( $block['attrs']['anchor'] ?? '' ) ) { $matches[] = $index; }
}
if ( 1 !== count( $matches ) ) { throw new RuntimeException( 'Expected exactly one top-level client-journey group. No changes made.' ); }

$render_pattern = static function () {
	ob_start();
	require get_theme_file_path( '/patterns/presentation-journey.php' );
	return ob_get_clean();
};

$has_class = static function ( array $block, string $class ): bool {
	return in_array( $class, preg_split( '/\s+/', $block['attrs']['className'] ?? '', -1, PREG_SPLIT_NO_EMPTY ), true );
};
$validate_fullwidth_journey = static function ( array $journey ) use ( $has_class ): bool {
	if ( 'core/group' !== ( $journey['blockName'] ?? '' ) || 'client-journey' !== ( $journey['attrs']['anchor'] ?? '' ) ) { return false; }
	$integrated = array_values( array_filter( $journey['innerBlocks'] ?? array(), static fn( $block ) => $has_class( $block, 'hp-integrated-journey' ) ) );
	if ( 1 !== count( $integrated ) || 'full' !== ( $integrated[0]['attrs']['align'] ?? '' ) ) { return false; }
	$tracks = array_values( array_filter( $integrated[0]['innerBlocks'] ?? array(), static fn( $block ) => $has_class( $block, 'hp-journey-track' ) ) );
	if ( 4 !== count( $tracks ) ) { return false; }
	$stage_count = 0;
	foreach ( $tracks as $track ) {
		if ( 'full' !== ( $track['attrs']['align'] ?? '' ) ) { return false; }
		$copies = array_values( array_filter( $track['innerBlocks'] ?? array(), static fn( $block ) => $has_class( $block, 'hp-stage-copy' ) ) );
		if ( 1 !== count( $copies ) || 'full' !== ( $copies[0]['attrs']['align'] ?? '' ) ) { return false; }
		$stages = array_values( array_filter( $copies[0]['innerBlocks'] ?? array(), static fn( $block ) => $has_class( $block, 'hp-stage' ) ) );
		if ( 6 !== count( $stages ) ) { return false; }
		foreach ( $stages as $stage ) {
			if ( 'full' !== ( $stage['attrs']['align'] ?? '' ) ) { return false; }
			$panels = array_values( array_filter( $stage['innerBlocks'] ?? array(), static fn( $block ) => $has_class( $block, 'hp-stage-panel' ) ) );
			if ( 1 !== count( $panels ) || 'full' !== ( $panels[0]['attrs']['align'] ?? '' ) ) { return false; }
			++$stage_count;
		}
	}
	return 24 === $stage_count;
};

$replacement = array_values( array_filter( parse_blocks( trim( $render_pattern() ) ), static fn( $block ) => null !== $block['blockName'] ) );
if ( 1 !== count( $replacement ) || ! $validate_fullwidth_journey( $replacement[0] ) ) {
	throw new RuntimeException( 'Integrated journey pattern lacks the required full-width block hierarchy. No changes made.' );
}

$backup_key = '_aspirecre_before_integrated_journey_v2_fullwidth';
if ( ! add_post_meta( $front_page_id, $backup_key, wp_slash( $home->post_content ), true ) ) {
	throw new RuntimeException( 'Could not create the v2 full-width journey backup. No changes made.' );
}
wp_save_post_revision( $front_page_id );
$blocks[ $matches[0] ] = $replacement[0];
$result = wp_update_post( array( 'ID' => $front_page_id, 'post_content' => wp_slash( trim( serialize_blocks( $blocks ) ) ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }

$saved_home = get_post( $front_page_id );
$saved_matches = array_values( array_filter( parse_blocks( $saved_home->post_content ), static fn( $block ) => 'core/group' === ( $block['blockName'] ?? '' ) && 'client-journey' === ( $block['attrs']['anchor'] ?? '' ) ) );
if ( 1 !== count( $saved_matches ) || ! $validate_fullwidth_journey( $saved_matches[0] ) ) {
	wp_update_post( array( 'ID' => $front_page_id, 'post_content' => wp_slash( $home->post_content ) ) );
	throw new RuntimeException( 'Saved Home failed full-width journey validation; original content was restored.' );
}
if ( ! update_option( $migration_option, $front_page_id, false ) ) {
	throw new RuntimeException( 'Journey updated, but the v2 migration completion option could not be saved.' );
}
echo "Home $front_page_id: replaced only the client journey with the full-width integrated editable experience.\n";
