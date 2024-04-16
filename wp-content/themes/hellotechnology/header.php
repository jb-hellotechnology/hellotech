<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package hellotechnology
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<link rel="stylesheet" href="https://sibforms.com/forms/end-form/build/sib-styles.css">
	<link rel="stylesheet" href="https://use.typekit.net/pkb4lcb.css">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'hellotechnology' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="restrict">
			<div class="site-branding">
				<a href="/"><img src="<?php bloginfo('stylesheet_directory');?>/images/hello_technology_logo_green.svg" alt="Hello Technology" /></a>
			</div><!-- .site-branding -->
	
			<nav id="site-navigation" class="main-navigation">
				<button class="menu-toggle" aria-controls="main-menu" aria-expanded="false">Menu <i class="fa fa-bars"></i></button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'main-menu',
					)
				);
				?>
			</nav><!-- #site-navigation -->
			
			<div class="icons">
				<a href="mailto:hi@hellotechnology.co.uk"><i class="fa fa-envelope"></i><span>hi@hellotechnology.co.uk</span></a>
				<a href="tel:+44(0)1947878108"><i class="fa fa-phone"></i><span>01947 878 108</span></a>
				<a href="https://linkedin.com/company/hellotechnology/"><i class="fab fa-linkedin"></i><span class="alt">LinkedIn</span></a>
				<a href="https://instagram.com/_hellotechnology"><i class="fa fa-instagram"></i><span class="alt">Instagram</span></a> 
				<a href="https://twitter.com/hellotechnology"><i class="fab fa-x-twitter"></i><span class="alt">Twitter/X</span></a>
			</div>
		</div>
	</header><!-- #masthead -->
