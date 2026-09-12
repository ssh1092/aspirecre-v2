<?php
/** Native block patterns and read-only dynamic shortcodes. */
defined( 'ABSPATH' ) || exit;
add_action( 'init', static function (): void {
	register_block_pattern_category( 'aspirecre', array( 'label' => 'AspireCRE' ) );
	foreach ( array( 'aspire_property_finder' => 'finder', 'aspire_featured_properties' => 'properties', 'aspire_team_members' => 'team' ) as $shortcode => $part ) {
		add_shortcode( $shortcode, static function () use ( $part ): string {
			ob_start();
			get_template_part( 'template-parts/home/' . $part );
			return ob_get_clean();
		} );
	}
} );
add_action( 'after_setup_theme', static function (): void {
	add_editor_style( array( 'style.css', 'assets/editor.css', 'assets/corporate.css', 'assets/presentation.css' ) );
} );

// Below-fold corporate photography remains a fully editable native Image block.
add_filter( 'render_block_core/image', static function ( string $content, array $block ): string {
	$classes = explode( ' ', $block['attrs']['className'] ?? '' );
	if ( ! array_intersect( array( 'corp-houston-image', 'corp-management-image', 'hp-native-photo' ), $classes ) ) { return $content; }
	$html = new WP_HTML_Tag_Processor( $content );
	if ( $html->next_tag( 'IMG' ) ) {
		$html->set_attribute( 'loading', 'lazy' );
		$html->set_attribute( 'decoding', 'async' );
		$html->set_attribute( 'sizes', in_array( 'hp-native-photo', $classes, true ) ? '(max-width: 767px) 100vw, 65vw' : '(max-width: 700px) calc(100vw - 40px), (max-width: 1440px) 42vw, 604px' );
	}
	return $html->get_updated_html();
}, 10, 2 );
