<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */
?>

<section class="block6">
        	<div class="container">
            	 <?php  dynamic_sidebar('four-1'); ?>
                 <?php  dynamic_sidebar('four-2'); ?>
                 <?php  dynamic_sidebar('four-3'); ?>
                 <?php  dynamic_sidebar('four-4'); ?>
        </section>
        <section class="block7">
        	<p>&copy; <?php echo date('Y'); ?>All rights reserved</p>
        </section>
    </div>

	
	<?php  dynamic_sidebar('four-5'); ?>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->


    <?php wp_footer(); ?>
    <script src="<?php echo get_template_directory_uri(); ?>/js/bootstrap.min.js"></script>
    <script>
     jQuery( document.body ).on( 'updated_cart_totals', function() {
       jQuery( '.woocommerce-message' ).html('Hold on! Updating cart.');
       window.location = '<?php echo get_option('siteurl').'/cart'; ?>';
    }); 
    
     jQuery( document ).ready( function (){

      jQuery('.trig_review').click( function(){
            
            $('#tab-description').hide();
            $('#tab-additional_information').hide();
            $('#tab-reviews').show();

      });

     });
  
    </script>
<script>
$( document ).ready(function() {
   $(".offer-popup").click(function(){
	   $("body").addClass("remove_over");
    $(".open_popup").css("display", "block");
  });
  $(".close_popup").click(function(){
	  $("body").removeClass("remove_over");
    $(".open_popup").css("display", "none");
  });
});

</script>
  </body>
</html>


<?php //dynamic_sidebar('GENERATEfourDIV1'); ?>

