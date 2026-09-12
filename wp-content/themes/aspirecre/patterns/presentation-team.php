<?php
/**
 * Title: Aspire — People behind the process
 * Slug: aspirecre/presentation-team
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
$transition = aspire_hp_p( 'Technology can surface<br>the opportunity.<br><em>People help you decide<br>what to do with it.</em>', 'hp-human-statement' );
$intro = aspire_hp_p( '04 / The people behind the process', 'hp-kicker' ) . aspire_hp_h( 'Meet the Aspire Commercial Team' ) . aspire_hp_p( 'Houston-based commercial real estate brokerage, advisory and property management. Local knowledge, brought to your next decision.', 'hp-lead' );
echo aspire_hp_group( aspire_hp_group( $transition, 'hp-human-transition hp-shell' ) . aspire_hp_group( $intro, 'hp-shell hp-section-intro' ) . aspire_hp_block( 'aspire-core/team-grid', array( 'count' => 8, 'presentation' => 'portraits', 'hideWhenEmpty' => true ) ) . aspire_hp_group( aspire_hp_link( 'Start a conversation with Aspire ↗', 'tel:+17139332001' ), 'hp-shell' ), 'hp-section hp-team-section', 'human-expertise', 'section', 'Real Aspire people' );
