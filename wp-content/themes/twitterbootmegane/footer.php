
       </div>
      
<hr>
<footer>
 <div class="row">  
       
        <div class="span3">
        <?php dynamic_sidebar('footer01'); ?>
          </div>
          <div class="span3">
          <?php dynamic_sidebar('footer02'); ?>
        </div>
          
          <div class="span3">
          <?php dynamic_sidebar('footer03'); ?>
        </div>
          
          <div class="span3">
          <?php dynamic_sidebar('footer04'); ?>
        
Copyright &copy; <?php bloginfo('name'); ?>
</div>
</div>
</footer>
</div>

<script src="<?php echo get_template_directory_uri(); ?>/js/bootstrap.min.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/scroll.js"></script>

<?php wp_footer(); ?>
</body>
</html>
