<?php
/** Normal static-page rendering; homepage content is owned by Gutenberg. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main-content" class="aspire-page-content" tabindex="-1">
<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
</main>
<?php get_footer(); ?>
