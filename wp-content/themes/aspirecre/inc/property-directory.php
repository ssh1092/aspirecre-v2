<?php
/** Page presentation only; directory functionality belongs to Aspire Core. */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',static function(){
 if(!is_singular()||!has_block('aspire/property-directory',get_queried_object_id()))return;
 wp_enqueue_style('aspire-home',get_stylesheet_uri(),array(),(string)filemtime(get_stylesheet_directory().'/style.css'));
 wp_enqueue_style('aspire-directory-theme',get_theme_file_uri('/assets/property-directory.css'),array('aspire-home'),(string)filemtime(get_theme_file_path('/assets/property-directory.css')));
 wp_enqueue_script('aspire-navigation',get_theme_file_uri('/assets/js/navigation.js'),array(),(string)filemtime(get_theme_file_path('/assets/js/navigation.js')),array('in_footer'=>true,'strategy'=>'defer'));
});
add_action('after_setup_theme',static function(){add_editor_style('assets/property-directory.css');});
