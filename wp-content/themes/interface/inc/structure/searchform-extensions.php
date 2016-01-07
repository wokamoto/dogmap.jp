<?php
/**
 * Renders the search form of the theme.
 *
 * @package 		Theme Horse
 * @subpackage 		Interface
 * @since 			Interface 1.0
 * @license 		http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link 			http://themehorse.com/themes/interface
 */

add_action( 'interface_searchform', 'interface_display_searchform', 10 );
/**
 * Displaying the search form.
 *
 */
function interface_display_searchform() {
?>

<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="searchform clearfix">
  <label class="assistive-text">
    <?php _e( 'Search', 'interface' ); ?>
  </label>
  <input type="search" placeholder="<?php esc_attr_e( 'Search', 'interface' ); ?>" class="s field" name="s">
  <input type="submit" value="<?php esc_attr_e( 'Search', 'interface' ); ?>" class="search-submit">
</form>
<!-- .search-form -->
<?php
}
?>
