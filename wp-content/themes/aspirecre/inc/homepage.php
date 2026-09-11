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
