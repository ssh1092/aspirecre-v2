<?php
/** Official Instagram Login API, read only and server side. No request on page render. */
defined( 'ABSPATH' ) || exit;
final class Aspire_Instagram {
	const CACHE = 'aspire_instagram_feed';
	const LAST_GOOD = 'aspire_instagram_last_good';
	const STATUS = 'aspire_instagram_refresh_status';
	const LOCK = 'aspire_instagram_refresh_lock';
	const CRON = 'aspire_instagram_refresh';
	const TTL = 6 * HOUR_IN_SECONDS;
	const MAX_STALE = 7 * DAY_IN_SECONDS;

	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( self::CRON, array( self::class, 'refresh' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_aspire_instagram_refresh', array( self::class, 'manual_refresh' ) );
		register_deactivation_hook( ASPIRE_CORE_FILE, static function (): void { wp_clear_scheduled_hook( self::CRON ); } );
	}
	/** Values come from deployment secrets/constants, never options or public REST. */
	private static function setting( string $name ): string {
		$value = defined( $name ) ? constant( $name ) : getenv( $name );
		return is_string( $value ) ? trim( $value ) : '';
	}
	public static function configuration(): array {
		$account = self::setting( 'ASPIRE_INSTAGRAM_ACCOUNT_ID' );
		$token = self::setting( 'ASPIRE_INSTAGRAM_ACCESS_TOKEN' );
		$version = self::setting( 'ASPIRE_INSTAGRAM_API_VERSION' );
		if ( ! preg_match( '/^\d{5,30}$/', $account ) || ! preg_match( '/^v\d{2,3}\.0$/', $version ) || ! preg_match( '/^[\x21-\x7e]{20,4096}$/', $token ) ) { return array(); }
		return array( 'account' => $account, 'token' => $token, 'version' => $version );
	}
	public static function register(): void {
		$base = dirname( ASPIRE_CORE_FILE ) . '/social-feed';
		wp_register_script( 'aspire-social-editor', plugins_url( 'social-feed/editor.js', ASPIRE_CORE_FILE ), array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ), (string) filemtime( $base . '/editor.js' ), true );
		register_block_type( $base, array( 'render_callback' => array( self::class, 'render' ) ) );
		if ( self::configuration() && ! wp_next_scheduled( self::CRON ) ) { wp_schedule_event( time() + 60, 'twicedaily', self::CRON ); }
	}
	/** Only Instagram permalink and official CDN media URLs can enter a public feed. */
	public static function safe_url( $url, string $kind ): string {
		if ( ! is_string( $url ) || strlen( $url ) > 4096 ) { return ''; }
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ) { return ''; }
		$host = strtolower( $parts['host'] ?? '' );
		if ( 'permalink' === $kind ) {
			if ( ! in_array( $host, array( 'instagram.com', 'www.instagram.com' ), true ) || ! preg_match( '#^/(?:p|reel|tv)/[A-Za-z0-9_-]+/?$#', $parts['path'] ?? '' ) ) { return ''; }
			return 'https://www.instagram.com' . $parts['path'];
		}
		if ( ! preg_match( '/(?:^|\.)(?:cdninstagram\.com|fbcdn\.net)$/', $host ) ) { return ''; }
		parse_str( $parts['query'] ?? '', $query );
		if ( array_intersect( array( 'access_token', 'appsecret_proof', 'client_secret' ), array_map( 'strtolower', array_keys( $query ) ) ) ) { return ''; }
		return esc_url_raw( $url, array( 'https' ) );
	}
	/** Sanitize only known fields. Raw API responses and pagination tokens are discarded. */
	public static function parse( $body ) {
		if ( ! is_array( $body ) || isset( $body['error'] ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) { return new WP_Error( 'invalid_feed', 'Instagram did not return a valid feed.' ); }
		$items = array();
		foreach ( array_slice( $body['data'], 0, 24 ) as $row ) {
			if ( ! is_array( $row ) || ! preg_match( '/^\d{5,40}$/', (string) ( $row['id'] ?? '' ) ) || ! in_array( $row['media_type'] ?? '', array( 'IMAGE', 'VIDEO', 'CAROUSEL_ALBUM' ), true ) ) { continue; }
			$link = self::safe_url( $row['permalink'] ?? '', 'permalink' );
			$image = self::safe_url( ( 'VIDEO' === $row['media_type'] ? $row['thumbnail_url'] ?? '' : $row['media_url'] ?? '' ), 'media' );
			if ( ! $link || ! $image ) { continue; }
			$caption = is_string( $row['caption'] ?? null ) ? sanitize_textarea_field( $row['caption'] ) : '';
			$timestamp = is_string( $row['timestamp'] ?? null ) ? strtotime( $row['timestamp'] ) : false;
			$items[ $row['id'] ] = array( 'id' => (string) $row['id'], 'caption' => mb_substr( $caption, 0, 2200 ), 'media_type' => $row['media_type'], 'image_url' => $image, 'permalink' => $link, 'timestamp' => $timestamp ? gmdate( 'c', $timestamp ) : '' );
		}
		if ( $body['data'] && ! $items ) { return new WP_Error( 'invalid_media', 'Instagram returned no safe media items.' ); }
		return array_values( $items );
	}
	private static function account_key( array $config ): string { return hash( 'sha256', $config['account'] . '|' . $config['version'] ); }
	public static function refresh() {
		$config = self::configuration();
		if ( ! $config ) { return new WP_Error( 'not_configured', 'Instagram credentials are not configured.' ); }
		if ( get_transient( self::LOCK ) ) { return new WP_Error( 'refresh_busy', 'A refresh is already running.' ); }
		set_transient( self::LOCK, 1, 2 * MINUTE_IN_SECONDS );
		try {
			$url = add_query_arg( array( 'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp', 'limit' => 12 ), 'https://graph.instagram.com/' . $config['version'] . '/' . $config['account'] . '/media' );
			$response = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 2 * MB_IN_BYTES, 'headers' => array( 'Authorization' => 'Bearer ' . $config['token'], 'Accept' => 'application/json' ) ) );
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { throw new RuntimeException( 'api_unavailable' ); }
			$items = self::parse( json_decode( wp_remote_retrieve_body( $response ), true, 32 ) );
			if ( is_wp_error( $items ) ) { throw new RuntimeException( $items->get_error_code() ); }
			$feed = array( 'items' => $items, 'fetched_at' => time(), 'account_key' => self::account_key( $config ) );
			set_transient( self::CACHE, $feed, self::TTL );
			update_option( self::LAST_GOOD, $feed, false );
			update_option( self::STATUS, array( 'success' => true, 'checked_at' => time(), 'item_count' => count( $items ) ), false );
			return $feed;
		} catch ( Throwable $error ) {
			// Never log/store the response, exception or request: they may contain secrets.
			update_option( self::STATUS, array( 'success' => false, 'checked_at' => time(), 'message' => 'Refresh unavailable. The last successful feed or curated photography remains visible.' ), false );
			return new WP_Error( 'refresh_failed', 'Instagram refresh is unavailable.' );
		} finally { delete_transient( self::LOCK ); }
	}
	public static function feed(): array {
		$config = self::configuration();
		if ( $config ) {
			foreach ( array( get_transient( self::CACHE ), get_option( self::LAST_GOOD, array() ) ) as $feed ) {
				if ( is_array( $feed ) && hash_equals( self::account_key( $config ), (string) ( $feed['account_key'] ?? '' ) ) && ( $feed['fetched_at'] ?? 0 ) >= time() - self::MAX_STALE && ! empty( $feed['items'] ) ) { return array( 'source' => 'instagram', 'items' => $feed['items'] ); }
			}
		}
		return array( 'source' => 'curated', 'items' => self::curated() );
	}
	private static function curated(): array {
		$items = array();
		foreach ( aspire_core_block_featured_properties( 8 ) as $post ) {
			$attachment = get_post_thumbnail_id( $post );
			if ( ! $attachment || ! wp_attachment_is_image( $attachment ) ) { continue; }
			$items[] = array( 'id' => 'property-' . $post->ID, 'attachment_id' => $attachment, 'caption' => Aspire_Property_Dossier::data( $post->ID )['title'], 'media_type' => 'IMAGE', 'permalink' => get_permalink( $post ), 'timestamp' => '' );
		}
		return $items;
	}
	public static function render( array $attributes = array() ): string {
		$feed = self::feed(); $items = array_slice( $feed['items'], 0, max( 1, min( 12, (int) ( $attributes['count'] ?? 6 ) ) ) );
		$source = $feed['source'];
		ob_start(); include dirname( ASPIRE_CORE_FILE ) . '/social-feed/template.php'; return ob_get_clean();
	}
	public static function menu(): void { add_options_page( 'Aspire Instagram', 'Aspire Instagram', 'manage_options', 'aspire-instagram', array( self::class, 'settings_page' ) ); }
	public static function manual_refresh(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not permitted.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'aspire_instagram_refresh' );
		self::refresh(); wp_safe_redirect( admin_url( 'options-general.php?page=aspire-instagram' ) ); exit;
	}
	public static function settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$status = get_option( self::STATUS, array() );
		echo '<div class="wrap"><h1>Aspire Instagram</h1><p>Official Instagram API with Instagram Login. Read-only feed; no publishing, messages or comments.</p><p><strong>' . ( self::configuration() ? 'Server credentials configured.' : 'Instagram is not connected. Curated Aspire property photography is shown.' ) . '</strong></p><p>Configure ASPIRE_INSTAGRAM_ACCOUNT_ID, ASPIRE_INSTAGRAM_ACCESS_TOKEN and ASPIRE_INSTAGRAM_API_VERSION as protected server constants or environment secrets. Credentials are never displayed or stored in WordPress options.</p><p>Use a professional Instagram account, a Meta app with Instagram Login and the instagram_business_basic permission. Keep the long-lived token valid through your deployment secret-management process.</p><p>Scheduled refresh runs twice daily; successful responses are cached for six hours. The last successful feed can be used for up to seven days before the local fallback takes over.</p>';
		if ( $status ) { echo '<p>Last attempt: ' . esc_html( wp_date( 'Y-m-d H:i', $status['checked_at'] ?? 0 ) ) . ' — ' . esc_html( ! empty( $status['success'] ) ? 'Successful' : 'Unavailable; fallback preserved' ) . '</p>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="aspire_instagram_refresh">'; wp_nonce_field( 'aspire_instagram_refresh' ); submit_button( 'Refresh Instagram feed', 'primary', 'submit', true, self::configuration() ? array() : array( 'disabled' => 'disabled' ) ); echo '</form></div>';
	}
}
Aspire_Instagram::init();
