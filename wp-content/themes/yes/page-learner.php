<?php
/*
*
* Template Name: Learner
*
*
*/
?>
<?php get_header('learner'); ?>
<article id="content">
<?php the_post(); ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<div class="entry-content">
<?php the_content(); ?>
<?php wp_link_pages('before=<div class="page-link">' . __( 'Pages:', 'blankslate' ) . '&after=</div>') ?>
</div>
</div>
</article>
<?php get_sidebar(); ?>
<?php get_footer('learner'); ?>