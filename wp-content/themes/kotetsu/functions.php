<?php
require_once get_template_directory() . '/inc/mw-bread-crumb.php';
require_once get_template_directory() . '/inc/kotetsu-customizer.php';

function kotetsu_parent_theme_setup() {
	if ( !class_exists( 'Kotetsu' ) ) {
		class Kotetsu extends KotetsuBaseFunctions {
			public function __construct() {
				parent::__construct();
			}
		}
	}
	$Kotetsu = new Kotetsu();
}
add_action( 'after_setup_theme', 'kotetsu_parent_theme_setup', 99999 );

/**
 * KotetsuBaseFunctions
 * Version: 1.0.1
 * Author: inc2734
 * Author URI: http://2inc.org
 */
class KotetsuBaseFunctions {

	/**
	 * __construct
	 */
	public function __construct() {
		global $content_width;
		if ( !isset( $content_width ) ) $content_width = 940;
		load_theme_textdomain( 'kotetsu', get_template_directory() . '/languages' );
		add_editor_style();
		add_filter( 'tiny_mce_before_init', array( $this, 'add_body_class_mce' ) );
		add_theme_support( 'automatic-feed-links' );
		$this->_menu();
		$this->_customizer();
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		add_action( 'widgets_init', array( $this, '_register_sidebar' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'wp_title', array( $this, 'wp_title' ), 11, 3 );
	}

	/**
	 * add_body_class_mce
	 * @param array $initArray
	 * @return array $initArray
	 */
	public function add_body_class_mce( $initArray ){
		$initArray['body_class'] = implode( ' ', get_post_class() );
		return $initArray;
	}

	/**
	 * _menu
	 */
	protected function _menu() {
		add_theme_support( 'menu' );
		register_nav_menus( array(
			'global-nav' => __( 'Global Navigation', 'kotetsu' ),
			'footer-nav' => __( 'Footer Navigation', 'kotetsu' ),
		) );
	}

	/**
	 * _customizer
	 */
	protected function _customizer() {
		$Kotetsu_Customizer = new Kotetsu_Customizer();
		add_action( 'customize_register', array( $Kotetsu_Customizer, 'customize_register' ) );
		add_action( 'wp_head', array( $Kotetsu_Customizer, 'customize_css' ) );
		add_action( 'customize_preview_init', array( $Kotetsu_Customizer, 'customize_preview_js' ) );
	}

	/**
	 * _register_sidebar
	 */
	public function _register_sidebar() {
		register_sidebar( array(
			'name'          => __( 'blog sidebar', 'kotetsu' ),
			'id'            => 'blog-sidebar',
			'description'   => __( 'right column', 'kotetsu' ),
			'before_widget' => '<div id="%1$s" class="widget-container %2$s"><dl>',
			'after_widget'  => '</dd></dl></div>',
			'before_title'  => '<dt class="widget-title">',
			'after_title'   => '</dt><dd class="widget-content">',
		) );
	}

	/**
	 * the_comments
	 * @param object $comment
	 * @param array $args
	 * @param int $depth
	 */
	public static function the_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment; ?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
			<dl id="comment-<?php comment_ID(); ?>" class="comment-item">
				<dt class="comment-header">
					<div class="comment-author vcard">
						<?php echo get_avatar( $comment, $size = '48', $default = '' ); ?>
						<cite class="fn"><?php comment_author_link(); ?></cite>
					<!-- end .comment-author --></div>
				</dt>
				<dd class="comment-body">
					<?php if ( $comment->comment_approved == '0' ) : ?>
						<?php _e( 'Your comment is awaiting moderation.', 'kotetsu' ) ?></em><br />
					  <?php endif; ?>
					<div class="comment-meta commentmetadata">
						<?php printf( __( '%1$s at %2$s', 'kotetsu' ), get_comment_date(), get_comment_time() ) ?><?php edit_comment_link( 'edit', '  ', '' ); ?>
					<!-- end .comment-meta --></div>
					<?php comment_text() ?>
					<div class="reply">
						<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
					<!-- end .reply --></div>
				</dd>
			</dl>
	<?php
	}

	/**
	 * the_entry_meta
	 */
	public static function the_entry_meta() {
		?>
		<div class="entry-meta">
			<ul>
				<li class="vCard author"><?php _e( 'Author', 'kotetsu' ); ?>: <span class="fn"><?php the_author(); ?></span></li>
				<li class="published">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php _e( 'Published', 'kotetsu' ); ?>: <?php echo esc_html( get_the_date() ); ?></time>
				</li>
				<li class="updated hidden">
					<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php _e( 'Updated', 'kotetsu' ); ?>: <?php echo esc_html( get_the_modified_date() ); ?></time>
				</li>
				<?php
				if ( $categories = get_the_category() ) :
					foreach ( $categories as $category )
						$catArr[] = '<a href="'.get_category_link( $category->cat_ID ).'">'.$category->cat_name.'</a>';
					?>
				<li class="categories">Category: <?php echo implode( ', ', $catArr ); ?></li>
				<?php endif; ?>
				<?php if ( $tags_list = get_the_tag_list( '', ', ' ) ): ?>
				<li class="tags">Tags: <?php echo $tags_list; ?></li>
				<?php endif; ?>
				<?php
				$postTypeObject = get_post_type_object( get_post_type() );
				if ( !empty( $postTypeObject->taxonomies ) ) {
					foreach ( $postTypeObject->taxonomies as $taxonomy_name ) {
						$term_list = get_the_term_list( get_the_ID(), $taxonomy_name,  '', ', ', '' );
						?>
						<li class="<?php echo esc_attr( $taxonomy_name ); ?>">Category: <?php echo $term_list; ?></li>
						<?php
					}
				}
				?>
			</ul>
		<!-- end .entry-meta --></div>
		<?php
	}

	/**
	 * the_copyright
	 */
	public static function the_copyright() {
		$theme_url = 'http://2inc.org';
		$wordpress_url = 'http://wordpress.org/';
		$theme_link = sprintf( '<a href="%s" target="_blank">%s</a>',
			esc_url( $theme_url ),
			__( 'Monkey Wrench', 'kotetsu' )
		);
		$wordpress_link = sprintf( '<a href="%s" target="_blank">%s</a>',
			esc_url( $wordpress_url ),
			__( 'WordPress', 'kotetsu' )
		);
		printf( __( 'Kotetsu theme by %s', 'kotetsu' ), $theme_link );
		echo '&nbsp;';
		printf( __( 'Powered by %s', 'kotetsu' ), $wordpress_link );
	}

	/**
	 * enqueue_styles
	 */
	public function enqueue_styles() {
		$url = get_template_directory_uri();
		if ( is_admin() ) return;
		wp_enqueue_style( 'base', get_stylesheet_uri() );
		wp_enqueue_style( 'base_layout_css', $url . '/css/layout.css' );
		wp_enqueue_style( 'jquery.scrollButton', $url . '/js/jquery.scrollButton/jquery.scrollButton.css' );
		wp_enqueue_style( 'responsive-nav', $url . '/js/jquery.responsive-nav/jquery.responsive-nav.css' );
		if ( is_singular() && get_option( 'thread_comments' ) ) {
			wp_enqueue_style( 'comment-reply' );
		}
	}

	/**
	 * enqueue_scripts
	 */
	public function enqueue_scripts() {
		$url = get_template_directory_uri();
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
		if ( is_admin() ) return;
		wp_enqueue_script( 'jquery.naviRollover', $url . '/js/jquery.naviRollover.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'jquery.smoothScroll', $url . '/js/jquery.smoothScroll.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'jquery.scrollButton', $url . '/js/jquery.scrollButton/jquery.scrollButton.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'responsive-nav', $url . '/js/jquery.responsive-nav/jquery.responsive-nav.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'base_main', $url . '/js/main.js', array( 'jquery' ), false, true );
	}

	/**
	 * wp_title
	 */
	public function wp_title( $title, $sep, $seplocation ) {
		if ( $sep === '|' && $seplocation === 'right' ) {
			$title .= get_bloginfo( 'name' );
		}
		return $title;
	}

	/**
	 * pager
	 */
	public static function pager() {
		global $wp_rewrite;
		global $wp_query;
		global $paged;
		$paginate_base = get_pagenum_link( 1 );
		if ( strpos( $paginate_base, '?' ) || ! $wp_rewrite->using_permalinks() ) {
			$paginate_format = '';
			$paginate_base = add_query_arg( 'paged', '%#%' );
		} else {
			$paginate_format = ( substr( $paginate_base, -1 ,1 ) == '/' ? '' : '/' ) .
			user_trailingslashit( 'page/%#%/', 'paged' );
			$paginate_base .= '%_%';
		}
		$paginate_links = paginate_links( array(
			'base'      => $paginate_base,
			'format'    => $paginate_format,
			'total'     => $wp_query->max_num_pages,
			'mid_size'  => 5,
			'current'   => ( $paged ? $paged : 1 ),
			'prev_text' => '&lt;',
			'next_text' => '&gt;',
		) );
		if ( $paginate_links ) {
			?>
			<div class="pager">
				<p>
					<?php echo $paginate_links; ?>
				</p>
			<!-- end .pager --></div>
			<?php
		}
	}

	/**
	 * the_link_pages
	 */
	public static function the_link_pages() {
		wp_link_pages( array(
			'before' => '<div class="pager"><p>',
			'after' => '</p><!-- end .pager --></div>',
			'nextpagelink'     => '&gt;%gt;',
			'previouspagelink' => '&lt;%lt;',
			'pagelink' => '<span>%</span>'
		) );
	}

	/**
	 * the_bread_crumb
	 */
	public static function the_bread_crumb( array $params = array() ) {
		$mw_bread_crumb = new mw_bread_crumb();
		$mw_bread_crumb->display( $params );
	}
}
