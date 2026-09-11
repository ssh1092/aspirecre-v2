<?php
/**
 * Plugin Name: Aspire Core
 * Description: Property and team management for AspireCRE.
 * Version: 0.2.0
 * Requires PHP: 8.2
 * Text Domain: aspire-core
 */
defined( 'ABSPATH' ) || exit;
define( 'ASPIRE_CORE_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-content-types.php';
require_once __DIR__ . '/includes/class-fields.php';
require_once __DIR__ . '/includes/class-admin.php';
add_action( 'init', array( 'Aspire_Core_Content_Types', 'register' ) );
add_action( 'init', array( 'Aspire_Core_Fields', 'register' ) );
add_action( 'init', array( 'Aspire_Core_Content_Types', 'upgrade' ), 20 );
add_action( 'template_redirect', array( 'Aspire_Core_Content_Types', 'block_archives' ) );
Aspire_Core_Admin::init();
register_activation_hook( __FILE__, array( 'Aspire_Core_Content_Types', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Aspire_Core_Content_Types', 'deactivate' ) );
require_once __DIR__ . '/includes/class-blocks.php';
require_once __DIR__ . '/includes/class-atlas.php';
