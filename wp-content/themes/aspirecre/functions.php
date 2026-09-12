<?php
/** AspireCRE theme setup. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/inc/homepage.php';
require_once __DIR__ . '/inc/customizer.php';
add_action( 'after_setup_theme', static function (): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'custom-logo', array( 'height' => 100, 'width' => 300, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => 'Primary navigation' ) );
	add_image_size( 'aspire-card', 800, 600, true );
	add_image_size( 'aspire-person', 640, 800, true );
	add_image_size( 'aspire-hero', 1600, 1400, true );
} );
add_action( 'wp_enqueue_scripts', static function (): void {
	// The existing non-homepage fallback stays independent of this implementation.
	if ( ! is_front_page() ) { return; }
	wp_enqueue_style( 'aspire-home', get_stylesheet_uri(), array(), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
	wp_enqueue_script( 'aspire-navigation', get_theme_file_uri( '/assets/js/navigation.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/navigation.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/property-directory.php';
