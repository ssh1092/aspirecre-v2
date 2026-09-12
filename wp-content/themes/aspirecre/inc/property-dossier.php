<?php
/** Theme assets; Core owns the read-only dossier model. */
defined('ABSPATH') || exit;
class_alias('Aspire_Property_Dossier','AspireCRE_Property_Dossier');
add_action('wp_enqueue_scripts',static function(){
 if(!is_singular('property'))return;
 wp_enqueue_style('aspire-home',get_stylesheet_uri(),array(),(string)filemtime(get_stylesheet_directory().'/style.css'));
 wp_enqueue_style('aspire-property-dossier',get_theme_file_uri('/assets/property-dossier.css'),array('aspire-home'),(string)filemtime(get_theme_file_path('/assets/property-dossier.css')));
 foreach(array('aspire-navigation'=>'navigation.js','aspire-property-dossier'=>'property-dossier.js') as $handle=>$file)wp_enqueue_script($handle,get_theme_file_uri('/assets/js/'.$file),array(),(string)filemtime(get_theme_file_path('/assets/js/'.$file)),array('in_footer'=>true,'strategy'=>'defer'));
});
