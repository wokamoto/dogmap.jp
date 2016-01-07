<?php
/**
 * This file displays page with right sidebar.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<?php
   /**
    * interface_before_primary
    */
   do_action( 'interface_before_primary' );
?>

<div id="primary" class="no-margin-left">
  <?php
      /**
       * interface_before_loop_content
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_loop_before 10
       */
      do_action( 'interface_before_loop_content' );

      /**
       * interface_loop_content
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_theloop 10
       */
      do_action( 'interface_loop_content' );

      /**
       * interface_after_loop_content
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_next_previous 5
		 * interface_loop_after 10
       */
      do_action( 'interface_after_loop_content' );      
   ?>
</div>
<!-- #primary -->

<?php
   /**
    * interface_after_primary
    */
   do_action( 'interface_after_primary' );
?>
<div id="secondary">
  <?php get_sidebar( 'right' ); ?>
</div>
<!-- #secondary -->