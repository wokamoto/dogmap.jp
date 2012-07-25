

<?php get_header(); ?>
      
  <div class="row">
  

        <div class="span6">
          <?php query_posts($query_string .'&posts_per_page=3'); 
	  if (have_posts()) :
	  while (have_posts()):
	  the_post() ;
	  get_template_part('front-content-archive');
      
	  endwhile;
	  endif;
	  ?>   
        <h2>Heading</h2>
           <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
          <p><a class="btn" href="#">View details &raquo;</a></p>
             <div class="row">
              <div class="span1">
                   <p><a class="btn btn-primary" href="#">View details &raquo;</a></p></div>
              <div class="span1">
                   <p><a class="btn btn-info" href="#">View details &raquo;</a></p></div>
              <div class="span1">
                   <p><a class="btn btn-success" href="#">View details &raquo;</a></p></div>
              <div class="span1">
                   <p><a class="btn btn-warning" href="#">View details &raquo;</a></p></div>
              <div class="span1">
                   <p><a class="btn btn-danger" href="#">View details &raquo;</a></p></div>
              <div class="span1">
                   <p><a class="btn btn-inverse" href="#">View details &raquo;</a></p></div>
             </div>

       </div>
   
         <div class="span3">
  
<?php dynamic_sidebar('primary-widget-area'); ?>
      </div>
      
       <?php get_sidebar(); ?>

      
<?php get_footer(); ?>
      