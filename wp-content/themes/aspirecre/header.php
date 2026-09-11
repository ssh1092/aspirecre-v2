<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'aspire-site' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
	<div class="container header-inner">
		<?php aspirecre_brand(); ?>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" hidden>Menu <span aria-hidden="true">☰</span></button>
		<nav id="primary-navigation" class="primary-navigation" aria-label="Primary navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-links', 'depth' => 1, 'fallback_cb' => 'aspirecre_primary_menu' ) ); ?>
			<a class="button button-small" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact <span aria-hidden="true">↗</span></a>
		</nav>
	</div>
</header>
