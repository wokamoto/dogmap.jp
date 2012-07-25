


      <article <?php post_class(); ?>>
 
      <header class="entry-header">
             <div class="row">  
        <div class="span2">
      <?php the_post_thumbnail ('thumbnail', array('alt' => the_title_attribute('echo=0'), 'title' => the_title_attribute('echo=0'))); ?>
      </div>
      <div class="span7">
      <time pubdate="pubdate" datetime="<?php the_time('y-m-d'); ?>" 
      class="entry-date"><?php the_time(get_option('date_format')); ?>
      </time>
      <h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>      
      <?php the_excerpt(); ?>
      </div>
      </div>
      </header>
      </article>
      <hr>
