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
function aspirecre_featured_properties(): array {
	if ( ! post_type_exists( 'property' ) ) { return array(); }
	$selected = array();
	$available = array( 'relation' => 'OR', array( 'key' => '_aspire_listing_status', 'value' => 'available' ), array( 'key' => '_aspire_listing_status', 'compare' => 'NOT EXISTS' ) );
	$tiers = array( array( 'relation' => 'AND', $available, array( 'key' => '_aspire_featured_property', 'value' => '1' ) ), $available, array() );
	foreach ( $tiers as $meta_query ) {
		$args = array( 'post_type' => 'property', 'post_status' => 'publish', 'posts_per_page' => 4 - count( $selected ), 'post__not_in' => array_map( static fn( $post ) => $post->ID, $selected ), 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true, 'ignore_sticky_posts' => true );
		if ( $meta_query ) { $args['meta_query'] = $meta_query; }
		$selected = array_merge( $selected, ( new WP_Query( $args ) )->posts );
		if ( 4 === count( $selected ) ) { break; }
	}
	return $selected;
}
function aspirecre_team_members(): array {
	return post_type_exists( 'team_member' ) ? get_posts( array( 'post_type' => 'team_member', 'post_status' => 'publish', 'numberposts' => 4, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) : array();
}
function aspirecre_metric( int $id ): string {
	foreach ( array( 'available_sf' => ' SF available', 'lot_acres' => ' acres' ) as $key => $suffix ) {
		$value = get_post_meta( $id, '_aspire_' . $key, true );
		if ( is_numeric( $value ) && (float) $value > 0 ) { return number_format_i18n( (float) $value, min( 4, strlen( rtrim( explode( '.', (string) $value )[1] ?? '', '0' ) ) ) ) . $suffix; }
	}
	$display = get_post_meta( $id, '_aspire_price_display', true );
	if ( is_string( $display ) && '' !== trim( $display ) ) { return $display; }
	$price = get_post_meta( $id, '_aspire_sale_price', true );
	return is_numeric( $price ) && (float) $price > 0 ? '$' . number_format_i18n( (float) $price, (float) $price === floor( (float) $price ) ? 0 : 2 ) : '';
}
function aspirecre_term_names( int $id, string $taxonomy ): string {
	$terms = get_the_terms( $id, $taxonomy );
	return $terms && ! is_wp_error( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';
}
function aspirecre_taxonomy_options( string $taxonomy ): void {
	if ( ! taxonomy_exists( $taxonomy ) ) { return; }
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( is_wp_error( $terms ) ) { return; }
	$render = static function ( $parent, $depth ) use ( &$render, $terms ): void {
		foreach ( $terms as $term ) {
			if ( (int) $term->parent !== $parent ) { continue; }
			echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( str_repeat( '— ', $depth ) . $term->name ) . '</option>';
			$render( (int) $term->term_id, $depth + 1 );
		}
	};
	$render( 0, 0 );
}
