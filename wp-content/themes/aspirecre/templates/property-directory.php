<?php
/** Template Name: Property Directory
 * Normal Gutenberg page rendering with the existing theme header and footer.
 */
defined('ABSPATH') || exit;
get_header(); ?>
<main id="main-content" class="property-directory-page">
<?php while(have_posts()):the_post();the_content();endwhile; ?>
</main>
<?php get_footer(); ?>
