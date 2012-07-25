

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
       <?php twitterbootmegane_posted_in(); ?>
      <?php edit_post_link( __( 'Edit', 'toolbox' ), '<span class="edit-link">', '</span>' ); ?>
      <hr>
      <div id="nav-below" class="navigation">
					<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '前の記事:', 'Previous post link','twitterbootmegane' ) . '</span> %title' ); ?></div>
					<div class="nav-next"><?php next_post_link( '%link', '<span class="meta-nav">' . _x( '次の記事:', 'Next post link','twitterbootmegane' ) . '</span> %title' ); ?></div>
				</div>
      <?php comments_template( '', true ); ?>
<?php get_template_part('back_to_top'); ?>
       </div>
       <?php get_sidebar(); ?>

      
<?php get_footer(); ?>
      