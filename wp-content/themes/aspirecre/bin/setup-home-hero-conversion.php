<?php
/** One-time update of the existing native Atlas hero attributes only. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$home = get_post( 74 );
if ( ! $home || 'publish' !== $home->post_status || 'Home' !== $home->post_title || 74 !== (int) get_option( 'page_on_front' ) || 'page' !== get_option( 'show_on_front' ) ) {
 throw new RuntimeException( 'Expected published Home 74 as static front page. No changes made.' );
}
if ( get_post_meta( 74, '_aspirecre_hero_conversion', true ) ) { echo "Home 74 already updated; editor changes preserved.\n"; exit; }
if ( 1 !== preg_match_all( '/<!-- wp:aspire\/atlas (\{.*?\}) \/-->/s', $home->post_content, $matches ) ) { throw new RuntimeException( 'Expected one native Atlas block.' ); }
$attrs = json_decode( $matches[1][0], true, 512, JSON_THROW_ON_ERROR );
if ( empty( $attrs['corporateHero'] ) ) { throw new RuntimeException( 'Expected the corporate hero.' ); }
$attrs['supportingText'] = 'Aspire helps tenants, property owners, investors and developers make better commercial real estate decisions across Greater Houston.';
$attrs['advisorPrompt'] = 'Prefer to talk first?';
$block = serialize_block( array( 'blockName'=>'aspire/atlas', 'attrs'=>$attrs, 'innerBlocks'=>array(), 'innerHTML'=>'', 'innerContent'=>array() ) );
$content = str_replace( $matches[0][0], $block, $home->post_content );
add_post_meta( 74, '_aspirecre_before_hero_conversion', wp_slash( $home->post_content ), true );
wp_save_post_revision( 74 );
$result = wp_update_post( array( 'ID'=>74, 'post_content'=>wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
update_post_meta( 74, '_aspirecre_hero_conversion', 1 );
echo "Home 74: company sentence and advisor prompt updated. All other blocks preserved byte-for-byte.\n";
