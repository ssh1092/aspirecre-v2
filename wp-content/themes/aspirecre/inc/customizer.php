<?php
/** Owned media and verified contact/regulatory details; no invented defaults. */
defined( 'ABSPATH' ) || exit;
add_action( 'customize_register', static function ( $customizer ): void {
	$customizer->add_section( 'aspirecre_home', array( 'title' => 'Aspire Homepage', 'priority' => 35 ) );

	$fields = array( 'contact_email' => array( 'Contact email', 'email', 'sanitize_email' ), 'contact_phone' => array( 'Contact phone', 'text', 'sanitize_text_field' ), 'contact_address' => array( 'Office address', 'textarea', 'sanitize_textarea_field' ), 'trec_iabs_url' => array( 'TREC Information About Brokerage Services URL', 'url', 'esc_url_raw' ), 'trec_notice_url' => array( 'TREC Consumer Protection Notice URL', 'url', 'esc_url_raw' ), 'privacy_legal_url' => array( 'Privacy / Legal URL', 'url', 'esc_url_raw' ) );
	foreach ( $fields as $name => $field ) {
		$key = 'aspirecre_' . $name;
		$customizer->add_setting( $key, array( 'default' => '', 'sanitize_callback' => $field[2] ) );
		$customizer->add_control( $key, array( 'label' => $field[0], 'type' => $field[1], 'section' => 'aspirecre_home' ) );
	}
} );
