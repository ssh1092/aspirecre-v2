<?php
/** Run: docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/integration.php */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$ids = array();
$assertions = 0;
function aspire_test( $condition, $message ) {
	global $assertions;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$assertions;
	echo "PASS: $message\n";
}
function aspire_test_save( $id, $data, $nonce = true ) {
	$_POST = array( 'aspire' => wp_slash( $data ), 'aspire_nonce' => $nonce ? wp_create_nonce( 'aspire_save_' . $id ) : 'invalid' );
	Aspire_Core_Admin::save( $id, get_post( $id ) );
	$_POST = array();
}
try {
	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
	wp_set_current_user( $admin->ID );
	deactivate_plugins( 'aspire-core/aspire-core.php', false );
	$result = activate_plugin( 'aspire-core/aspire-core.php', '', false, false );
	aspire_test( ! is_wp_error( $result ) && is_plugin_active( 'aspire-core/aspire-core.php' ), 'Plugin deactivates and activates without fatal errors' );
	foreach ( array( 'property', 'team_member' ) as $type ) {
		$obj = get_post_type_object( $type );
		aspire_test( $obj && $obj->show_ui && ! $obj->has_archive, "$type admin registration and no public archive" );
		foreach ( array( 'title', 'editor', 'thumbnail', 'revisions' ) as $support ) { aspire_test( post_type_supports( $type, $support ), "$type supports $support" ); }
	}
	foreach ( array( 'property_type' => 5, 'transaction_type' => 4 ) as $taxonomy => $count ) {
		$obj = get_taxonomy( $taxonomy );
		aspire_test( $obj->show_ui && ! $obj->publicly_queryable && ! $obj->rewrite && ! $obj->query_var, "$taxonomy internal only" );
		aspire_test( count( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) ) >= $count, "$taxonomy terms seeded" );
	}
	$office = get_term_by( 'name', 'Office', 'property_type' );
	$condo = get_term_by( 'name', 'Office Condo', 'property_type' );
	aspire_test( (int) $condo->parent === $office->term_id, 'Office Condo is a child of Office' );
	foreach ( array( 'property', 'team_member', 'team_member' ) as $type ) {
		$ids[] = wp_insert_post( array( 'post_type' => $type, 'post_title' => 'Aspire temporary integration fixture', 'post_status' => 'publish', 'post_name' => 'aspire-test-' . wp_generate_uuid4() ) );
	}
	list( $property, $broker1, $broker2 ) = $ids;
	aspire_test( 'TX' === get_post_meta( $property, '_aspire_state', true ), 'Unsaved state defaults to TX' );
	aspire_test_save( $property, array( 'city' => 'Blocked' ), false );
	aspire_test( ! metadata_exists( 'post', $property, '_aspire_city' ), 'Invalid nonce blocks writes' );
	wp_set_current_user( 0 );
	aspire_test_save( $property, array( 'city' => 'Blocked' ) );
	aspire_test( ! metadata_exists( 'post', $property, '_aspire_city' ), 'Capability check blocks writes' );
	wp_set_current_user( $admin->ID );
	$revision = wp_insert_post( array( 'post_type' => 'revision', 'post_parent' => $property, 'post_status' => 'inherit', 'post_title' => 'Temporary revision' ) );
	$ids[] = $revision;
	aspire_test_save( $revision, array( 'city' => 'Blocked' ) );
	aspire_test( ! metadata_exists( 'post', $property, '_aspire_city' ), 'Revision saves do not overwrite property meta' );
	$data = array();
	foreach ( Aspire_Core_Fields::groups( 'property' ) as $fields ) {
		foreach ( $fields as $name => $kind ) { $data[ $name ] = is_array( $kind ) ? 'sold' : ( 'boolean' === $kind ? '1' : ( in_array( $kind, array( 'integer', 'number', 'latitude', 'longitude' ), true ) ? '12' : "O'Brien <b>value</b>\nline" ) ); }
	}
	$data['suites_present'] = '1';
	$data['suites'] = array( array( 'suite_name' => 'A', 'square_feet' => '1250.5', 'rate' => '22.75', 'notes' => "O'Brien\nline", 'availability_status' => 'coming_soon' ), array( 'suite_name' => 'B', 'availability_status' => 'available' ) );
	$data['brokers_present'] = '1';
	$data['listing_broker_ids'] = array( $broker1, $broker2, $broker1, $property );
	aspire_test_save( $property, $data );
	foreach ( Aspire_Core_Fields::groups( 'property' ) as $fields ) {
		foreach ( $fields as $name => $kind ) { aspire_test( (string) get_post_meta( $property, '_aspire_' . $name, true ) === (string) Aspire_Core_Fields::clean( $data[ $name ], $kind ), "$name saves sanitized value" ); }
	}
	$suites = get_post_meta( $property, '_aspire_suites', true );
	aspire_test( 2 === count( $suites ) && 1250.5 === $suites[0]['square_feet'] && "O'Brien\nline" === $suites[0]['notes'], 'Structured suites and multiline notes saved' );
	aspire_test( array( $broker1, $broker2 ) === array_map( 'intval', get_post_meta( $property, '_aspire_listing_broker_id' ) ), 'Multiple brokers deduplicated and wrong post type rejected' );
	$query = get_posts( array( 'post_type' => 'property', 'meta_key' => '_aspire_listing_broker_id', 'meta_value' => $broker2, 'fields' => 'ids' ) );
	aspire_test( in_array( $property, $query, true ), 'Properties queryable by one broker ID' );
	aspire_test_save( $property, array( 'suites_present' => 1, 'suites' => array( array( 'suite_name' => 'Edited', 'availability_status' => 'invalid', 'square_feet' => '-1' ) ) ) );
	$suites = get_post_meta( $property, '_aspire_suites', true );
	aspire_test( 1 === count( $suites ) && 'Edited' === $suites[0]['suite_name'] && 'available' === $suites[0]['availability_status'] && '' === $suites[0]['square_feet'], 'Suite edit, removal, and validation' );
	aspire_test_save( $property, array( 'suites_present' => 1, 'brokers_present' => 1, 'latitude' => '91', 'longitude' => '-181', 'year_built' => '2001.5', 'sale_price' => '-10', 'state' => '', 'listing_status' => 'invalid' ) );
	aspire_test( array() === get_post_meta( $property, '_aspire_suites', true ) && array() === get_post_meta( $property, '_aspire_listing_broker_id' ), 'Remove all suites and brokers' );
	foreach ( array( 'latitude', 'longitude', 'year_built', 'sale_price' ) as $name ) { aspire_test( ! metadata_exists( 'post', $property, '_aspire_' . $name ), "Invalid $name rejected" ); }
	aspire_test( '' === get_post_meta( $property, '_aspire_state', true ), 'Explicit empty state preserved' );
	aspire_test( 'available' === get_post_meta( $property, '_aspire_listing_status', true ), 'Invalid listing status normalized' );
	foreach ( array( 'application/pdf', 'image/png', 'image/jpeg' ) as $mime ) { $ids[] = wp_insert_attachment( array( 'post_title' => 'Temporary attachment fixture', 'post_mime_type' => $mime, 'post_status' => 'inherit' ) ); }
	list( $pdf, $png, $jpg ) = array_slice( $ids, -3 );
	update_post_meta( $png, '_wp_attached_file', 'aspire-test-' . wp_generate_uuid4() . '.png' );
	update_post_meta( $jpg, '_wp_attached_file', 'aspire-test-' . wp_generate_uuid4() . '.jpg' );
	aspire_test_save( $property, array( 'brochure_attachment_id' => "$png,$pdf", 'gallery_attachment_ids' => "$pdf,$png,$jpg,$png,$property" ) );
	aspire_test( $pdf === (int) get_post_meta( $property, '_aspire_brochure_attachment_id', true ), 'Brochure permits only PDF attachments' );
	aspire_test( array( $png, $jpg ) === get_post_meta( $property, '_aspire_gallery_attachment_ids', true ), 'Gallery stores multiple unique image IDs only' );
	aspire_test_save( $property, array( 'brochure_attachment_id' => '', 'gallery_attachment_ids' => '' ) );
	aspire_test( 0 === (int) get_post_meta( $property, '_aspire_brochure_attachment_id', true ) && array() === get_post_meta( $property, '_aspire_gallery_attachment_ids', true ), 'Media can be cleared' );
	$team_data = array( 'job_title' => '<b>Broker</b>', 'email' => 'broker@example.test', 'phone' => '+1 (555) 000-0000', 'linkedin_url' => 'https://www.linkedin.com/in/test', 'license_number' => '000123', 'short_bio' => "Short\nbio" );
	aspire_test_save( $broker1, $team_data );
	foreach ( Aspire_Core_Fields::groups( 'team_member' ) as $fields ) { foreach ( $fields as $name => $kind ) { aspire_test( Aspire_Core_Fields::clean( $team_data[ $name ], $kind ) === get_post_meta( $broker1, '_aspire_' . $name, true ), "Team $name saves" ); } }
	foreach ( array( 'property_type' => $condo->term_id, 'transaction_type' => get_term_by( 'name', 'For Lease', 'transaction_type' )->term_id ) as $taxonomy => $term ) { wp_set_object_terms( $property, array( $term ), $taxonomy ); aspire_test( has_term( $term, $taxonomy, $property ), "$taxonomy assignable" ); }
	foreach ( array( $property => 'properties', $broker1 => 'team' ) as $id => $prefix ) {
		$url = get_permalink( $id );
		aspire_test( str_contains( $url, '/' . $prefix . '/' ) && url_to_postid( $url ) === $id, "$prefix permalink and rewrite resolution" );
		$response = wp_remote_get( str_replace( 'localhost:8080', 'localhost', $url ), array( 'headers' => array( 'Host' => 'localhost:8080' ), 'redirection' => 0 ) );
		aspire_test( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), "$prefix single HTTP 200 using existing theme fallback" );
	}
	foreach ( array( '/property_type/office/', '/?taxonomy=property_type&term=office', '/?taxonomy=transaction_type&term=for-lease', '/?post_type=property', '/?post_type=team_member' ) as $path ) {
		$response = wp_remote_get( 'http://localhost' . $path, array( 'headers' => array( 'Host' => 'localhost:8080' ), 'redirection' => 0 ) );
		aspire_test( 404 === wp_remote_retrieve_response_code( $response ), "Public archive blocked: $path" );
	}
	define( 'DOING_AUTOSAVE', true );
	$before = get_post_meta( $property, '_aspire_city', true );
	aspire_test_save( $property, array( 'city' => 'Blocked autosave' ) );
	aspire_test( $before === get_post_meta( $property, '_aspire_city', true ), 'Autosave guard preserves metadata' );
	echo "SUCCESS: $assertions assertions\n";
} finally {
	$_POST = array();
	foreach ( array_reverse( $ids ) as $id ) { 'attachment' === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true ); }
	echo "Temporary records removed.\n";
}
