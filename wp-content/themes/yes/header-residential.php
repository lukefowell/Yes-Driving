<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta http-equiv="content-type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title(' | ', true, 'right'); ?><?php bloginfo('name'); ?></title>
<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" />
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="wrapper" class="hfeed">
<header>
<div id="branding">

<div id="blog-title">
		<?php if ( is_singular() ) {} else {echo '<h1>';} ?>
        	<a href="<?php echo home_url() ?>/" title="<?php bloginfo( 'name' ) ?>" rel="home" title="<?php bloginfo( 'name' ) ?>">
        		<img src="<?php echo get_bloginfo('template_url'); ?>/images/logo.png" alt="<?php bloginfo( 'name' ) ?>" />
       		 </a>
		<?php if ( is_singular() ) {} else {echo '</h1>';} ?>
    </div>
</div>
<div id="top-right">
<?php //get_search_form(); ?>
	<img class="social" src="<?php echo get_bloginfo('template_url'); ?>/images/call-now.png" alt="<?php bloginfo( 'name' ) ?>" />
    <div class="social">
    <a href="" target="_blank"><img class="facebook" src="<?php echo get_bloginfo('template_url'); ?>/images/facebook.png" height="25" width="25" alt="<?php bloginfo( 'name' ) ?>" /></a>
    <a href="" target="_blank"><img class="twitter"  src="<?php echo get_bloginfo('template_url'); ?>/images/twitter.png" height="25" width="25" alt="<?php bloginfo( 'name' ) ?>" /></a>
    </div>
</div>
<nav>
<?php wp_nav_menu( array( 'theme_location' => 'main-menu' ) ); ?>
</nav>
</header>
<div id="container">
<?php wp_nav_menu( array( 'theme_location' => 'residential-menu', 'container_class' => 'residential-menu-container' ) ); ?>