<?php
/**
 * The Header template for our theme
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php wp_title( '|', true, 'right' ); ?></title>

    <!-- Bootstrap -->
    <link href="<?php echo get_template_directory_uri(); ?>/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"
    

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
   <?php wp_head(); ?> 

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-96087778-1', 'auto');
  ga('send', 'pageview');

</script>

  </head>
  <body <?php body_class(); ?>>
    <div class="main">
    	<section class="block1">
        	<div class="container">
            	<div class="row">
                	<div class="col-md-2 col-sm-12 ">
                    	<div class="logo">
                        	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/logo.png"></a>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-7">
                    	<div class="header-nav">
                        	<ul>
                            	<?php 

                                   wp_nav_menu( array(
                                    'theme_location' => 'primary'
                                 ) ); ?>
                            </ul>
                            
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-5">
                      <?php if( get_option('woocommerce_demo_store') == 'yes') { ?>  <div class="bulk_alert"> <?php echo  get_option('woocommerce_demo_store_notice') ?> </div> <?php  } ?>
                    	<div class="call">
                        	<a href="tel:7733388100">Help Links<span><br><strong>773.338.8100</strong></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="block2">
        	<div class="container">
            	<div class="col-lg-3 hidden-sm"></div>
                <div class="col-lg-5 col-md-6 col-sm-6">
                	<div class="input-group" id="adv-search">
              <?php echo do_shortcode('[yith_woocommerce_ajax_search]');?>
                
            </div>
                	
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="wishlist"><a href="<?php echo get_option('siteurl'); ?>/wishlist/">Wishlist <i class="fa fa-heart" aria-hidden="true"></i></a></div>
                	<ul class="cart_li">
                        <li><a href="<?php echo get_option('siteurl');?>/my-account">My Account</a></li>
			<li><a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>"><?php echo sprintf ( _n( '<span class="c_r_p">%d</span> <i class="fa fa-shopping-cart" aria-hidden="true"></i>
', '<span class="c_r_p">%d</span> <i class="fa fa-shopping-cart" aria-hidden="true"></i>
', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?> </a></li>
                    </ul>
                </div>
            </div>
        </section>

  <?php  if(is_front_page() ) { ?>      
        <section class="block3">
        	<div class="container">
            	<div class="row">
                	<div class="col-sm-5 col-md-4 col-lg-3 pdg-rgt">

                    	<ul class="sidebar-menu">
                        		<h2>Shop by <strong>Popular Items</strong></h2>
                        	<?php 

                                   wp_nav_menu( array(
                                    'theme_location' => 'Sidebar',
                                    'link_after' => '<span><i class="fa fa-angle-right"></i></span>'
                                 ) ); ?>
                        </ul>
                    
                    </div>
                    <div class="col-sm-7 col-md-8 col-lg-9 pdg-lft">
                        
                        
                        <div id="myCarousel" class="carousel slide" data-ride="carousel">
              <!-- Indicators -->
                         <div class="carousel-inner" role="listbox">

                          <?php  $SliderArray = get_post_meta(151, '_cycloneslider_metas' , true ); ?>
                          <?php  $counter = 0; ?>
                          <?php  foreach( $SliderArray as $Slider_Array) {   ?>
                          <?php   $ImageSlide = get_post( $SliderArray[$counter]['id'] );
                              $ImagePath = $ImageSlide->guid;  
                          ?>
                                   <div class="item <?php if($counter == 0){ echo 'active'; } else { echo ''; } ?>">
                                       
                                        <img class="first-slide" src="<?php echo $ImagePath ?>" width="100%">
                                   </div>


                           <?php $counter++; } ?>          
                        
                      </div>
   			        </div>
                        
                        
                        
                        
                    </div>
                    
                </div>
            </div>
        </section>

<?php  } ?>
