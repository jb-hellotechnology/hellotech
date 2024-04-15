<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package hellotechnology
 */

if ( is_singular() ) {
?>
<img src="/wp-content/themes/hellotechnology/images/blog.jpg" alt="Blog" class="attachment-large" />
<article id="post post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header>
		<div class="content hero">
			<div class="restrict">
				<div class="textarea flow bg-sand">
				<?php
				$date = get_the_date( 'd/m/Y' );
				$category = get_the_category();
				echo '<p class="date">'.$date.' &bull; <a href="/articles/category/'.$category[0]->slug.'/">'.$category[0]->name.'</a></p>';
				the_title( '<h1 class="entry-title">', '</h1>' );
				the_excerpt();
				?>
				</div>
			</div>
		</div>
	</header><!-- .entry-header -->

	<section class="bg-white">
		<div class="restrict">
			<div class="textarea flow">
			<?php
			the_content(
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'hellotechnology' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					wp_kses_post( get_the_title() )
				)
			);
	
			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'hellotechnology' ),
					'after'  => '</div>',
				)
			);
			?>
			</div>
			<footer class="entry-footer">
				<?php echo get_the_post_thumbnail( get_the_ID(), 'medium' ); ?>
				<?php hellotechnology_entry_footer(); ?>
				<span>Search</span>
				<?php echo get_search_form(); ?>
			</footer><!-- .entry-footer -->
		</div><!-- .entry-content -->
		
	</section>
</article><!-- #post-<?php the_ID(); ?> -->

<?php
}else{
?>

<article class="bg-yellow">
	<a href="<?php echo esc_url( get_permalink() )?>" rel="bookmark">
		<?php
			the_title( '<h2 class="entry-title">', '</h2>' );
			the_excerpt(); 
		?>
	</a>
</article>

<?php
}	