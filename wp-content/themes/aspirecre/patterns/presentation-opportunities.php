<?php
/**
 * Title: Aspire — Property portfolio
 * Slug: aspirecre/presentation-opportunities
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
$intro = aspire_hp_p( '02 / Places with possibility', 'hp-kicker' ) . aspire_hp_h( 'Commercial Real Estate Opportunities Across Houston' ) . aspire_hp_p( 'Real places. Different possibilities. Explore current opportunities represented by Aspire Commercial.', 'hp-lead' ) . aspire_hp_link( 'View All Properties ↗', home_url( '/properties/' ) );
echo aspire_hp_group( aspire_hp_group( $intro, 'hp-shell hp-section-intro' ) . aspire_hp_block( 'aspire-core/featured-properties', array( 'count' => 4, 'presentation' => 'portfolio' ) ), 'hp-section hp-opportunities-section', 'current-opportunities', 'section', 'Current Houston opportunities' );
