<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that other
 * 'pages' on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */

get_header(); ?>
<?php /* The loop */ ?>

<section class="camera-page">   
        	<div class="container">
                   <div class="row">

                          <h3><strong><?php the_title(); ?></strong></h3>

 <?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
   <?php the_post_thumbnail(); ?>
<?php endif; ?>
                              

<?php while ( have_posts() ) : the_post(); ?>

<?php the_content(); ?>

<?php endwhile; ?>
        </div>
    </div>
</section>



<?php get_sidebar(); ?>
<?php get_footer(); ?>