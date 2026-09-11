<?php
/**
 * Minimal fallback template.
 *
 * @package AspireCRE
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main style="max-width:960px;margin:4rem auto;padding:0 1.5rem;font-family:system-ui,sans-serif;">
    <h1><?php bloginfo( 'name' ); ?></h1>
    <p>AspireCRE V2 local WordPress environment is running.</p>
</main>
<?php wp_footer(); ?>
</body>
</html>
