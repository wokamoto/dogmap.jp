


      <article>
      <header class="page-header">
      <h1 class="page-title"><?php the_title(); ?></h1>
      </header>
      <section class="entry-content">
      <?php the_content(); ?>
	  <?php wp_link_pages(); ?>
      <?php posts_nav_link(); ?>
      <?php paginate_links(); ?>
      
	  <?php if (is_single()) : ?>
      <hr>
      <time pubdate="pubdate" datetime="<?php the_time('y-m-d'); ?>" 
      class="single-entry-date"><?php the_time(get_option('date_format')); ?>
      </time>
	  <?php endif; ?>
      
     
      </section>
      </article>
