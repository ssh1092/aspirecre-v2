<?php
/**
 * AspireCRE theme foundation.
 *
 * @package AspireCRE
 */

defined( 'ABSPATH' ) || exit;

add_action(
    'after_setup_theme',
    static function (): void {
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'editor-styles' );
        add_theme_support( 'wp-block-styles' );
    }
);
