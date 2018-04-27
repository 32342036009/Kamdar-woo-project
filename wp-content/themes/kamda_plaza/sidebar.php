<?php
/**
 * The sidebar containing the secondary widget area
 *
 * Displays on posts and pages.
 *
 * If no active widgets are in this sidebar, hide it completely.
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */

?>
<?php if( is_front_page() ) {  ?>

	<section class="block5">
		<div id="carousel">    				
			<div class="container">
				<div class="row">
					<div class="col-md-12 ">
						<div class="block5-width">
							<h2>Client Testimonials</h2>

					<?php    $loop = new WP_Query( array( 'post_type' => 'testimonial' ) ); 
					if( $loop->have_posts() ){
                             	   while ( $loop->have_posts() ) { $loop->the_post();
                             	   	   $reviews[] = array( get_the_post_thumbnail() , get_the_content() , get_post_meta( get_the_ID(), '_sub_title', true));
                             	   }
                             }
                    ?>		
					
					<?php  if( !empty( $reviews )) {  ?>	

						<div class="carousel slide" id="fade-quote-carousel" data-ride="carousel" data-interval="3000">
								<!-- Carousel indicators -->
                            
								<ol class="carousel-indicators">
								<?php  $count=0; foreach ($reviews as $fetched_reviews) {
									
									 echo '<li data-target="#fade-quote-carousel" data-slide-to="'.$count.'"></li>';
									 $count++;
								}?>
									
								</ol>
								<!-- Carousel items -->
								<div class="carousel-inner">

									

									<?php  $count=0; foreach ($reviews as $fetched_reviews) {

									 $active_string = '';
									 if( $count == 0 )
									 $active_string = 'active';	
									 echo '<div class="item '.$active_string.'">

										<blockquote>
											<p>'.$fetched_reviews[1].'</p>
											<p class="author">'.$fetched_reviews[2].'</p>
										</blockquote>
									</div>
';
									 $count++;
								  }?>

										
								
								</div>
							</div>

						 <?php  } else{  ?>

						 	  <div class="alert alert-warning"> Reviews are not available. </div>

						 <?php  } ?>	

						</div>
					</div>							
				</div>
			</div>
		</div>
	</section>

	<?php } ?>