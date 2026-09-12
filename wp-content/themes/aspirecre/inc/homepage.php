<?php
/** Homepage presentation helpers; uses Aspire Core's storage contract. */
defined( 'ABSPATH' ) || exit;
function aspirecre_brand(): void {
	if ( has_custom_logo() ) { the_custom_logo(); return; }
	?><a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Aspire Commercial home"><span class="brand-name">aspire<span aria-hidden="true">.</span></span><span class="brand-subtitle">COMMERCIAL</span></a><?php
}
function aspirecre_primary_menu(): void {
	$items = array( 'Properties' => '/properties/', 'Services' => '/services/', 'About' => '/about/', 'Team' => '/our-team/', 'Insights' => '/insights/' );
	echo '<ul class="nav-links">';
	foreach ( $items as $label => $path ) { echo '<li><a href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $label ) . '</a></li>'; }
	echo '</ul>';
}

/** Compatibility helpers; data logic is owned by Aspire Core. */
function aspirecre_featured_properties(  ): array { return aspire_core_block_featured_properties(  ); }
function aspirecre_team_members(  ): array { return aspire_core_block_team_members(  ); }
function aspirecre_metric( int $id ): string { return aspire_core_block_metric( $id ); }
function aspirecre_term_names( int $id, string $taxonomy ): string { return aspire_core_block_term_names( $id, $taxonomy ); }
function aspirecre_taxonomy_options( string $taxonomy ): void { aspire_core_block_taxonomy_options( $taxonomy ); }

/** Real published destinations, with useful Home anchors until corporate pages exist.
 * Patterns save ordinary editable links; changing future destinations in existing
 * page content remains an editorial action (the setup script never overwrites edits).
 */
function aspirecre_home_destination( string $key ): string {
	$paths = array( 'properties' => 'properties', 'services' => 'services', 'about' => 'about', 'team' => 'our-team', 'insights' => 'insights', 'contact' => 'contact' );
	$services = array( 'tenant-representation', 'landlord-representation', 'investment-sales', 'investor-developer-services', 'property-management', 'cre-consulting' );
	foreach ( $services as $service ) { $paths[ $service ] = 'services/' . $service; }
	$page = isset( $paths[ $key ] ) ? get_page_by_path( $paths[ $key ], OBJECT, 'page' ) : null;
	if ( 'team' === $key && ( ! $page || 'publish' !== $page->post_status ) ) { $page = get_page_by_path( 'team', OBJECT, 'page' ); }
	if ( $page && 'publish' === $page->post_status ) { return get_permalink( $page ); }
	if ( 'contact' === $key ) { return 'tel:+17139332001'; }
	$anchors = array( 'services' => 'expertise', 'about' => 'human-expertise', 'team' => 'human-expertise', 'insights' => 'insights', 'atlas' => 'aspire-atlas' );
	return home_url( '/' ) . '#' . ( $anchors[ $key ] ?? ( in_array( $key, $services, true ) ? $key : 'talk-to-aspire' ) );
}

// Standard WordPress title-tag handling, without a second SEO metadata system.
add_filter( 'document_title_parts', static function ( array $parts ): array {
	return is_front_page() ? array( 'title' => 'Houston Commercial Real Estate | Aspire Commercial' ) : $parts;
} );
