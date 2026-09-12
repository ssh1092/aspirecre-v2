<?php
/**
 * Title: Aspire — The next conversation
 * Slug: aspirecre/presentation-contact
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
$main = aspire_hp_p( '06 / Let’s talk', 'hp-kicker' ) . aspire_hp_h( 'What’s Your Next Real Estate Move?' ) . aspire_hp_p( 'Whether you’re searching for space, evaluating an investment, positioning a property or managing an asset, start with a conversation.', 'hp-lead' ) . aspire_hp_link( 'Talk to Aspire ↗', 'tel:+17139332001', 'hp-final-action' );
$contact = aspire_hp_link( '713-933-2001', 'tel:+17139332001', 'hp-phone' ) . aspire_hp_p( '10777 Westheimer Road, Suite 800<br>Houston, TX 77042', 'hp-address' );
// The verified live site's existing subscription form handles subscriptions.
$updates = aspire_hp_p( 'New Listings &amp; Market Updates', 'hp-updates-title' ) . aspire_hp_link( 'Sign up on Aspire’s current website ↗', 'https://www.aspirecre.com/#block-e9911cfedbdd720b2af1', 'hp-updates-link' );
echo aspire_hp_group( aspire_hp_group( $main, 'hp-final-main' ) . aspire_hp_group( $contact . aspire_hp_group( $updates, 'hp-updates' ), 'hp-final-details' ), 'hp-section hp-contact-section hp-shell', 'talk-to-aspire', 'section', 'Your next conversation' );
