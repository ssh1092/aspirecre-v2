<?php
/** Explicit, bounded Team import from a reviewed local manifest. No network or Property writes.
 * php import-verified-team.php /absolute/path/verified-team.json
 */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$path = realpath( $argv[1] ?? '' );
if ( ! $path || ! is_file( $path ) ) { throw new RuntimeException( 'Supply the reviewed Team manifest.' ); }
$manifest = json_decode( file_get_contents( $path ), true, 32, JSON_THROW_ON_ERROR );
$approved = array( '6284eb04949a8' => 'Brandon Avedikian', '6284eb04949fa' => 'Bradley Segreto', 'm9il0r0p' => 'D.A. Smith', 'lrfg8l41' => 'Alex Bibb' );
if ( 1 !== ( $manifest['schema_version'] ?? null ) || count( $manifest['records'] ?? array() ) !== 4 ) { throw new RuntimeException( 'Expected the four reviewed Team records.' ); }
$records = array();
foreach ( $manifest['records'] as $row ) {
	if ( ( $approved[ $row['source_id'] ?? '' ] ?? null ) !== ( $row['name'] ?? null ) || 'https://www.aspirecre.com/our-team' !== ( $row['source_page'] ?? '' ) || ! preg_match( '#^https://files\.elfsightcdn\.com/#', $row['source_image_url'] ?? '' ) ) { throw new RuntimeException( 'Unverified Team identity or source.' ); }
	$file = realpath( dirname( $path ) . '/' . basename( $row['image_file'] ?? '' ) );
	if ( ! $file || dirname( $file ) !== dirname( $path ) || ! hash_equals( $row['image_sha256'] ?? '', hash_file( 'sha256', $file ) ) || ! in_array( wp_get_image_mime( $file ), array( 'image/png', 'image/jpeg', 'image/webp' ), true ) ) { throw new RuntimeException( 'Portrait source integrity failed.' ); }
	if ( empty( $row['job_title'] ) || empty( $row['short_bio'] ) || isset( $records[ $row['source_id'] ] ) ) { throw new RuntimeException( 'Incomplete or duplicate Team source.' ); }
	$row['file'] = $file; $records[ $row['source_id'] ] = $row;
}
$created_posts = $created_media = $temporary = $report = array();
try {
	foreach ( $records as $row ) {
		$existing = get_posts( array( 'post_type' => 'team_member', 'post_status' => 'any', 'numberposts' => 2, 'meta_key' => '_aspire_team_source_id', 'meta_value' => $row['source_id'] ) );
		if ( count( $existing ) > 1 ) { throw new RuntimeException( 'Duplicate source identity; no automatic merge.' ); }
		if ( $existing ) { $report[] = array( 'name' => $row['name'], 'team_id' => $existing[0]->ID, 'attachment_id' => get_post_thumbnail_id( $existing[0] ), 'action' => 'preserved' ); continue; }
		$matching = get_posts( array( 'post_type' => 'team_member', 'post_status' => 'any', 'numberposts' => 1, 'title' => $row['name'] ) );
		if ( $matching ) { throw new RuntimeException( 'An existing Team member needs manual reconciliation; preserved.' ); }
		$editor = wp_get_image_editor( $row['file'] );
		if ( is_wp_error( $editor ) ) { throw new RuntimeException( 'Portrait could not be processed.' ); }
		$editor->set_quality( 84 ); $editor->resize( 1800, 1800, false );
		$tmp = wp_tempnam( 'aspire-team' ); $temporary[] = $tmp;
		$saved = $editor->save( $tmp . '.webp', 'image/webp' ); $temporary[] = $tmp . '.webp';
		if ( is_wp_error( $saved ) ) { throw new RuntimeException( 'Portrait WebP could not be generated.' ); }
		$attachment = media_handle_sideload( array( 'name' => sanitize_title( $row['name'] ) . '-aspire-portrait.webp', 'tmp_name' => $saved['path'] ), 0, $row['name'] . ' — Aspire Commercial portrait' );
		if ( is_wp_error( $attachment ) ) { throw new RuntimeException( 'Portrait attachment could not be created.' ); }
		$created_media[] = $attachment;
		$bio = sanitize_textarea_field( $row['short_bio'] );
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'team_member', 'post_status' => 'publish', 'post_title' => $row['name'], 'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $bio ) . '</p><!-- /wp:paragraph -->', 'menu_order' => (int) $row['menu_order'] ) ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Team record could not be created.' ); }
		$created_posts[] = $id;
		update_post_meta( $id, '_aspire_job_title', sanitize_text_field( $row['job_title'] ) );
		update_post_meta( $id, '_aspire_short_bio', $bio );
		update_post_meta( $id, '_aspire_team_source_id', $row['source_id'] );
		$provenance = array_intersect_key( $row, array_flip( array( 'source_id', 'source_page', 'source_widget', 'source_image_url', 'source_record_sha256', 'image_sha256' ) ) );
		$provenance['verified_at'] = $manifest['verified_at']; $provenance['imported_at'] = gmdate( 'c' ); $provenance['source_payload_sha256'] = $manifest['source_payload_sha256'];
		$provenance['bio_method'] = 'Concise factual paraphrase of the verified live biography; numerical performance claims omitted.';
		update_post_meta( $id, '_aspire_team_provenance', $provenance );
		update_post_meta( $attachment, '_aspire_team_provenance', $provenance );
		update_post_meta( $attachment, '_wp_attachment_image_alt', 'Portrait of ' . $row['name'] );
		set_post_thumbnail( $id, $attachment );
		$report[] = array( 'name' => $row['name'], 'team_id' => $id, 'attachment_id' => $attachment, 'action' => 'created', 'source' => $row['source_page'], 'image_source' => $row['source_image_url'] );
	}
} catch ( Throwable $error ) {
	foreach ( $created_posts as $id ) { wp_delete_post( $id, true ); }
	foreach ( $created_media as $id ) { wp_delete_attachment( $id, true ); }
	throw $error;
} finally {
	foreach ( $temporary as $file ) { if ( is_file( $file ) ) { unlink( $file ); } }
}
echo wp_json_encode( array( 'records' => $report, 'created_team_ids' => $created_posts, 'created_attachment_ids' => $created_media ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
