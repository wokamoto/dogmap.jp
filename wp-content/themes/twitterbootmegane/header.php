<!DOCTYPE HTML>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<link rel="profile" href="http://gmpg.org/xfn/11" />

<link rel="stylesheet" type="text/css" media="all" href="<?php echo get_template_directory_uri(); ?>/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" media="all" href="<?php echo get_template_directory_uri(); ?>/css/bootstrap-responsive.css" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' ); ?>
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<title><?php
  global $page, $paged;
  if (is_search()) : 
    wp_title('', true, 'left');
    echo ' | ';
  else :
    wp_title('|', true, 'right');
  endif;
  bloginfo('name');
  if (is_front_page()) : 
    echo ' | ';
    bloginfo('description');
  endif;
  if ($paged >= 2 || $page >= 2) : 
    echo ' | ' . sprintf('%sページ', max($paged, $page));
  endif;
?></title>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
 <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="http://test.m-g-n.me/">megane9988</a>
          <div class="nav-collapse">
          <?php wp_nav_menu(array(  
		  'theme_location' => 'place_global',
		  'menu_class' => 'nav',
		  ));
		  ?>
         
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">
<div class="row">
        <div class="span12">
<?php if (!is_front_page() && function_exists('bread_crumb')) :
bread_crumb('navi_element=nav&elm_id=bread-crumb');
endif;
?>
</div>
</div>
  
<?php if (is_front_page()) { ?>

<div class="row">
        <div class="span9">
           <p><img src="<?php header_image(); ?> " /></p>
        </div>
        <div class="span3">
         
        <?php dynamic_sidebar('top-widget-area'); ?>
       </div>
      </div>

        <?php } ?>