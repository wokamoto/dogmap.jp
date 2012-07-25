

<?php get_header(); ?>
      
     
  <div class="row">  
        <div class="span9">
        
      <?php 
	  if (have_posts()) :
	  while (have_posts()) :
	  the_post() ;
	  get_template_part('content');
      
	  endwhile;
	  endif;
	  ?>      
      <hr>
      <?php comments_template( '', true ); ?>
      <?php edit_post_link( __( 'Edit', 'toolbox' ), '<span class="edit-link">', '</span>' ); ?>
<?php get_template_part('back_to_top'); ?>
       </div>
       <?php get_sidebar(); ?>

      
<?php get_footer(); ?>
      