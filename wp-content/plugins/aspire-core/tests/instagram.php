<?php
/** Offline security/parser/cache tests; own options restored, no content or media writes. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks = 0;
function instagram_check( $condition, string $message ): void { global $checks; if ( ! $condition ) { throw new RuntimeException( $message ); } ++$checks; echo "PASS $message\n"; }
if ( Aspire_Instagram::configuration() ) { throw new RuntimeException( 'Run offline tests without configured production Instagram credentials.' ); }
$keys = array( Aspire_Instagram::LAST_GOOD, Aspire_Instagram::STATUS, '_transient_' . Aspire_Instagram::CACHE, '_transient_timeout_' . Aspire_Instagram::CACHE, '_transient_' . Aspire_Instagram::LOCK, '_transient_timeout_' . Aspire_Instagram::LOCK );
$before = array(); foreach ( $keys as $key ) { $before[ $key ] = get_option( $key, null ); }
$environment = array(); foreach ( array( 'ASPIRE_INSTAGRAM_ACCOUNT_ID', 'ASPIRE_INSTAGRAM_ACCESS_TOKEN', 'ASPIRE_INSTAGRAM_API_VERSION' ) as $name ) { $environment[ $name ] = getenv( $name ); }
$http = null; $original_user = get_current_user_id(); $die_handler = null;
try {
	instagram_check( WP_Block_Type_Registry::get_instance()->is_registered( 'aspire-core/field-feed' ), 'Native In the Field block registered' );
	instagram_check( ! Aspire_Instagram::configuration(), 'Missing credentials do not create an API connection' );
	instagram_check( is_wp_error( Aspire_Instagram::refresh() ), 'Unconfigured manual refresh fails safely' );
	$payload = array( 'data' => array(
		array( 'id' => '12345678', 'media_type' => 'IMAGE', 'media_url' => 'https://scontent.cdninstagram.com/photo.jpg?sig=valid', 'permalink' => 'https://www.instagram.com/p/Abc_123/?utm_source=x', 'caption' => '<script>bad()</script><b>Current property</b>', 'timestamp' => '2026-09-12T10:00:00+0000', 'access_token' => 'must-never-survive' ),
		array( 'id' => '22345678', 'media_type' => 'VIDEO', 'thumbnail_url' => 'https://scontent.fbcdn.net/poster.jpg', 'media_url' => 'https://scontent.fbcdn.net/huge-video.mp4', 'permalink' => 'https://www.instagram.com/reel/Reel123/', 'caption' => 'Site visit' ),
		array( 'id' => '32345678', 'media_type' => 'CAROUSEL_ALBUM', 'media_url' => 'https://scontent.cdninstagram.com/album.jpg', 'permalink' => 'https://www.instagram.com/p/Album123/' ),
	), 'paging' => array( 'next' => 'https://example.com/?access_token=must-never-survive' ) );
	$parsed = Aspire_Instagram::parse( $payload );
	instagram_check( is_array( $parsed ) && 3 === count( $parsed ), 'Image, video poster and carousel parsed' );
	instagram_check( ! str_contains( wp_json_encode( $parsed ), 'must-never-survive' ) && ! str_contains( wp_json_encode( $parsed ), 'huge-video' ), 'Raw secrets, pagination and video downloads omitted' );
	instagram_check( ! str_contains( $parsed[0]['caption'], '<' ) && ! str_contains( $parsed[0]['caption'], 'bad()' ), 'Captions strip executable markup' );
	instagram_check( 'https://www.instagram.com/p/Abc_123/' === $parsed[0]['permalink'], 'Instagram link tracking is discarded' );
	foreach ( array( 'http://scontent.cdninstagram.com/a.jpg', 'https://cdninstagram.com.attacker.test/a.jpg', 'https://127.0.0.1/a.jpg', 'https://user:pass@scontent.fbcdn.net/a.jpg', 'https://scontent.fbcdn.net:444/a.jpg', 'https://scontent.fbcdn.net/a.jpg?access_token=secret' ) as $url ) { instagram_check( '' === Aspire_Instagram::safe_url( $url, 'media' ), 'Unsafe media origin/credential URL rejected' ); }
	instagram_check( '' === Aspire_Instagram::safe_url( 'https://www.instagram.com.evil.test/p/abc/', 'permalink' ), 'Spoofed Instagram permalink rejected' );
	instagram_check( is_wp_error( Aspire_Instagram::parse( array( 'error' => array( 'message' => 'secret' ) ) ) ), 'API error is not a public feed' );
	instagram_check( is_wp_error( Aspire_Instagram::parse( array( 'data' => array( array( 'id' => '12345678', 'media_type' => 'VIDEO', 'media_url' => 'https://scontent.fbcdn.net/large.mp4', 'permalink' => 'https://www.instagram.com/reel/NoPoster/' ) ) ) ) ), 'Video without safe poster does not trigger video load' );
	instagram_check( array() === Aspire_Instagram::parse( array( 'data' => array() ) ), 'Genuine empty feed is distinct from malformed response' );
	putenv( 'ASPIRE_INSTAGRAM_ACCOUNT_ID=123456789012345' ); putenv( 'ASPIRE_INSTAGRAM_API_VERSION=v25.0' ); putenv( 'ASPIRE_INSTAGRAM_ACCESS_TOKEN=offline-test-token-never-public' );
	instagram_check( (bool) Aspire_Instagram::configuration(), 'Valid deployment settings accepted' );
	$mode = 'success'; $requests = 0;
	$http = static function ( $preempt, $args, $url ) use ( &$mode, &$requests, $payload ) {
		++$requests;
		instagram_check( str_starts_with( $url, 'https://graph.instagram.com/v25.0/123456789012345/media?' ) && ! str_contains( $url, 'offline-test-token' ), 'Fixed official API URL contains no access token' );
		instagram_check( 'Bearer offline-test-token-never-public' === $args['headers']['Authorization'] && 0 === $args['redirection'], 'Bearer token stays server-side and redirect forwarding is disabled' );
		if ( 'error' === $mode ) { return new WP_Error( 'http_error', 'Sensitive remote response offline-test-token-never-public' ); }
		return array( 'headers' => array(), 'body' => wp_json_encode( $payload ), 'response' => array( 'code' => 200, 'message' => 'OK' ) );
	};
	add_filter( 'pre_http_request', $http, 10, 3 );
	delete_transient( Aspire_Instagram::LOCK );
	$good = Aspire_Instagram::refresh(); instagram_check( ! is_wp_error( $good ) && 3 === count( $good['items'] ), 'Authorized server refresh stores sanitized feed' );
	instagram_check( 'instagram' === Aspire_Instagram::feed()['source'], 'Fresh cache used without HTTP' );
	$render = Aspire_Instagram::render();
	instagram_check( 1 === $requests && ! str_contains( $render, 'offline-test-token' ) && ! str_contains( $render, '<video' ), 'Rendering never fetches or exposes credentials/autoplay video' );
	delete_transient( Aspire_Instagram::CACHE );
	$mode = 'error'; instagram_check( is_wp_error( Aspire_Instagram::refresh() ), 'API failure is handled' );
	instagram_check( 'instagram' === Aspire_Instagram::feed()['source'] && get_option( Aspire_Instagram::LAST_GOOD ) === $good, 'Last successful feed survives failed refresh' );
	instagram_check( ! str_contains( wp_json_encode( get_option( Aspire_Instagram::STATUS ) ), 'offline-test-token' ), 'Failure status does not persist remote secrets' );
	$old = $good; $old['fetched_at'] = time() - Aspire_Instagram::MAX_STALE - 1; update_option( Aspire_Instagram::LAST_GOOD, $old, false );
	instagram_check( 'curated' === Aspire_Instagram::feed()['source'], 'Expired last-good feed returns honest local imagery' );
	update_option( Aspire_Instagram::LAST_GOOD, $good, false ); putenv( 'ASPIRE_INSTAGRAM_ACCOUNT_ID=999999999999999' );
	instagram_check( 'curated' === Aspire_Instagram::feed()['source'], 'Changed account cannot display previous account cache' );
	putenv( 'ASPIRE_INSTAGRAM_ACCOUNT_ID=123456789012345' ); set_transient( Aspire_Instagram::LOCK, 1, 60 ); $before_requests = $requests;
	instagram_check( is_wp_error( Aspire_Instagram::refresh() ) && $before_requests === $requests, 'Refresh lock prevents repeated request' );
	putenv( "ASPIRE_INSTAGRAM_ACCESS_TOKEN=token-with\nheader-injection" ); instagram_check( ! Aspire_Instagram::configuration(), 'Header injection in token rejected' );
	$curated = Aspire_Instagram::render(); instagram_check( str_contains( $curated, 'Curated photography' ) && ! str_contains( $curated, 'Sugarwell' ) && ! str_contains( $curated, 'Sienna Park' ) && ! str_contains( $curated, '3,410,000' ) && ! str_contains( $curated, '<time' ), 'Fallback contains real public inventory, no drafts/prices/fake dates' );
	$die_handler = static fn() => static function ( $message, $title = '', $args = array() ): void { throw new RuntimeException( 'guard_denied', (int) ( $args['response'] ?? 403 ) ); };
	add_filter( 'wp_die_handler', $die_handler );
	wp_set_current_user( 0 );
	try { Aspire_Instagram::manual_refresh(); throw new RuntimeException( 'Unauthenticated refresh was not rejected.' ); } catch ( RuntimeException $error ) { instagram_check( 'guard_denied' === $error->getMessage() && 403 === $error->getCode(), 'Unauthenticated refresh is denied before any API request' ); }
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	if ( $admins ) { wp_set_current_user( (int) $admins[0] ); try { Aspire_Instagram::manual_refresh(); throw new RuntimeException( 'Missing nonce was not rejected.' ); } catch ( RuntimeException $error ) { instagram_check( 'guard_denied' === $error->getMessage(), 'Administrator without valid nonce is denied' ); } }
} finally {
	if ( $die_handler ) { remove_filter( 'wp_die_handler', $die_handler ); }
	wp_set_current_user( $original_user );
	if ( $http ) { remove_filter( 'pre_http_request', $http, 10 ); }
	foreach ( $keys as $key ) { if ( null === $before[ $key ] ) { delete_option( $key ); } else { update_option( $key, $before[ $key ], false ); } }
	foreach ( $environment as $name => $value ) { putenv( false === $value ? $name : $name . '=' . $value ); }
}
echo "$checks Instagram checks passed; test options restored.\n";
