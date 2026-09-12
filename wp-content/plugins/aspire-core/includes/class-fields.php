<?php
/** Field definitions shared by registration, UI, and persistence. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Core_Fields {
	public static function groups( $type ) {
		if ( 'team_member' === $type ) {
			return array( 'Team Member Details' => array( 'job_title' => 'text', 'email' => 'email', 'phone' => 'text', 'linkedin_url' => 'url', 'license_number' => 'text', 'short_bio' => 'textarea' ) );
		}
		return array(
			'Property Details' => array( 'listing_status' => array( 'available', 'under_contract', 'leased', 'sold', 'off_market' ), 'featured_property' => 'boolean' ),
			'Address & Location' => array( 'address_line_1' => 'text', 'address_line_2' => 'text', 'city' => 'text', 'state' => 'text', 'postal_code' => 'text', 'latitude' => 'latitude', 'longitude' => 'longitude' ),
			'Size & Building Details' => array( 'building_sf' => 'number', 'available_sf' => 'number', 'minimum_available_sf' => 'number', 'maximum_contiguous_sf' => 'number', 'lot_acres' => 'number', 'year_built' => 'integer', 'renovated_year' => 'integer', 'stories' => 'integer', 'clear_height_ft' => 'number', 'parking_spaces' => 'integer', 'traffic_count_vpd' => 'integer', 'building_class' => 'text', 'parking_ratio' => 'text' ),
			'Pricing & Lease Information' => array( 'sale_price' => 'number', 'lease_rate_min' => 'number', 'lease_rate_max' => 'number', 'lease_rate_type' => 'text', 'lease_rate_display' => 'text', 'price_display' => 'text' ),
			'Property Content' => array( 'property_highlights' => 'textarea', 'amenities' => 'textarea', 'building_specifications' => 'textarea', 'demographic_notes' => 'textarea' ),
		);
	}
	public static function suites() {
		return array( 'suite_name' => 'text', 'square_feet' => 'number', 'rate' => 'number', 'rate_type' => 'text', 'former_use' => 'text', 'notes' => 'textarea', 'availability_status' => array( 'available', 'coming_soon', 'leased', 'unavailable' ) );
	}
	public static function clean( $value, $kind ) {
		if ( ! is_scalar( $value ) ) { return ''; }
		if ( is_array( $kind ) && '' === $value ) { return ''; }
		if ( is_array( $kind ) ) { return in_array( $value, $kind, true ) ? $value : $kind[0]; }
		if ( 'boolean' === $kind ) { return in_array( $value, array( true, 1, '1' ), true ); }
		if ( in_array( $kind, array( 'number', 'integer', 'latitude', 'longitude' ), true ) ) {
			if ( '' === trim( (string) $value ) || ! is_numeric( $value ) || ! is_finite( (float) $value ) ) { return ''; }
			$number = (float) $value;
			$limit = 'latitude' === $kind ? 90 : 180;
			if ( in_array( $kind, array( 'latitude', 'longitude' ), true ) ? abs( $number ) > $limit : $number < 0 ) { return ''; }
			if ( 'integer' === $kind && ( floor( $number ) !== $number || $number > PHP_INT_MAX ) ) { return ''; }
			return 'integer' === $kind ? (int) $number : $number;
		}
		if ( 'textarea' === $kind ) { return sanitize_textarea_field( $value ); }
		if ( 'email' === $kind ) { return sanitize_email( $value ); }
		if ( 'url' === $kind ) { return esc_url_raw( $value, array( 'http', 'https' ) ); }
		return sanitize_text_field( $value );
	}
	public static function register() {
		foreach ( array( 'property', 'team_member' ) as $type ) {
			foreach ( self::groups( $type ) as $fields ) {
				foreach ( $fields as $name => $kind ) {
					$args = array( 'single' => true, 'type' => is_array( $kind ) ? 'string' : ( in_array( $kind, array( 'number', 'integer', 'boolean' ), true ) ? $kind : ( in_array( $kind, array( 'latitude', 'longitude' ), true ) ? 'number' : 'string' ) ), 'show_in_rest' => false,
						'sanitize_callback' => static function ( $value ) use ( $kind ) { return self::clean( $value, $kind ); },
						'auth_callback' => static function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); },
					);
					if ( 'state' === $name ) { $args['default'] = 'TX'; }
					if ( 'listing_status' === $name ) { $args['default'] = 'available'; }
					register_post_meta( $type, '_aspire_' . $name, $args );
				}
			}
		}
		foreach ( array( 'suites' => 'array', 'gallery_attachment_ids' => 'array', 'brochure_attachment_id' => 'integer', 'listing_broker_id' => 'integer' ) as $name => $type ) {
			register_post_meta( 'property', '_aspire_' . $name, array( 'type' => $type, 'single' => 'listing_broker_id' !== $name, 'show_in_rest' => false, 'auth_callback' => static function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); } ) );
		}
	}
}
