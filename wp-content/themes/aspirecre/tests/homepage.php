<?php
/** Local CLI checks; all fixture records are rolled back, never committed. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks = 0;
function aspirecre_check( bool $ok, string $label ): void {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	++$checks;
	echo "PASS: $label\n";
}
$ids = array();
$wpdb->query( 'START TRANSACTION' );
try {
	wp_set_current_user( 0 );
	aspirecre_check( str_ends_with( get_front_page_template(), '/front-page.php' ), 'WordPress resolves proper front-page template' );
	aspirecre_check( post_type_exists( 'property' ) && post_type_exists( 'team_member' ), 'Aspire Core CPTs remain registered' );
	ob_start(); get_template_part( 'template-parts/home/finder' ); $form = ob_get_clean();
	aspirecre_check( str_contains( $form, 'method="get"' ) && str_contains( $form, home_url( '/properties/' ) ), 'Finder GET contract' );
	aspirecre_check( str_contains( $form, 'office-condo' ) && str_contains( $form, 'for-sale-or-lease' ), 'Finder uses seeded taxonomy terms' );
	ob_start(); get_template_part( 'template-parts/home/properties' ); $empty = ob_get_clean();
	aspirecre_check( str_contains( $empty, 'Property listings will appear' ) && ! str_contains( $empty, 'Manage properties' ), 'Public property empty state hides admin controls' );
	ob_start(); get_template_part( 'template-parts/home/team' ); $empty = ob_get_clean();
	aspirecre_check( str_contains( $empty, 'Team profiles will appear' ) && ! str_contains( $empty, 'Manage team members' ), 'Public team empty state hides admin controls' );
	foreach ( array( array( 'available', 1, 'publish' ), array( 'available', 0, 'publish' ), array( 'sold', 1, 'publish' ), array( 'available', 1, 'draft' ), array( 'leased', 0, 'publish' ) ) as $i => $fixture ) {
		$id = wp_insert_post( array( 'post_type' => 'property', 'post_status' => $fixture[2], 'post_title' => 'Temporary property ' . $i, 'post_name' => 'temporary-home-test-' . $i ) );
		$ids[] = $id;
		update_post_meta( $id, '_aspire_listing_status', $fixture[0] );
		update_post_meta( $id, '_aspire_featured_property', $fixture[1] );
	}
	$properties = aspirecre_featured_properties();
	$selected = wp_list_pluck( $properties, 'ID' );
	aspirecre_check( 2 === count( $selected ) && 2 === count( array_unique( $selected ) ), 'Only available published properties selected' );
	aspirecre_check( $ids[0] === $selected[0] && $ids[1] === $selected[1], 'Featured available first, then available' );
	aspirecre_check( ! in_array( $ids[3], $selected, true ), 'Draft property excluded' );
	aspirecre_check( ! in_array( $ids[2], $selected, true ) && ! in_array( $ids[4], $selected, true ), 'Sold and leased properties excluded' );
	aspirecre_check( 1 === count( aspire_core_block_featured_properties( 1 ) ), 'Property count honored' );
	$id = $ids[0];
	update_post_meta( $id, '_aspire_available_sf', 1250.5 );
	update_post_meta( $id, '_aspire_lot_acres', 2.75 );
	update_post_meta( $id, '_aspire_price_display', 'Contact for pricing' );
	update_post_meta( $id, '_aspire_sale_price', 500000 );
	aspirecre_check( '1,250.5 SF available' === aspirecre_metric( $id ), 'Available SF has first priority' );
	delete_post_meta( $id, '_aspire_available_sf' );
	aspirecre_check( '2.75 acres' === aspirecre_metric( $id ), 'Lot acres fallback' );
	delete_post_meta( $id, '_aspire_lot_acres' );
	aspirecre_check( 'Contact for pricing' === aspirecre_metric( $id ), 'Price display fallback' );
	delete_post_meta( $id, '_aspire_price_display' );
	aspirecre_check( '$500,000' === aspirecre_metric( $id ), 'Sale price fallback' );
	delete_post_meta( $id, '_aspire_sale_price' );
	aspirecre_check( '' === aspirecre_metric( $id ), 'No invented metric for missing values' );
	update_post_meta( $id, '_aspire_city', 'Houston' );
	wp_set_object_terms( $id, 'office', 'property_type' );
	wp_set_object_terms( $id, 'for-lease', 'transaction_type' );
	ob_start(); get_template_part( 'template-parts/property-card', null, array( 'property' => get_post( $id ) ) ); $card = ob_get_clean();
	aspirecre_check( str_contains( $card, 'Houston, TX' ) && str_contains( $card, 'Office' ) && str_contains( $card, 'For Lease' ), 'Card uses real location/taxonomy keys' );
	aspirecre_check( 1 === substr_count( $card, '<a ' ) && str_contains( $card, 'aria-labelledby=' ) && str_contains( $card, '/properties/temporary-home-test-0/' ), 'Whole card has one named accessible single link' );
	foreach ( range( 0, 4 ) as $i ) {
		$member = wp_insert_post( array( 'post_type' => 'team_member', 'post_status' => 4 === $i ? 'draft' : 'publish', 'post_title' => 'Temporary member ' . $i, 'post_name' => 'temporary-member-' . $i ) );
		$ids[] = $member;
		update_post_meta( $member, '_aspire_job_title', 'Broker' );
	}
	aspirecre_check( 1 === count( aspire_core_block_team_members( 1 ) ), 'Team count honored' );
	aspirecre_check( 4 === count( aspirecre_team_members() ), 'Four published team members; draft excluded' );
	ob_start(); get_template_part( 'template-parts/home/team' ); $team = ob_get_clean();
	aspirecre_check( 4 === substr_count( $team, 'class="team-card"' ) && str_contains( $team, 'Broker' ) && str_contains( $team, '/team/temporary-member-' ), 'Team cards use correct job title and single URLs' );
	aspirecre_check( ! str_contains( $card . $team, 'Read More' ), 'Cards omit blog affordances' );
	echo "SUCCESS: $checks checks\n";
} finally {
	$wpdb->query( 'ROLLBACK' );
	foreach ( $ids as $id ) { clean_post_cache( $id ); }
	echo "Fixture transaction rolled back.\n";
}
