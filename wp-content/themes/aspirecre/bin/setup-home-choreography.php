<?php
/** Explicit Home-only editorial cleanup. Existing journey copy and other blocks are preserved. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once dirname( __DIR__ ) . '/inc/presentation-patterns.php';
$id = (int) get_option( 'page_on_front' );
$home = get_post( $id );
if ( 74 !== $id || ! $home || 'Home' !== $home->post_title || 'publish' !== $home->post_status || 'page' !== get_option( 'show_on_front' ) ) {
	throw new RuntimeException( 'Expected published Home 74 as the static front page. No changes made.' );
}
if ( get_post_meta( $id, '_aspirecre_home_choreography', true ) ) { echo "Home 74 already updated; editor changes preserved.\n"; exit; }
$changed = 0;
$has_class = static fn( $block, $name ) => in_array( $name, explode( ' ', $block['attrs']['className'] ?? '' ), true );
$walk = function ( $blocks ) use ( &$walk, &$changed, $has_class ) {
	foreach ( $blocks as &$block ) {
		if ( $has_class( $block, 'hp-journey-intro' ) ) {
			$selector = null;
			foreach ( $block['innerBlocks'] as $child ) { if ( $has_class( $child, 'hp-objectives' ) ) { $selector = $child; } }
			if ( ! $selector ) { throw new RuntimeException( 'Existing objective selector missing.' ); }
			$block = parse_blocks( aspire_hp_group( aspire_hp_h( 'Your real estate journey', 2, 'hp-journey-heading' ) . serialize_block( $selector ), 'hp-journey-intro hp-shell' ) )[0];
			$changed++;
		} elseif ( $has_class( $block, 'hp-team-section' ) ) {
			$transition = null; $intro = null; $rest = array();
			foreach ( $block['innerBlocks'] as $child ) {
				if ( $has_class( $child, 'hp-human-transition' ) ) { $transition = serialize_blocks( $child['innerBlocks'] ); }
				elseif ( $has_class( $child, 'hp-section-intro' ) ) { $intro = serialize_blocks( $child['innerBlocks'] ); }
				else { $rest[] = $child; }
			}
			if ( null === $transition || null === $intro ) { throw new RuntimeException( 'Expected existing Team introduction and transition.' ); }
			$block = parse_blocks( aspire_hp_group( aspire_hp_group( $transition . $intro, 'hp-shell hp-section-intro' ) . serialize_blocks( $rest ), 'hp-section hp-team-section', 'human-expertise', 'section', 'Real Aspire people' ) )[0];
			$changed++;
		} else { $block['innerBlocks'] = $walk( $block['innerBlocks'] ); }
	}
	return $blocks;
};
$content = serialize_blocks( $walk( parse_blocks( $home->post_content ) ) );
if ( 2 !== $changed || ! has_block( 'aspire/atlas', $content ) ) { throw new RuntimeException( 'Unexpected homepage structure; no changes made.' ); }
add_post_meta( $id, '_aspirecre_before_home_choreography', wp_slash( $home->post_content ), true );
wp_save_post_revision( $id );
$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
update_post_meta( $id, '_aspirecre_home_choreography', 1 );
echo "Home 74: removed redundant journey introduction and integrated Team entrance. All stage content preserved.\n";
