<?php
/** Content registration and one-time setup. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Core_Content_Types {
	public static function register() {
		foreach ( array( 'property' => array( 'Properties', 'Property', 'properties', 'dashicons-building' ), 'team_member' => array( 'Team Members', 'Team Member', 'team', 'dashicons-groups' ) ) as $type => $settings ) {
			register_post_type( $type, array(
				'labels' => array( 'name' => $settings[0], 'singular_name' => $settings[1], 'add_new_item' => 'Add ' . $settings[1], 'edit_item' => 'Edit ' . $settings[1] ),
				'public' => true, 'show_in_rest' => true, 'has_archive' => false,
				'rewrite' => array( 'slug' => $settings[2], 'with_front' => false ),
				'menu_icon' => $settings[3],
				'supports' => 'property' === $type ? array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ) : array( 'title', 'editor', 'thumbnail', 'revisions' ),
			) );
		}
		foreach ( array( 'property_type' => 'Property Types', 'transaction_type' => 'Transaction Types' ) as $taxonomy => $label ) {
			register_taxonomy( $taxonomy, 'property', array(
				'label' => $label, 'hierarchical' => true, 'public' => false, 'publicly_queryable' => false,
				'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => true,
				'rewrite' => false, 'query_var' => false, 'show_in_nav_menus' => false,
			) );
		}
	}
	/** Also reject query-string archives, which do not need registered rewrites. */
	public static function block_archives() {
		$taxonomies = array( 'property_type', 'transaction_type' );
		$types = (array) get_query_var( 'post_type' );
		// WordPress may discard a private taxonomy query var before this hook.
		$requested_taxonomy = isset( $_GET['taxonomy'] ) && is_string( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		if ( in_array( $requested_taxonomy, $taxonomies, true ) || is_tax( $taxonomies ) || in_array( get_query_var( 'taxonomy' ), $taxonomies, true ) || ( ! is_singular() && ! is_search() && array_intersect( $types, array( 'property', 'team_member' ) ) ) ) {
			global $wp_query;
			$wp_query->set_404();
			$wp_query->posts = array();
			$wp_query->post = null;
			$wp_query->post_count = 0;
			$wp_query->found_posts = 0;
			status_header( 404 );
			nocache_headers();
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}
	}
	public static function activate() {
		self::register();
		self::upgrade();
	}
	public static function upgrade() {
		if ( '0.2.0' === get_option( 'aspire_core_schema_version' ) ) { return; }
		$ok = true;
		foreach ( array( 'property_type' => array( 'Retail', 'Office', 'Industrial / Flex', 'Land' ), 'transaction_type' => array( 'For Lease', 'For Sale', 'For Sale or Lease', 'Ground Lease' ) ) as $taxonomy => $names ) {
			foreach ( $names as $name ) {
				if ( ! term_exists( $name, $taxonomy ) && is_wp_error( wp_insert_term( $name, $taxonomy ) ) ) { $ok = false; }
			}
		}
		$office = term_exists( 'Office', 'property_type' );
		if ( $office && ! term_exists( 'Office Condo', 'property_type' ) ) {
			$ok = ! is_wp_error( wp_insert_term( 'Office Condo', 'property_type', array( 'parent' => (int) $office['term_id'] ) ) ) && $ok;
		}
		if ( $ok ) {
			// Fresh installations use plain URLs; pretty singles require permalinks.
			// Preserve any existing nonempty permalink structure.
			if ( ! get_option( 'permalink_structure' ) ) {
				global $wp_rewrite;
				$wp_rewrite->set_permalink_structure( '/%postname%/' );
				self::register();
			}
			flush_rewrite_rules();
			update_option( 'aspire_core_schema_version', '0.2.0' );
		}
	}
	public static function deactivate() {
		unregister_post_type( 'property' );
		unregister_post_type( 'team_member' );
		unregister_taxonomy( 'property_type' );
		unregister_taxonomy( 'transaction_type' );
		delete_option( 'aspire_core_schema_version' );
		flush_rewrite_rules();
	}
}
