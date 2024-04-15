<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package hellotechnology
 */

?>

<article id="post-<?php the_ID(); ?>" class="bg-yellow">
	<a href="<?php echo esc_url( get_permalink() )?>" rel="bookmark">
		<h2><?php the_title(); ?></h2>
		<?php the_excerpt(); ?>
	</a>
</article>
