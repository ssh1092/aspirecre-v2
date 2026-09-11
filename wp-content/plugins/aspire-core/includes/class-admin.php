<?php
/** Native administration and guarded form persistence. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Core_Admin {
	public static function init() {
		// These data-heavy editors use WordPress's native classic meta-box layout.
		add_filter( 'use_block_editor_for_post_type', static function ( $use, $type ) {
			return in_array( $type, array( 'property', 'team_member' ), true ) ? false : $use;
		}, 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}
	public static function assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, array( 'property', 'team_member' ), true ) ) { return; }
		wp_enqueue_style( 'aspire-core-admin', plugins_url( 'assets/admin.css', ASPIRE_CORE_FILE ), array(), '0.2.0' );
		if ( 'property' === $screen->post_type ) {
			wp_enqueue_media();
			wp_enqueue_script( 'aspire-core-admin', plugins_url( 'assets/admin.js', ASPIRE_CORE_FILE ), array( 'jquery', 'media-views' ), '0.2.0', true );
		}
	}
	public static function boxes( $type ) {
		if ( ! in_array( $type, array( 'property', 'team_member' ), true ) ) { return; }
		foreach ( Aspire_Core_Fields::groups( $type ) as $title => $fields ) {
			add_meta_box( 'aspire-' . sanitize_title( $title ), $title, array( __CLASS__, 'fields_box' ), $type, 'normal', 'default', $fields );
		}
		if ( 'property' === $type ) {
			foreach ( array( 'suites' => 'Suite Availability', 'media' => 'Media', 'brokers' => 'Listing Brokers' ) as $key => $title ) {
				add_meta_box( 'aspire-' . $key, $title, array( __CLASS__, $key . '_box' ), $type, 'normal' );
			}
		}
	}
	public static function label( $name ) {
		return ucwords( str_replace( array( '_', 'sf', 'vpd', 'ft', 'url' ), array( ' ', 'SF', 'VPD', 'ft', 'URL' ), $name ) );
	}
	public static function control( $name, $kind, $value, $id ) {
		if ( is_array( $kind ) ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $kind as $option ) { echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( self::label( $option ) ) . '</option>'; }
			echo '</select>';
		} elseif ( 'textarea' === $kind ) {
			echo '<textarea class="widefat" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'boolean' === $kind ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $value, true, false ) . '>';
		} else {
			$numeric = in_array( $kind, array( 'integer', 'number', 'latitude', 'longitude' ), true );
			$attributes = '';
			if ( $numeric ) {
				$attributes = ' step="' . ( 'integer' === $kind ? '1' : 'any' ) . '"';
				$attributes .= 'latitude' === $kind ? ' min="-90" max="90"' : ( 'longitude' === $kind ? ' min="-180" max="180"' : ' min="0"' );
			}
			echo '<input class="widefat" type="' . esc_attr( $numeric ? 'number' : $kind ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $attributes . '>';
		}
	}
	public static function fields_box( $post, $box ) {
		$first = array_key_first( Aspire_Core_Fields::groups( $post->post_type ) );
		if ( $box['id'] === 'aspire-' . sanitize_title( $first ) ) { wp_nonce_field( 'aspire_save_' . $post->ID, 'aspire_nonce' ); }
		echo '<div class="aspire-fields">';
		foreach ( $box['args'] as $name => $kind ) {
			echo '<div><label for="aspire-' . esc_attr( $name ) . '">' . esc_html( self::label( $name ) ) . '</label>';
			self::control( 'aspire[' . $name . ']', $kind, get_post_meta( $post->ID, '_aspire_' . $name, true ), 'aspire-' . $name );
			echo '</div>';
		}
		echo '</div>';
	}
	public static function suite_row( $index, $suite ) {
		echo '<fieldset class="aspire-suite"><legend>Suite</legend><div class="aspire-fields">';
		foreach ( Aspire_Core_Fields::suites() as $name => $kind ) {
			$id = 'aspire-suite-' . $index . '-' . $name;
			echo '<div><label for="' . esc_attr( $id ) . '">' . esc_html( self::label( $name ) ) . '</label>';
			self::control( 'aspire[suites][' . $index . '][' . $name . ']', $kind, $suite[ $name ] ?? '', $id );
			echo '</div>';
		}
		echo '</div><p><button type="button" class="button aspire-remove-suite">Remove Suite</button></p></fieldset>';
	}
	public static function suites_box( $post ) {
		echo '<input type="hidden" name="aspire[suites_present]" value="1"><div id="aspire-suite-rows">';
		$suites = get_post_meta( $post->ID, '_aspire_suites', true );
		foreach ( is_array( $suites ) ? $suites : array() as $index => $suite ) { self::suite_row( $index, $suite ); }
		echo '</div><template id="aspire-suite-template">';
		self::suite_row( '__INDEX__', array() );
		echo '</template><button type="button" class="button" id="aspire-add-suite">Add Suite</button><p class="description">Changes are saved when you save or update this property.</p>';
	}
	public static function media_box( $post ) {
		foreach ( array( 'brochure_attachment_id' => 'Brochure PDF', 'gallery_attachment_ids' => 'Image Gallery' ) as $name => $label ) {
			$value = get_post_meta( $post->ID, '_aspire_' . $name, true );
			$ids = is_array( $value ) ? $value : array_filter( array( (int) $value ) );
			echo '<div class="aspire-media" data-kind="' . esc_attr( $name ) . '"><p><strong>' . esc_html( $label ) . '</strong></p><input type="hidden" name="aspire[' . esc_attr( $name ) . ']" value="' . esc_attr( implode( ',', $ids ) ) . '"><ul class="aspire-media-preview">';
			foreach ( $ids as $id ) { echo '<li>' . esc_html( get_the_title( $id ) ?: 'Attachment ' . $id ) . ' (#' . esc_html( $id ) . ')</li>'; }
			echo '</ul><button type="button" class="button aspire-select-media">Select ' . esc_html( $label ) . '</button> <button type="button" class="button aspire-clear-media">Clear</button></div>';
		}
	}
	public static function brokers_box( $post ) {
		$selected = array_map( 'intval', get_post_meta( $post->ID, '_aspire_listing_broker_id', false ) );
		$brokers = get_posts( array( 'post_type' => 'team_member', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<input type="hidden" name="aspire[brokers_present]" value="1"><label for="aspire-broker-filter">Filter team members</label><input type="search" id="aspire-broker-filter" class="widefat" autocomplete="off"><div class="aspire-brokers">';
		foreach ( $brokers as $broker ) {
			if ( ! current_user_can( 'read_post', $broker->ID ) ) { continue; }
			echo '<label><input type="checkbox" name="aspire[listing_broker_ids][]" value="' . esc_attr( $broker->ID ) . '" ' . checked( in_array( $broker->ID, $selected, true ), true, false ) . '> ' . esc_html( $broker->post_title ?: '(Untitled team member)' ) . ' <span class="description">(#' . esc_html( $broker->ID ) . ')</span></label>';
		}
		echo '</div><p class="description">Select any number of team members. Create team members first if none are listed.</p>';
	}
	public static function attachment_ids( $raw, $pdf = false ) {
		$ids = is_array( $raw ) ? $raw : ( is_scalar( $raw ) ? explode( ',', (string) $raw ) : array() );
		return array_values( array_unique( array_filter( array_map( static function ( $id ) use ( $pdf ) {
			$id = is_scalar( $id ) ? absint( $id ) : 0;
			return 'attachment' === get_post_type( $id ) && ( $pdf ? 'application/pdf' === get_post_mime_type( $id ) : wp_attachment_is_image( $id ) ) ? $id : 0;
		}, $ids ) ) ) );
	}
	public static function save( $id, $post ) {
		if ( ! in_array( $post->post_type, array( 'property', 'team_member' ), true ) || wp_is_post_autosave( $id ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
		if ( ! isset( $_POST['aspire_nonce'] ) || ! is_string( $_POST['aspire_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aspire_nonce'] ) ), 'aspire_save_' . $id ) || ! current_user_can( 'edit_post', $id ) ) { return; }
		if ( ! isset( $_POST['aspire'] ) || ! is_array( $_POST['aspire'] ) ) { return; }
		$data = wp_unslash( $_POST['aspire'] );
		foreach ( Aspire_Core_Fields::groups( $post->post_type ) as $fields ) {
			foreach ( $fields as $name => $kind ) {
				if ( ! array_key_exists( $name, $data ) ) { continue; }
				$value = Aspire_Core_Fields::clean( $data[ $name ], $kind );
				// Explicit empty state must remain empty; missing state alone defaults to TX.
				if ( '' === $value && 'state' !== $name ) { delete_post_meta( $id, '_aspire_' . $name ); } else { update_post_meta( $id, '_aspire_' . $name, wp_slash( $value ) ); }
			}
		}
		if ( 'property' !== $post->post_type ) { return; }
		if ( isset( $data['suites_present'] ) ) {
			$suites = array();
			foreach ( isset( $data['suites'] ) && is_array( $data['suites'] ) ? $data['suites'] : array() as $row ) {
				if ( ! is_array( $row ) ) { continue; }
				$suite = array();
				foreach ( Aspire_Core_Fields::suites() as $name => $kind ) { $suite[ $name ] = Aspire_Core_Fields::clean( $row[ $name ] ?? '', $kind ); }
				$suites[] = $suite;
			}
			update_post_meta( $id, '_aspire_suites', wp_slash( $suites ) );
		}
		foreach ( array( 'brochure_attachment_id', 'gallery_attachment_ids' ) as $name ) {
			if ( array_key_exists( $name, $data ) ) {
				$ids = self::attachment_ids( $data[ $name ], 'brochure_attachment_id' === $name );
				update_post_meta( $id, '_aspire_' . $name, 'brochure_attachment_id' === $name ? ( $ids[0] ?? 0 ) : $ids );
			}
		}
		if ( isset( $data['brokers_present'] ) ) {
			delete_post_meta( $id, '_aspire_listing_broker_id' );
			$ids = isset( $data['listing_broker_ids'] ) && is_array( $data['listing_broker_ids'] ) ? $data['listing_broker_ids'] : array();
			$ids = array_unique( array_map( static function ( $value ) { return is_scalar( $value ) ? absint( $value ) : 0; }, $ids ) );
			foreach ( $ids as $broker ) {
				if ( 'team_member' === get_post_type( $broker ) && ! in_array( get_post_status( $broker ), array( 'trash', 'auto-draft' ), true ) && current_user_can( 'read_post', $broker ) ) { add_post_meta( $id, '_aspire_listing_broker_id', $broker ); }
			}
		}
	}
}
