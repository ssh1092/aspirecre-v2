<?php
/**
 * Title: Aspire — Property territories
 * Slug: aspirecre/presentation-types
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
$intro = aspire_hp_p( '03 / A place for every ambition', 'hp-kicker' ) . aspire_hp_h( 'Explore Houston Commercial Properties by Type' );
echo aspire_hp_group( aspire_hp_group( $intro, 'hp-shell hp-section-intro' ) . aspire_hp_block( 'aspire-core/property-types', array() ), 'hp-section hp-types-section', 'property-types', 'section', 'Explore property types' );
