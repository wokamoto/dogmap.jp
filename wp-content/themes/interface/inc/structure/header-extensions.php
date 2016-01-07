<?php
/**
 * Adds header structures.
 *
 * @package 		Theme Horse
 * @subpackage 		Interface
 * @since 			Interface 1.0
 * @license 		http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link 			http://themehorse.com/themes/interface
 */

/****************************************************************************************/

add_action( 'interface_title', 'interface_add_meta_name', 5 );
/**
 * Add meta tags.
 */ 
function interface_add_meta_name() {
?>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<?php  
 global $options, $array_of_default_settings;
 $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
	   if ('on' == $options['site_design']) { ?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<?php   } else{ ?>
<meta name="viewport" content="width=1078" />
<?php  }
}

/****************************************************************************************/

add_action( 'interface_links', 'interface_add_links', 10 );
/**
 * Adding link to stylesheet file
 *
 * @uses get_stylesheet_uri()
 */
function interface_add_links() {
?>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<!--[if lt IE 9]>
	<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js"></script>
	<![endif]-->
<?php
}

/****************************************************************************************/

// Load Favicon in Header Section
add_action( 'interface_links', 'interface_favicon', 15 );
// Load Favicon in Admin Section
add_action( 'admin_head', 'interface_favicon' );
/**
 * Get the favicon Image from Customizer
 * display favicon
 * 
 */
function interface_favicon() {	
	
	$interface_favicon = '';
		global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
		if ( 1 != $options[ 'disable_favicon' ] ) {
			if ( !empty( $options[ 'favicon' ] ) ) {
				$interface_favicon .= '<link rel="shortcut icon" href="'.esc_url( $options[ 'favicon' ] ).'" type="image/x-icon" />';
			}
		}

	echo $interface_favicon ;	
}

/****************************************************************************************/

// Load webpageicon in Header Section
add_action( 'interface_links', 'interface_webpage_icon', 20 );
/**
 * Get the webpageicon Image from Customizer
 * display webpageicon
 *
 */
function interface_webpage_icon() {	
	
	$interface_webpage_icon = '';
		global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());

		if ( 1 != $options[ 'disable_webpageicon' ] ) {
			if ( !empty( $options[ 'webpageicon' ] ) ) {
				$interface_webpage_icon .= '<link rel="apple-touch-icon-precomposed" href="'.esc_url( $options[ 'webpageicon' ] ).'" />';
			}
		}
		
		
	echo $interface_webpage_icon ;	
}

/****************************************************************************************/

add_action( 'interface_header', 'interface_headercontent_details', 10 );
/**
 * Shows Header content details
 *
 * Shows the site logo, title, description, searchbar, social icons and many more
 */
function interface_headercontent_details() {	
?>
<?php
		global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());

   	$elements = array();
		$elements = array( 	$options[ 'social_facebook' ], 
									$options[ 'social_twitter' ],
									$options[ 'social_googleplus' ],
									$options[ 'social_pinterest' ],
									$options[ 'social_youtube' ],
									$options[ 'social_vimeo' ],
									$options[ 'social_linkedin' ],
									$options[ 'social_flickr' ],
									$options[ 'social_tumblr' ],
									$options[ 'social_rss' ]
							 	);	

		$set_flags = 0;		
		if( !empty( $elements ) ) {
			foreach( $elements as $option) {
				if( !empty( $option ) ) {
					$set_flags = 1;
				}
				else {
					$set_flags = 0;
				}
				if( 1 == $set_flags ) {
					break;
				}
			}
		} ?>
<?php if ( ! function_exists( 'interface_footer_infoblog' ) ) :
/**
 * This function for social links display on header
 *
 * Get links through Theme Options
 */  
 

 function interface_footer_infoblog( $set_flags, $place ='') {
	
	global $options, $array_of_default_settings;
   $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
	$interface_footer_infoblog = '';
	$place = '';
	if($set_flags || (!empty($options['social_phone'] ) || !empty($options['social_email'] ) || !empty($options['social_location']))){
	$interface_footer_infoblog .=  '<div class="info-bar">
		<div class="container clearfix">';
		if(!empty($options['social_phone'] ) || !empty($options['social_email'] ) || !empty($options['social_location']) ) {
			$interface_footer_infoblog .=  '<div class="info clearfix">
											<ul>';
		if ( !empty($options['social_phone'] )){ 
		
		$interface_footer_infoblog .= '<li class=' .'"phone-number"'. '><a title='.__( '" Call Us "', 'interface' ).' '. 'href=' .'"tel:' ;
		$interface_footer_infoblog .=  preg_replace("/[^() 0-9+-]/", '', $options[ 'social_phone' ]) ; 
		
		$interface_footer_infoblog .= '">';
		$interface_footer_infoblog .=  preg_replace("/[^() 0-9+-]/", '', $options[ 'social_phone' ]) ;
		$interface_footer_infoblog .= '</a></li>';
		 
				} if (!empty($options['social_email'] )){ 
		
		
		$interface_footer_infoblog .='<li class=' .'"email"'. '><a title=' .__( '" Mail Us "', 'interface' ). ' ' . 'href=' .'"mailto:';
		$interface_footer_infoblog .=  is_email($options[ 'social_email'] );
		$interface_footer_infoblog .='">';
		$interface_footer_infoblog .=  is_email($options[ 'social_email'] ); 
		$interface_footer_infoblog .='</a></li>';
				 
				}if(!empty($options['social_location'])){
		
		$interface_footer_infoblog .='<li class=' .'"address"' .'>';
		$interface_footer_infoblog .=  esc_attr($options[ 'social_location']);
		$interface_footer_infoblog .= '</li>';
				} 
		$interface_footer_infoblog .= '</ul>
		</div><!-- .info -->';
		}
	}
	else if($place == "header" && $set_flags == 1 )
		$interface_footer_infoblog .=  '<div class="info-bar">
		<div class="container clearfix">';
	
	echo $interface_footer_infoblog;
}
endif;

if (1 != $options['disable_top']) {
 interface_footer_infoblog( $set_flags, "header" ); 
                   /****************************************************************************************/
}
if ( ! function_exists( 'interface_socialnetworks' ) ) :
/**
 * This function for social links display on header
 *
 * Get links through Theme Options
 */
 
function interface_socialnetworks( $set_flags ) {
	
		global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
		$interface_socialnetworks = '';
	if ( ( 1 != $set_flags ) || ( 1 == $set_flags ) )  {
				$social_links = array(); 
				$social_links_name = array();
				$social_links_name = array( __( 'Facebook', 'interface' ), // __ double underscore gets the value for translation
											__( 'Twitter', 'interface' ),
											__( 'Google Plus', 'interface' ),
											__( 'Pinterest', 'interface' ),
											__( 'Youtube', 'interface' ),
											__( 'Vimeo', 'interface' ),
											__( 'LinkedIn', 'interface' ),
											__( 'Flickr', 'interface' ),
											__( 'Tumblr', 'interface' ),
											__( 'RSS', 'interface' )
											);
				$social_links = array( 	'Facebook' 		=> 'social_facebook',
												'Twitter' 		=> 'social_twitter',
												'Google-Plus'	=> 'social_googleplus',
												'Pinterest' 	=> 'social_pinterest',
												'You-tube'		=> 'social_youtube',
												'Vimeo'			=> 'social_vimeo',
												'linkedin'			=> 'social_linkedin',
												'Flickr'			=> 'social_flickr',
												'Tumblr'			=> 'social_tumblr',
												'RSS'				=> 'social_rss'  
											);
											
											
				
				
				$i=0;
				$a = '';
				foreach( $social_links as $key => $value ) {
					if ( !empty( $options[ $value ] ) ) {
						$a .=
							'<li class="'.strtolower($key).'"><a href="'.esc_url( $options[ $value ] ).'" title="'.sprintf( esc_attr__( '%1$s on %2$s', 'interface' ), get_bloginfo( 'name' ), $social_links_name[$i] ).'" target="_blank">'.'</a></li>';
					}
				$i++;	
				}
				
				if($i > 0)
				{
					$interface_socialnetworks .='<div class="social-profiles clearfix">
					<ul>';
					$interface_socialnetworks .= $a;
						
		
					$interface_socialnetworks .='
				</ul>
				</div><!-- .social-profiles -->';
				}	
		
	}
	echo $interface_socialnetworks;
	
}
endif;
if (1 != $options['disable_top']) {
 interface_socialnetworks( $set_flags ); 
}
/****************************************************************************************/ ?>
<?php if(1 != $options['disable_top'] && ($set_flags == 1 || (!empty($options['social_phone'] ) || !empty($options['social_email'] ) || !empty($options['social_location'])))){ ?>
</div>
<!-- .container -->
</div>
<!-- .info-bar -->
<?php } ?>
<?php $header_image = get_header_image();
			if( !empty( $header_image ) ) :?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $header_image ); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"> </a>
<?php endif; ?>
<div class="hgroup-wrap">
  <div class="container clearfix">
    <section id="site-logo" class="clearfix">
      <?php 
						if( $options[ 'header_show' ] != 'disable-both' && $options[ 'header_show' ] == 'header-text' ) {
						?>
						<?php if(is_single() || (!is_page_template('page-templates/page-template-business.php' )) && !is_home()){ ?>
      <h2 id="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
        <?php bloginfo( 'name' ); ?>
        </a> </h2>
        <?php } else { ?>
        <h1 id="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
        <?php bloginfo( 'name' ); ?>
        </a> </h1>
        <?php }
       $site_description = get_bloginfo( 'description', 'display' );
			if($site_description){?>
      <h2 id="site-description">
        <?php bloginfo( 'description' ); ?>
      </h2>
        <?php } ?>
      <?php
						}
						elseif( $options[ 'header_show' ] != 'disable-both' && $options[ 'header_show' ] == 'header-logo' ) {
						?>
							<?php if(is_single() || (!is_page_template('page-templates/page-template-business.php' )) && !is_home()){ ?>
      <h2 id="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"> <img src="<?php echo $options[ 'header_logo' ]; ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"> </a> </h2>
      <?php
   						} else{ ?>
   						<h1 id="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"> <img src="<?php echo $options[ 'header_logo' ]; ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"> </a> </h1>
   					<?php	}
						}
						?>
    </section>
    <!-- #site-logo -->
    <button class="menu-toggle">Responsive Menu</button>
    <section class="hgroup-right">
      <?php
		if ( has_nav_menu( 'primary' ) ) { // if there is nav menu then content displayed from nav menu else from pages
			$args = array(
				'theme_location'    => 'primary',
				'container'         => '',
				'items_wrap'      => '<ul class="nav-menu">%3$s</ul>'
				 
			);
			echo '<nav id="access" class="clearfix">';
					
				wp_nav_menu( $args );  //extract the content from apperance-> nav menu
			echo '</nav><!-- #access -->';
		}
		else {								// extract the content from page menu only
			echo '<nav id="access" class="clearfix">';
				wp_page_menu(array( 'menu_class'  => 'nav-menu' ));
			echo '</nav><!-- #access -->';
		}
	?>
      <div class="search-toggle"></div>
      <!-- .search-toggle -->
      <div id="search-box" class="hide">
        <?php get_search_form(); ?>
        <span class="arrow"></span> </div>
      <!-- #search-box --> 
    </section>
    <!-- .hgroup-right --> 
  </div>
  <!-- .container --> 
</div>
<!-- .hgroup-wrap -->

<?php	global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());

		if( 'above-slider' == $options[ 'slogan_position' ] &&  ( is_home() || is_front_page() ) ) 
			if( function_exists( 'interface_home_slogan' ) )

				interface_home_slogan(); 

		if( is_home() || is_front_page() ) {
			if( "0" == $options[ 'disable_slider' ] ) {
				if( function_exists( 'interface_pass_slider_effect_cycle_parameters' ) ) 
   				interface_pass_slider_effect_cycle_parameters();
   			if( function_exists( 'interface_featured_sliders' ) ) 
   				interface_featured_sliders();
   		}
		}
		else { 
			if( ( '' != interface_header_title() ) || function_exists( 'bcn_display_list' ) ) { 
		?>
<div class="page-title-wrap">
  <div class="container clearfix">
    <?php
		    		if( function_exists( 'interface_breadcrumb' ) )
						interface_breadcrumb();
					?>
				<?php if( is_page() ) {
		if( is_page_template('page-templates/page-template-blog-full-content.php') || is_page_template('page-templates/page-template-blog-image-large.php') || is_page_template('page-templates/page-template-blog-image-medium.php') || is_page_template('page-templates/page-template-business.php') || is_page_template('page-templates/page-template-contact.php' )) { ?>
    <h1 class="page-title"><?php echo interface_header_title(); ?></h1>
    <?php }else { ?>
	<h1 class="page-title" id="entry-title"><?php echo interface_header_title(); ?></h1>
    <?php }
    } else{ ?>
    <h1 class="page-title"><?php echo interface_header_title(); ?></h1>
    <?php	}?>
    <!-- .page-title --> 
  </div>
</div>
<?php
	   	}
		} 
		global $options, $array_of_default_settings;
      $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
		if( 'below-slider' == $options[ 'slogan_position' ] && ( is_home() || is_front_page() ) ) 
			if( function_exists( 'interface_home_slogan' ) )
				interface_home_slogan(); 

}
if ( ! function_exists( 'interface_home_slogan' ) ) :

/**
 * Display Home Slogan.
 *
 * Function that enable/disable the home slogan1 and home slogan2.
 */
function interface_home_slogan() {	
	global $options, $array_of_default_settings;
   $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
	$interface_home_slogan = '';
	if( !empty( $options[ 'home_slogan1' ] ) || !empty( $options[ 'home_slogan2' ] ) ) {
      
		if ( 0 == $options[ 'disable_slogan' ] ) {
			$interface_home_slogan .= '<section class="slogan-wrap"><div class="container"><div class="slogan">';
			if ( !empty( $options[ 'home_slogan1' ] ) ) {
				$interface_home_slogan .= esc_html( $options[ 'home_slogan1' ] );
			}
			if ( !empty( $options[ 'home_slogan2' ] ) ) {
				$interface_home_slogan .= '<span>'.esc_html( $options[ 'home_slogan2' ] ).'</span>';
			}
			$interface_home_slogan .= '</div><!-- .slogan -->';
			$interface_home_slogan .= '</div><!-- .container --></section><!-- .slogan-wrap -->';
		}
		
	}	
	echo $interface_home_slogan;
}
endif;

/****************************************************************************************/

if ( ! function_exists( 'interface_featured_sliders' ) ) :
/**
 * displaying the featured image in home page
 *
 */
function interface_featured_sliders() {	
	global $post;
	global $options, $array_of_default_settings;
   $options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
	
	$interface_featured_sliders = '';
		if( !$interface_featured_sliders != empty( $options[ 'featured_post_slider' ] ) ) {

		if( 'narrow-layout' == $options[ 'site_layout' ] ) {
			$slider_size = 'slider-narrow';
			
			
		}
		else {
			$slider_size = 'slider-wide';
			
		}
		
		$interface_featured_sliders .= '
		<section class="featured-slider"><div class="slider-cycle">';
			$get_featured_posts = new WP_Query( array(
				'posts_per_page' 			=> $options[ 'slider_quantity' ],
				'post_type'					=> array( 'post', 'page' ),
				'post__in'		 			=> $options[ 'featured_post_slider' ],
				'orderby' 		 			=> 'post__in',
				'ignore_sticky_posts' 	=> 1 	
			));
			$i=0; while ( $get_featured_posts->have_posts()) : $get_featured_posts->the_post(); $i++;
				$title_attribute = apply_filters( 'the_title', get_the_title( $post->ID ) );
				$excerpt = get_the_excerpt();
				if ( 1 == $i ) { $classes = "slides displayblock"; } else { $classes = "slides displaynone"; }
				$interface_featured_sliders .= '
				<div class="'.$classes.'">';
						if( has_post_thumbnail() ) {
	$interface_featured_sliders .= '<figure><a href="' . get_permalink() . '" title="'.the_title('','',false).'">';
	$interface_featured_sliders .= get_the_post_thumbnail( $post->ID, $slider_size, array( 'title' => esc_attr( $title_attribute ), 'alt' => esc_attr( $title_attribute ), 'class'	=> 'pngfix' ) ).'</a></figure>';
						}
						if( $title_attribute != '' || $excerpt !='' ) {
						$interface_featured_sliders .= '
							<div class="container">
							<article class="featured-text">';
							if( $title_attribute !='' ) {
									$interface_featured_sliders .= '<h2 class="featured-title"><a href="' . get_permalink() . '" title="'.the_title('','',false).'">'. get_the_title() . '</a></h2><!-- .featured-title -->';
							}
							if( $excerpt !='' ) {	
							
								if(strlen($excerpt) >120){
								$excerpt_length = substr($excerpt, 0 , 120);					
								$interface_featured_sliders .= '<div class="featured-content">'.$excerpt_length.'...'.'</div><!-- .featured-content -->';
								}else{
								$interface_featured_sliders .= '<div class="featured-content">'.$excerpt.'</div><!-- .featured-content -->';
									}
							}
						$interface_featured_sliders .= '
							</article><!-- .featured-text -->
							</div>';
						}
				$interface_featured_sliders .= '
				</div><!-- .slides -->';
			endwhile; wp_reset_query();
		$interface_featured_sliders .= '</div>	<!-- .slider-cycle -->			
		<nav id="controllers" class="clearfix">
		</nav><!-- #controllers --></section><!-- .featured-slider -->';
		}
	echo $interface_featured_sliders;	
}
endif;

/****************************************************************************************/

if ( ! function_exists( 'interface_breadcrumb' ) ) :
/**
 * Display breadcrumb on header.
 *
 * If the page is home or front page, slider is displayed.
 * In other pages, breadcrumb will display if breadcrumb NavXT plugin exists.
 */
function interface_breadcrumb() {
	if( function_exists( 'bcn_display' ) ) {
		echo '<div class="breadcrumb">';                
		bcn_display();               
		echo '</div> <!-- .breadcrumb -->'; 
	}   
}
endif;

/****************************************************************************************/

if ( ! function_exists( 'interface_header_title' ) ) :
/**
 * Show the title in header
 *
 * @since Interface 1.0
 */
function interface_header_title() {
	if( is_archive() ) {
		$interface_header_title = single_cat_title( '', FALSE );
	}
	elseif( is_404() ) {
		$interface_header_title = __( 'Page NOT Found', 'interface' );
	}
	elseif( is_search() ) {
		$interface_header_title = __( 'Search Results', 'interface' );
	}
	elseif( is_page_template()  ) {
		$interface_header_title = get_the_title();
	}
	else {
		$interface_header_title = get_the_title();
	}

	return $interface_header_title;

}
endif;
?>
