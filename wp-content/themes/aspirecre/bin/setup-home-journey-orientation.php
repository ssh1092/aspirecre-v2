<?php
/** One-time, bounded replacement of Home's native journey introduction only. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
function aspire_orientation_range( string $content ): array {
 preg_match_all( '/<!--\s*(\/?)wp:group\b.*?-->/s', $content, $tokens, PREG_OFFSET_CAPTURE );
 $start = null; $depth = 0; $ranges = array();
 foreach ( $tokens[0] as $i => $token ) {
  $closing = '/' === $tokens[1][$i][0];
  if ( null === $start && ! $closing && str_contains( $token[0], 'hp-journey-intro hp-shell' ) ) { $start = $token[1]; $depth = 0; }
  if ( null === $start ) { continue; }
  $depth += $closing ? -1 : 1;
  if ( 0 === $depth ) { $ranges[] = array( $start, $token[1] + strlen( $token[0] ) - $start ); $start = null; }
 }
 if ( 1 !== count( $ranges ) ) { throw new RuntimeException( 'Expected exactly one native journey introduction.' ); }
 return $ranges[0];
}
$home = get_post( 74 );
if ( ! $home || 'Home' !== $home->post_title || 'publish' !== $home->post_status || 74 !== (int) get_option( 'page_on_front' ) || 'page' !== get_option( 'show_on_front' ) ) { throw new RuntimeException( 'Expected published static Home 74.' ); }
if ( get_post_meta( 74, '_aspirecre_journey_orientation', true ) ) { echo "Already applied; editor changes preserved.\n"; exit; }
ob_start(); require get_theme_file_path( '/patterns/presentation-journey.php' ); $pattern = ob_get_clean();
list( $start, $length ) = aspire_orientation_range( $home->post_content );
list( $new_start, $new_length ) = aspire_orientation_range( $pattern );
$replacement = substr( $pattern, $new_start, $new_length );
$content = substr_replace( $home->post_content, $replacement, $start, $length );
if ( substr_replace( $content, '', $start, strlen( $replacement ) ) !== substr_replace( $home->post_content, '', $start, $length ) ) { throw new RuntimeException( 'Out-of-scope content change.' ); }
add_post_meta( 74, '_aspirecre_before_journey_orientation', wp_slash( $home->post_content ), true );
wp_save_post_revision( 74 );
$result = wp_update_post( array( 'ID' => 74, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
update_post_meta( 74, '_aspirecre_journey_orientation', 1 );
echo "Updated only the native journey introduction. Hero, all stages and remaining Page content are byte-for-byte unchanged.\n";
