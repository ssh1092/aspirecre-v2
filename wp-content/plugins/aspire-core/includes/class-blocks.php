<?php
/** First-class dynamic Gutenberg blocks. No new data definitions or writes. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/block-data.php';
final class Aspire_Core_Blocks {
	public static function register(): void {
		$base = dirname( ASPIRE_CORE_FILE );
		$asset = require $base . '/blocks/build/editor.asset.php';
		wp_register_script( 'aspire-core-block-editor', plugins_url( 'blocks/build/editor.js', ASPIRE_CORE_FILE ), $asset['dependencies'], $asset['version'], true );
		wp_register_style( 'aspire-core-block-editor', plugins_url( 'blocks/build/editor.css', ASPIRE_CORE_FILE ), array(), $asset['version'] );
		foreach ( array( 'property-finder' => 'finder', 'featured-properties' => 'properties', 'team-grid' => 'team', 'property-types' => 'types' ) as $slug => $part ) {
			register_block_type( $base . '/blocks/build/' . $slug, array( 'render_callback' => static function ( $attributes ) use ( $part ): string { return self::render( $part, $attributes ); } ) );
		}
	}
	public static function render( string $part, array $attributes = array(), ?bool $editor = null ): string {
		if ( ! in_array( $part, array( 'finder', 'properties', 'team', 'types' ), true ) ) { return ''; }
		$count = max( 1, min( 8, (int) ( $attributes['count'] ?? 4 ) ) );
		$editorial = 'editorial' === ( $attributes['presentation'] ?? '' );
		$portfolio = 'portfolio' === ( $attributes['presentation'] ?? '' );
		$portraits = 'portraits' === ( $attributes['presentation'] ?? '' );
		$hide_when_empty = ! empty( $attributes['hideWhenEmpty'] );
		$editor = $editor ?? ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) );
		ob_start();
		include dirname( ASPIRE_CORE_FILE ) . '/blocks/templates/' . $part . '.php';
		$content = ob_get_clean();
		if ( '' === trim( $content ) ) { return ''; }
		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'aspire-dynamic-block aspire-dynamic-' . $part ) ) . '>' . $content . '</div>';
	}
}
add_action( 'init', array( 'Aspire_Core_Blocks', 'register' ) );
add_filter( 'block_categories_all', static function ( $categories ) {
	if ( ! in_array( 'aspirecre', array_column( $categories, 'slug' ), true ) ) { array_unshift( $categories, array( 'slug' => 'aspirecre', 'title' => 'AspireCRE' ) ); }
	return $categories;
} );
