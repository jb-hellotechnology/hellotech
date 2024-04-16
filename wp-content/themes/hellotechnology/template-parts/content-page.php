<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package hellotechnology
 */

?>

		<?php
		global $post;
		$post_slug = $post->post_name;
		
		if ( $post_slug == 'contact' ) :
			echo '
	            <div class="content hero">
	            	<div class="restrict">
		            	<div class="textarea flow bg-no-background">
		            		<h1>Contact Hello Technology to discuss your project.</h1>
		            		<h2>Want to talk about websites, IT systems, marketing, software, graphics or something else? Get in touch!</h2>
		            	</div>
		            </div>
	            </div>
	            <div class="content form bg-no-background">
	            	<div class="restrict">
		            	<div class="textarea flow form">
						'; echo do_shortcode( '[contact-form-7 id="68f9a98" title="Contact Form"]' ); echo '
						</div>
	            	</div>
	            </div>';
		endif;

		// Check value exists.
		if( have_rows('page_content') ):
		
		    // Loop through rows.
		    while ( have_rows('page_content') ) : the_row();
		
		        // Case: Hero.
		        if( get_row_layout() == 'hero' ):
		        	$color = get_sub_field('color');
		            $heading = get_sub_field('heading');
		            $subheading = get_sub_field('subheading');
		            echo '
		            <div class="content hero">
		            	<div class="restrict">
			            	<div class="textarea flow bg-'.$color.'">
			            		<h1>'.$heading.'</h1>
			            		<h2>'.$subheading.'</h2>
			            	</div>
			            </div>
		            </div>';
		            
		        // Case: Textarea.
		        elseif( get_row_layout() == 'textarea' ):
		        	$textarea = get_sub_field('textarea');
		            echo '
		            <div class="content bg-no-background">
		            	<div class="restrict">
			            	<div class="textarea flow">
			            		'.$textarea.'
			            	</div>
			            </div>
		            </div>';
		
		        // Case: Highlight.
		        elseif( get_row_layout() == 'highlight' ):
		        	$background = get_sub_field('background');
		        	$color = get_sub_field('color');
		            $heading = get_sub_field('heading');
		            $subheading = get_sub_field('subheading');
		            $url = get_sub_field('url');
		            $button = get_sub_field('button_label');
		            echo '
		            <div class="content bg-'.$background.' margin-top">
		            	<div class="restrict">
			            	<div class="highlight">
			            		<div class="heading">
				            		<h2>'.$heading.'</h2>
			            		</div>
			            		<div class="callout bg-'.$color.'">
			            			<div>
			            				<img src="'; echo bloginfo('stylesheet_directory').'/images/bubble_1.png'; echo '" class="bubble" alt="Laptop" />
			            			</div>
			            			<div>
			            				<img src="'; echo bloginfo('stylesheet_directory').'/images/hello_technology_sand.svg'; echo '" alt="Hello" />
			            			</div>
				            		<p>'.$subheading.'</p>
				            		<div>
					            		<a href="'.$url.'" class="button bg-white">'.$button.'</a>
				            		</div>
				            	</div>
			            	</div>
			            </div>
		            </div>';
		            
		        // Case: Island.
		        elseif( get_row_layout() == 'island' ):
		            $heading = get_sub_field('heading');
		            $subheading = get_sub_field('subheading');
		            $logos = get_sub_field('logos');
		            if(get_sub_field('negative_margin')){
			        	$negative_margin = 'negative_margin';    
			        }
		            $background = get_sub_field('background');
		            $color = get_sub_field('color');
		            echo '
		            <div class="content bg-'.$background.'">
		            	<div class="restrict">
			            	<div class="island bg-'.$color.' '.$negative_margin.'">
			            		<div class="heading">
				            		<p><strong>'.$heading.'</strong></p>
				            		<ul class="logos">';
				            		foreach($logos as $logo){
					            		//print_r($key.'-'.$item);
					            		//echo $logo['alt'];
					            		//print_r($logo);
					            		echo "<li><img src=".$logo['logo']['sizes']['medium_large']." alt=".$logo['alt']." /></li>";
				            		}
				            		echo '</ul>
			            		</div>
			            		<div class="subheading">
				            		<img src="'; echo bloginfo('stylesheet_directory').'/images/bubble_1.png'; echo '" class="bubble" alt="Laptop" />
				            		<p>'.$subheading.'</p>
				            	</div>
			            	</div>
			            </div>
		            </div>';
		            
		        // Case: Blocks.
		        elseif( get_row_layout() == 'blocks' ):
		            if(get_sub_field('negative_margin')){
			        	$negative_margin = 'negative_margin';   
			        }
					// Check rows exists.
					if( have_rows('blocks') ):
						echo '
					<div class="content bg-no-background '.$negative_margin.'">
						<div class="restrict blocks">';
					    // Loop through rows.
					    while( have_rows('blocks') ) : the_row();
					
					        // Load sub field value.
					        $heading = get_sub_field('heading');
					        $subheading = get_sub_field('subheading');
					        $color = get_sub_field('color');
					        $image = get_sub_field('image');
					        echo '
					        <div class="block">
					        	<div class="text flow bg-'.$color.' '; if($image){ echo 'image'; } echo '">
					        		<h2>'.$heading.'</h2>
					        		<p>'.$subheading.'</p>
					        	</div>';
					        	if($image){
									echo '
					        	<div class="image">
					        		<img src="'.$image['sizes']['medium_large'].'" alt="" />';
									echo '
					        	</div>';
					        	}
					        echo '
					        </div>';
					
					    // End loop.
					    endwhile;
						echo '
						</div>
					</div>';
					// No value.
					else :
					    // Do something...
					endif;
					
				// Case: Blocks.
		        elseif( get_row_layout() == 'grid' ):
					// Check rows exists.
					if( have_rows('items') ):
						echo '
					<div class="content bg-no-background">
						<div class="restrict grid">';
					    // Loop through rows.
					    while( have_rows('items') ) : the_row();
					
					        // Load sub field value.
					        $icon = get_sub_field('icon');
					        $heading = get_sub_field('heading');
					        $text = get_sub_field('text');
					        echo '
				        	<div class="item flow">
				        		<i class="fa-solid '.$icon.' fa-2xl"></i>
				        		<h2>'.$heading.'</h2>
				        		<p>'.$text.'</p>
				        	</div>';
					
					    // End loop.
					    endwhile;
						echo '
						</div>
					</div>';
					// No value.
					else :
					    // Do something...
					endif;
		
		        endif;
		
		    // End loop.
		    endwhile;
		    
		    if ( is_front_page() ) :
			    echo '
			    <div class="content bg-white">
			    	<div class="restrict">
			    		<h2>Helpful Articles</h2>
				    	<ul class="posts">';
					    $args = array(
					        'posts_per_page' => 2, /* how many post you need to display */
					        'offset' => 0,
					        'orderby' => 'post_date',
					        'order' => 'DESC',
					        'post_type' => 'post', /* your post type name */
					        'post_status' => 'publish'
					    );
					    $query = new WP_Query($args);
					    if ($query->have_posts()) :
					        while ($query->have_posts()) : $query->the_post();
					            ?>
					            <li class="bg-yellow">
						            <a href="<?php the_permalink(); ?>" class="flow">
						            	<h2><?php the_title(); ?></h2>
										<p><?php the_excerpt(); ?></p>
						            </a>
					            </li>
					            <?php
					        endwhile;
					    endif;
					    wp_reset_postdata();
					    echo '
				    	</ul>
				    	<a class="button posts" href="/articles/">More articles</a>
			    	</div>
			    </div>';
		    endif;

		    
		    
		    if ( $post_slug == 'support' ) :
			    echo '
			    <div class="content bg-white">
			    	<div class="restrict">
				    	<ul class="posts">';
					    $args = array(
					        'posts_per_page' => 4, /* how many post you need to display */
					        'offset' => 0,
					        'orderby' => 'post_date',
					        'order' => 'DESC',
					        'post_type' => 'post', /* your post type name */
					        'post_status' => 'publish',
					        'cat' => 4,
					    );
					    $query = new WP_Query($args);
					    if ($query->have_posts()) :
					        while ($query->have_posts()) : $query->the_post();
					            ?>
					            <li class="bg-yellow">
						            <a href="<?php the_permalink(); ?>" class="flow">
						            	<h2><?php the_title(); ?></h2>
										<p><?php the_excerpt(); ?></p>
						            </a>
					            </li>
					            <?php
					        endwhile;
					    endif;
					    wp_reset_postdata();
					    echo '
				    	</ul>
				    	<a class="button posts" href="/articles/category/support/">Read more</a>
			    	</div>
			    </div>';
		    endif;
		
		// No value.
		else :
		    // Do something...
		endif;
		?>