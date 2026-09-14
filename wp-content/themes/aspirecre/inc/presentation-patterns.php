<?php
/** Pattern-authoring helpers. These produce native blocks, saved as editable Page content. */
defined( 'ABSPATH' ) || exit;
function aspire_hp_block( string $name, array $attrs, string $html = '' ): string {
	$encoded = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	return $html ? "<!-- wp:$name$encoded -->\n$html\n<!-- /wp:$name -->\n" : "<!-- wp:$name$encoded /-->\n";
}
function aspire_hp_p( string $text, string $class = '' ): string {
	return aspire_hp_block( 'paragraph', $class ? array( 'className' => $class ) : array(), '<p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . $text . '</p>' );
}
function aspire_hp_h( string $text, int $level = 2, string $class = '' ): string {
	return aspire_hp_block( 'heading', array_filter( array( 'level' => $level, 'className' => $class ) ), '<h' . $level . ' class="wp-block-heading' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $text . '</h' . $level . '>' );
}
function aspire_hp_group( string $content, string $class, string $anchor = '', string $tag = 'div', string $name = '', string $align = '' ): string {
	$attrs = array( 'className' => $class, 'layout' => array( 'type' => 'default' ) );
	if ( $anchor ) { $attrs['anchor'] = $anchor; }
	if ( 'div' !== $tag ) { $attrs['tagName'] = $tag; }
	if ( $name ) { $attrs['metadata'] = array( 'name' => $name ); }
	if ( $align ) { $attrs['align'] = $align; }
	$wrapper_class = 'wp-block-group' . ( $align ? ' align' . $align : '' ) . ' ' . $class;
	return aspire_hp_block( 'group', $attrs, '<' . $tag . ( $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' ) . ' class="' . esc_attr( $wrapper_class ) . '">' . "\n$content</$tag>" );
}
function aspire_hp_image( int $id, string $class, string $alt ): string {
	if ( ! wp_attachment_is_image( $id ) ) { return ''; }
	return aspire_hp_block( 'image', array( 'id' => $id, 'sizeSlug' => 'full', 'linkDestination' => 'none', 'className' => $class ), '<figure class="wp-block-image size-full ' . esc_attr( $class ) . '"><img src="' . esc_url( wp_get_attachment_url( $id ) ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . $id . '"/></figure>' );
}
function aspire_hp_link( string $text, string $url, string $class = 'hp-link' ): string {
	return aspire_hp_p( '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>', $class );
}
