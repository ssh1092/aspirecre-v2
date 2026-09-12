<?php
/** Explicit Home-only presentation upgrade. Re-run preserves subsequent editorial changes. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$id = (int) get_option( 'page_on_front' );
$home = get_post( $id );
if ( 'page' !== get_option( 'show_on_front' ) || ! $home || 'page' !== $home->post_type || 'Home' !== $home->post_title || 'publish' !== $home->post_status ) { throw new RuntimeException( 'Expected the existing published Home front page. No changes made.' ); }
if ( get_post_meta( $id, '_aspirecre_home_presentation', true ) ) { echo "Home $id already uses the presentation composition; editor changes preserved.\n"; exit; }
$atlas = null;
foreach ( parse_blocks( $home->post_content ) as $block ) { if ( 'aspire/atlas' === $block['blockName'] ) { $atlas = $block; break; } }
if ( ! $atlas ) { throw new RuntimeException( 'Existing Atlas block missing. No changes made.' ); }
$atlas['attrs']['corporateHero'] = true;
$atlas['attrs']['headline'] = 'Houston Commercial Real Estate, Made Clear.';
$atlas['attrs']['continueLabel'] = 'Your next move, from here';
$atlas['attrs']['continueTarget'] = 'client-journey';
$content = serialize_block( $atlas ) . "\n\n";
foreach ( array( 'journey', 'opportunities', 'types', 'team', 'field', 'contact' ) as $section ) {
	ob_start(); include dirname( __DIR__ ) . '/patterns/presentation-' . $section . '.php'; $content .= trim( ob_get_clean() ) . "\n\n";
}
$blocks = array_values( array_filter( parse_blocks( $content ), static fn( $block ) => null !== $block['blockName'] ) );
if ( count( $blocks ) !== 7 ) { throw new RuntimeException( 'Expected Atlas and six homepage compositions.' ); }
add_post_meta( $id, '_aspirecre_before_home_presentation', wp_slash( $home->post_content ), true );
wp_save_post_revision( $id );
$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( trim( $content ) ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
update_post_meta( $id, '_aspirecre_home_presentation', 1 );
echo "Home $id updated: Atlas + six native, editable presentation sections.\n";
