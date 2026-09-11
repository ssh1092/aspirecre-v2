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
	add_editor_style( array( 'style.css', 'assets/editor.css' ) );
} );
