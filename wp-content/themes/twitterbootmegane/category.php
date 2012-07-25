

<?php get_header(); ?>
      
     
  <div class="row">  
        <div class="span9">
        <header class="page-header">
        <h1 class="page-title">
        <?php
						printf( __( '%sの一覧','twitterbootmegane'), '<span>' . single_cat_title( '', false ) . '</span>' );
					?>
        </h1>
        </header>
        <div class="post">
      <?php 
	  if (have_posts()) :
	  while (have_posts()) :
	  the_post() ;
	  get_template_part('content-archive');
      
	  endwhile;
	  endif;
	  ?>      
      <?php if ( function_exists( 'page_navi' ) ) page_navi( 'items=7&prev_label=Prev&next_label=Next&first_label=First&last_label=Last&show_num=1&num_position=after' ); ?>

<?php get_template_part('back_to_top'); ?>
      </div>
       </div>
       <?php get_sidebar(); ?>

      
<?php get_footer(); ?>
      