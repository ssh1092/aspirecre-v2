<?php
/**
 * Title: Aspire — In the field
 * Slug: aspirecre/presentation-field
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
$intro = aspire_hp_p( '05 / A closer look', 'hp-kicker' ) . aspire_hp_h( 'In the Field: Houston Commercial Real Estate' ) . aspire_hp_p( 'The places, people and details that make the work real.', 'hp-lead' );
echo aspire_hp_group( aspire_hp_group( $intro, 'hp-shell hp-section-intro' ) . aspire_hp_block( 'aspire-core/field-feed', array() ), 'hp-section hp-field-section', 'in-the-field', 'section', 'In the field' );
