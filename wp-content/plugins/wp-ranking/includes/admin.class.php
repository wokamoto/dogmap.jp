<?php

new WPRankingAdmin();

class WPRankingAdmin {
	
	private $optiion_default = array(
								'display_num'  => 5,
								'cache_time'   => 3000,
								'interval'     => 4000,
								'display_term' => '7days'
									 );

	function __construct() {
		$option = get_option( 'wp_ranking_options' );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'wp_ranking_count_timer', array( &$this, 'wp_ranking_count_timer' ), 0 );
		add_filter( 'wp_ranking_default_rows', array( &$this, 'wp_ranking_default_rows' ), 0 );
		add_filter( 'wp_ranking_cache_expire', array( &$this, 'wp_ranking_cache_expire' ), 0 );
		add_filter( 'wp_ranking_default_period', array( &$this, 'wp_ranking_default_period' ), 0 );
	}

	public function admin_menu() 
	{
		add_menu_page( __( 'WP Ranking', 'wp-ranking' ), __( 'WP Ranking', 'wp-ranking' ), 'edit_posts', 'wp-ranking-view', array( &$this,'view' ) );
		add_submenu_page( 'wp-ranking-view', __( 'View', 'wp-ranking' ), __( 'View', 'wp-ranking' ), 'edit_posts', 'wp-ranking-view', array( &$this, 'view' ) );
		add_submenu_page( 'wp-ranking-view', __( 'Configuration', 'wp-ranking' ), __( 'Configuration', 'wp-ranking' ), 'edit_posts', 'wp-ranking-config', array( &$this, 'config' ) );
	}

	public function view()
	{
?>
<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html( __( 'WP Ranking', 'wp-ranking' ) ); ?></h2>
<?php if ( isset($updated_message) ) : ?>
<div id="message" class="updated fade"><p><?php echo $updated_message; ?></p></div>
<?php endif;
$list_table = new WP_Ranking_View_List_Table();
$list_table->prepare_items();
$list_table->views();
 ?>
<form id="entries-filter" method="get">
<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
<?php $list_table->display(); ?>
</form>
</div>
<?php
	}

	public function config()
	{
?>
<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html( __( 'WP Ranking', 'wp-ranking' ) ); ?></h2>
<?php if ( isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == 'true' ) : ?>
<div id="message" class="updated fade"><p><?php _e('Updated', 'wp-ranking') ?></p></div>
<?php endif; ?>
<form action="options.php" method="post">
<?php
settings_fields( 'wp_ranking_options' );
do_settings_sections( 'wp_ranking' );
?>
<p class="submit"><input name="Submit" type="submit" value="<?php _e( 'Save'); ?>" class="button-primary" /></p>
<input type="hidden" name="updated" vaalue="Y" />
</form>
</div>
<?php
	}

	public function admin_init() {
		register_setting( 'wp_ranking_options', 'wp_ranking_options',  array( &$this, 'options_validate') );
		add_settings_section( 'wp_ranking_main', __( 'Configuration' ), array( &$this, 'section_text' ), 'wp_ranking' );
		add_settings_field( 'wp_ranking_display_num', __( 'Number of display', 'wp-ranking' ), array( &$this, 'setting_displya_num'),
		'wp_ranking', 'wp_ranking_main' );
		add_settings_field( 'wp_ranking_cache_time', __( 'Cache Time', 'wp-ranking' ), array( &$this, 'setting_cache_time'),
		'wp_ranking', 'wp_ranking_main' );
		add_settings_field( 'wp_ranking_display_term', __( 'Displya term', 'wp-ranking' ), array( &$this, 'setting_display_term'),
		'wp_ranking', 'wp_ranking_main' );
		add_settings_field( 'wp_ranking_interval', __( 'Interval of requests to count as a value', 'wp-ranking' ), array( &$this, 'setting_interval'),
		'wp_ranking', 'wp_ranking_main' );
	}
	
	public function setting_displya_num() {
		$options = get_option( 'wp_ranking_options' );
		if ( !isset($options['display_num']) )
			$options['display_num'] = $this->optiion_default['display_num'];
		
		echo '<input id="displya_num" name="wp_ranking_options[display_num]" size="40" type="text" value="' . esc_attr( $options['display_num'] ) . '" />';
	}
	
	public function setting_cache_time() {
		$options = get_option( 'wp_ranking_options' );
		if ( !isset($options['cache_time']) )
			$options['cache_time'] = $this->optiion_default['cache_time'];
		
		echo '<input id="displya_num" name="wp_ranking_options[cache_time]" size="40" type="text" value="' . esc_attr( $options['cache_time'] ) . '" />second';
	}
	
	public function setting_display_term() {
		$options = get_option( 'wp_ranking_options' );
		if ( !isset($options['display_term']) )
			$options['display_term'] = $this->optiion_default['display_term'];
		
		echo '<select id="displya_num" name="wp_ranking_options[display_term]"><option '.selected( $options['display_term'], '30days', false).' value="30days">'.__('Monthly', 'wp-ranking').'</option><option '.selected( $options['display_term'], '7days', false).' value="7days">'.__('weekly', 'wp-ranking').'</option><option '.selected( $options['display_term'], 'yesterday', false).' value="yesterday">'.__('Daily', 'wp-ranking').'</option>';
	}
	
	public function setting_interval() {
		$options = get_option( 'wp_ranking_options' );
		if ( !isset($options['interval']) )
			$options['interval'] = $this->optiion_default['interval'];
		
		echo '<input id="interval" name="wp_ranking_options[interval]" size="40" type="text" value="' . esc_attr( $options['interval'] ) . '" />second';
	}
	
	public function options_validate( $input ) {
		$newinput['display_num'] = absint( $input['display_num'] );
		$newinput['cache_time'] = absint( $input['cache_time'] );
		$newinput['display_term'] = trim( $input['display_term'] );
		$newinput['interval'] = absint( $input['interval'] );
		return $newinput;
	}
	
	public function section_text() {
	}
	
		
	public function wp_ranking_count_timer($time) {
		$option = get_option( 'wp_ranking_options' );
		return isset($option['interval']) ? $option['interval'] : $time;
	}
	
	public function wp_ranking_default_rows($row) {
		$option = get_option( 'wp_ranking_options' );
		return isset($option['display_num']) ? $option['display_num'] : $row;
	}
	
	public function wp_ranking_cache_expire($time) {
		$option = get_option( 'wp_ranking_options' );
		return isset($option['cache_time']) ? $option['cache_time'] : $time;
	}
	
	public function wp_ranking_default_period($set) {
		$option = get_option( 'wp_ranking_options' );
		return isset($option['display_term']) ? $option['display_term'] : $set;
	}

} // WPRankingAdmin
// EOF



if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class WP_Ranking_View_List_Table extends WP_List_Table {

    var $example_data = array();
	var $status = ''; 
    
    function __construct(){
        global $status, $page, $wpranking;

		$this->status = isset($_GET['status']) ? $_GET['status'] : '30days';
		$this->example_data = $this->get_ranking($this->status);
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'ranking',
            'plural'    => 'ranking',
            'ajax'      => false
        ) );
        
    }

	function get_views() {
		$class = ' class="current"';
		
		$views = array();
		$views['30days'] = '<a href="?page=wp-ranking-view&status=30days"'. ( '30days' == $this->status ? $class : '' ) .' >' .__( 'monthly', 'wp-ranking' ). '</a>';
		$views['7days'] = '<a href="?page=wp-ranking-view&status=7days"'. ( '7days' == $this->status ? $class : '' ) .' >' .__( 'weekly', 'wp-ranking' ). '</a>';
		$views['yesterday'] = '<a href="?page=wp-ranking-view&status=yesterday"'. ( 'yesterday' == $this->status ? $class : '' ) .' >' .__( 'daily', 'wp-ranking' ). '</a>';


		return $views;
	}

	public function get_ranking($query_set, $rows = 10) {
    	global $wpranking;
    	$posts = $wpranking->get_ranking_data($query_set, $rows);
        $list = array();
		$count = 1;
        foreach ($posts as $p) {
            $list[] = array(
                'ID' => $p['post_id'],
				'title' => '<a href="'.get_permalink($p['post_id']).'" >'.get_the_title($p['post_id']).'</a>',
                'ranking' => $count++,
                'pv' => $p['count']
            );
        }
       	 
        return $list;
    }

    function column_default($item, $column_name){
               return $item[$column_name];
    }

    function get_columns(){
        $columns = array(
            'ranking'     => 'Ranking',
            'title'    => 'Title',
            'pv'  => 'Count'
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
			'ranking'    => array('ranking',true),
            'title'     => array('title',false),
            'pv'  => array('pv',false)
        );
        return $sortable_columns;
    }
    
    function process_bulk_action() {
    }
    

    function prepare_items() {
        
        $per_page = 10;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        

        $this->process_bulk_action();
        
        
        $data = $this->example_data;
                

        //function usort_reorder($a,$b){
        //    $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'ranking';
        //    $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
        //    $result = strcmp($a[$orderby], $b[$orderby]);
        //    return ($order==='asc') ? $result : -$result;
        //}
        //usort($data, 'usort_reorder');
        
        
        $current_page = $this->get_pagenum();
        
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
    
}