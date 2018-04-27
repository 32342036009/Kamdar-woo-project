<?php

/* Template Name: Home page template */

get_header(); ?>
<?php /* The loop */ ?>


<?php while ( have_posts() ) : the_post(); ?>

  <?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
    <?php the_post_thumbnail(); ?>
  <?php endif; ?>

  <?php the_content(); ?>

<?php endwhile; ?>


<section class="block4">
 <div class="block4-inner">
  <div class="container">

    <ul class="nav nav-tabs">
      <li class="active"><a data-toggle="tab" href="#home">Featured</a></li>

      <?php 

      $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        );

      $all_tax_for_tab_list = get_terms( $args );  
      $home_tab_array = array();
      foreach ($all_tax_for_tab_list as $alltaxfortablist) {

       $custom_options = get_option( "product_cat_".$alltaxfortablist->term_id);
       if( $custom_options['home_tab'] == true ){

         $home_tab_array[]  = $alltaxfortablist;
       }

     } 



     if( !empty( $home_tab_array )){

      $counter = 1;
      foreach ($home_tab_array as $hometabarray) {

       ?>  
       <li><a data-toggle="tab" data-tax-id="<?php echo $hometabarray->term_id ?>" href="#menu<?php echo $counter  ?>"> <?php echo $hometabarray->name ?> </a></li>

       <?php $counter++; } }  ?> 
     </ul>

     <div class="tab-content tab-content-sta">
      <div id="home" class="tab-pane fade in active">

        <div class="row">
          <?php 
          $args = array(  
           'post_type' => 'product',  
           'meta_key' => '_featured',  
           'meta_value' => 'yes',  

           );  


          $featured_query = new WP_Query( $args );  
          
          if ($featured_query->have_posts()) {

           while ($featured_query->have_posts()) {
             $featured_query->the_post();   

             ?>

             <div class="col-sm-3">
               <div class="porduct-details">
                 <?php  if( !$product->is_in_stock( ) ){ ?>

                   <div class="sold-out"></div>

                   <?php  } ?>
                   <div class="image-outer ">
                    <a href="<?php the_permalink() ?>">
                     <?php if( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } else{ 

                       echo '<img src="'.get_option('siteurl').'/wp-content/uploads/2017/02/PNG-Image-216-×-216-pixels.png">';
                     }    ?>
                   </a>  
                 </div>
                 <p><?php  the_title(); ?> <a href="#" class="f_c"><?php echo $product->get_price_html(); ?></a></p>
                 <p class="ratting"><a href="#" class="f_c">  <?php echo  $product->get_rating_html( ) ?></a></p>
               </div>
             </div>
                  <?php       }   wp_reset_postdata(); } else {   ?>

              <div class="porduct-details">    
                <div class="alert alert-warning">

                 Sorry, there is no featured product.

               </div>
             </div>    

             <?php } ?>  
             


           </div>


         </div>

         <?php  if( !empty($home_tab_array )) {

          $counter = 1;   foreach ($home_tab_array as $hometabarray) {

           ?>
           <div id="menu<?= $counter?>" class="tab-pane fade">
             <div class="row">

               <?php  

               $args = null;
               $args = array(
                 'posts_per_page' => -1,
                 'post_type' => 'product',
                 'orderby' => 'title',
                 'tax_query' => array(

                  array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'id',
                    'terms'     =>  array($hometabarray->term_id)


                    ))
                 );


               $the_query = new WP_Query($args);


               if ( $the_query->have_posts() ) {

                 while ( $the_query->have_posts() ) {
                   $the_query->the_post(); global $product;



                   ?>
                   <div class="col-sm-3">
                     <div class="porduct-details">
                       <?php  if( !$product->is_in_stock( ) ){ ?>

                         <div class="sold-out"></div>

                         <?php  } ?>
                         <div class="image-outer ">
                          <a href="<?php the_permalink() ?>">
                           <?php if( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } else{ 

                             echo '<img src="'.get_option('siteurl').'/wp-content/uploads/2017/02/PNG-Image-216-×-216-pixels.png">';
                           }    ?>
                         </a>  
                       </div>
                       <p><?php  the_title(); ?> <a href="#" class="f_c"><?php echo $product->get_price_html(); ?></a></p>
                       <p class="ratting"><a href="#" class="f_c">  <?php echo  $product->get_rating_html( ) ?></a></p>
                     </div>
                   </div>


                   <?php       }   wp_reset_postdata(); } else {   ?>

                    <div class="porduct-details">    
                      <div class="alert alert-warning">

                       Sorry, there is no product in this category.

                     </div>
                   </div>    

                   <?php } ?>                    



                 </div>
               </div>
               <?php $counter++; } } ?> 
             </div>
           </div>
         </section>

         <?php get_sidebar(); ?>
         <?php get_footer(); ?>