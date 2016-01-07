<?php
/**
 * Displays the footer section of the theme.
 *
 * @package 		Theme Horse
 * @subpackage 		Interface
 * @since 			Interface 1.0
 */
?>
</div>
<!-- .container -->
</div>
<!-- #main -->

<?php
		/** 
		 * interface_business_template_ourclients hook
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_display_business_template_ourclients 10
		 */
		
			if( is_page_template( 'page-templates/page-template-business.php' ) ) {
				do_action( 'interface_business_template_ourclients' );
			}

	?>
<?php
	      /** 
	       * interface_after_main hook
	       */
	      do_action( 'interface_after_main' );
	   ?>
<?php 
	   	/**
	   	 * interface_before_footer hook
	   	 */
	   	do_action( 'interface_before_footer' );
	   ?>

<footer id="colophon" class="clearfix">
  <?php
		      /** 
		       * interface_footer hook		       
				 *
				 * HOOKED_FUNCTION_NAME PRIORITY
				 *
				 * interface_footer_widget_area 5
				 * interface_footer_infoblog 10
				 * interface_footer_div_close 15
				 * interface_open_sitegenerator_div 20
				 * interface_socialnetworks 25
				 * interface_footer_info 30
				 * interface_close_sitegenerator_div 35
				 * interface_backtotop_html 40
		       */
		      do_action( 'interface_footer' );
		   ?>
</footer>
<?php 
	   	/**
	   	 * interface_after_footer hook
	   	 */
	   	do_action( 'interface_after_footer' );
	   ?>
</div>
<!-- .wrapper -->

<?php
		/** 
		 * interface_after hook
		 */
		do_action( 'interface_after' );
	?>
<?php wp_footer(); ?>
</body></html>