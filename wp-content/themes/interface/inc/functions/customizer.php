<?php 
/**
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 3.0
 */
function interface_textarea_register($wp_customize){
	class Interface_Customize_Interface_upgrade extends WP_Customize_Control {
		public function render_content() { ?>
		<div class="theme-info"> 
			<a title="<?php esc_attr_e( 'Donate', 'interface' ); ?>" href="<?php echo esc_url( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=BRLCCUGP2ACYN' ); ?>" target="_blank">
			<?php _e( 'Donate', 'interface' ); ?>
			</a>
			<a title="<?php esc_attr_e( 'Review Interface', 'interface' ); ?>" href="<?php echo esc_url( 'http://wordpress.org/support/view/theme-reviews/interface' ); ?>" target="_blank">
			<?php _e( 'Rate Interface', 'interface' ); ?>
			</a>
			<a href="<?php echo esc_url( 'http://themehorse.com/theme-instruction/interface/' ); ?>" title="<?php esc_attr_e( 'Interface Theme Instructions', 'interface' ); ?>" target="_blank">
			<?php _e( 'Theme Instructions', 'interface' ); ?>
			</a>
			<a href="<?php echo esc_url( 'http://themehorse.com/support-forum/' ); ?>" title="<?php esc_attr_e( 'Support Forum', 'interface' ); ?>" target="_blank">
			<?php _e( 'Support Forum', 'interface' ); ?>
			</a>
			<a href="<?php echo esc_url( 'http://themehorse.com/preview/interface/' ); ?>" title="<?php esc_attr_e( 'Interface Demo', 'interface' ); ?>" target="_blank">
			<?php _e( 'View Demo', 'interface' ); ?>
			</a>
		</div>
		<?php
		}
	}
	class Interface_Customize_Interface_upgrade_to_pro extends WP_Customize_Control {
		public function render_content() { ?>
			<a href="<?php echo esc_url( 'http://themehorse.com/themes/interface-pro/' ); ?>" title="<?php esc_attr_e( 'Upgrade to Interface Pro', 'interface' ); ?>" target="_blank">
			<?php _e( 'Upgrade to Interface Pro', 'interface' ); ?>
			</a><?php _e('to get more aditional features like (Advanced Slider, Color Options, Typography Options and many more.)','interface');?>
		<?php
		}
	}
	class Interface_Customize_Category_Control extends WP_Customize_Control {
		/**
		* The type of customize control being rendered.
		*/
		public $type = 'multiple-select';
		/**
		* Displays the multiple select on the customize screen.
		*/
		public function render_content() {
		global $options, $array_of_default_settings;
		$options = wp_parse_args(  get_option( 'interface_theme_options', array() ),  interface_get_option_defaults());
		$categories = get_categories(); ?>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<select <?php $this->link(); ?> multiple="multiple" style="height: 100%;">
				<option value="0" <?php if ( empty( $options['front_page_category'] ) ) { selected( true, true ); } ?>><?php _e( '--Disabled--', 'interface' ); ?></option>
				<?php
					foreach ( $categories as $category) :?>
						<option value="<?php echo $category->cat_ID; ?>" <?php if ( in_array( $category->cat_ID, $categories) ) { selected();}?>><?php echo $category->cat_name; ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php 
		}
	}
}

function interface_customize_register($wp_customize){
	$wp_customize->add_panel( 'interface_layout_options_panel', array(
	'priority'       => 200,
	'capability'     => 'edit_theme_options',
	'title'          => __('Layout Options', 'interface')
	));
	$wp_customize->add_panel( 'interface_design_options_panel', array(
	'priority'       => 300,
	'capability'     => 'edit_theme_options',
	'title'          => __('Design Options', 'interface')
	));

	$wp_customize->add_panel( 'interface_advanced_options_panel', array(
	'priority'       => 400,
	'capability'     => 'edit_theme_options',
	'title'          => __('Advance Options', 'interface')
	));

	$wp_customize->add_panel( 'interface_featured_post_page_panel', array(
	'priority'       => 500,
	'capability'     => 'edit_theme_options',
	'title'          => __('Featured Post/Page Slider', 'interface')
	));

	$wp_customize->add_panel( 'interface_contact_social_panel', array(
	'priority'       => 600,
	'capability'     => 'edit_theme_options',
	'title'          => __('Contact / Social Links', 'interface')
	));

	global $options, $array_of_default_settings;
	$options = wp_parse_args(  get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
/********************Interface Upgrade ******************************************/
	$wp_customize->add_section('interface_upgrade_to_pro', array(
		'title'					=> __('What is new on Interface Pro?', 'interface'),
		'priority'				=> 0.5,
	));
	$wp_customize->add_setting( 'interface_theme_settings[interface_upgrade_to_pro]', array(
		'default'				=> false,
		'capability'			=> 'edit_theme_options',
		'sanitize_callback'	=> 'wp_filter_nohtml_kses',
	));
	$wp_customize->add_control(
		new Interface_Customize_Interface_upgrade_to_pro(
		$wp_customize,
		'interface_upgrade_to_pro',
			array(
				'label'					=> __('Interface Upgrade','interface'),
				'section'				=> 'interface_upgrade_to_pro',
				'settings'				=> 'interface_theme_settings[interface_upgrade_to_pro]',
			)
		)
	);
	$wp_customize->add_section('interface_upgrade', array(
		'title'					=> __('Interface Support', 'interface'),
		'description'			=> __('Hey! Buy us a beer and we shall come with new features and update.','interface'),
		'priority'				=> 1,
	));
	$wp_customize->add_setting( 'interface_theme_options[interface_upgrade]', array(
		'default'				=> false,
		'capability'			=> 'edit_theme_options',
		'sanitize_callback'	=> 'wp_filter_nohtml_kses',
	));
	$wp_customize->add_control(
		new Interface_Customize_Interface_upgrade(
		$wp_customize,
		'interface_upgrade',
			array(
				'label'					=> __('Interface Upgrade','interface'),
				'section'				=> 'interface_upgrade',
				'settings'				=> 'interface_theme_options[interface_upgrade]',
			)
		)
	);
	/******************** Layout Options ******************************************/
	/********************Site Layout******************************************/
	$wp_customize->add_section('interface_site_layout', array(
		'title'					=> __('Site Layout', 'interface'),
		'priority'				=> 210,
		'panel'					=>'interface_layout_options_panel'
	));
	$wp_customize->add_setting('interface_theme_options[site_layout]', array(
		'default'				=> 'wide-layout',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('interface_site_layout', array(
		'label'					=> __('Site Layout','interface'),
		'description'			=> __('This change is reflected in whole website','interface'),
		'section'				=> 'interface_site_layout',
		'settings'				=> 'interface_theme_options[site_layout]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
		'choices'				=> array(
			'narrow-layout'					=> __('Narrow Layout','interface'),
			'wide-layout'					=> __('Wide Layout','interface'),
		),
	));
	/********************Content Layout******************************************/
	$wp_customize->add_section('interface_default_layout', array(
		'title'					=> __('Content Layout', 'interface'),
		'priority'				=> 220,
		'panel'					=>'interface_layout_options_panel'
	));
	$wp_customize->add_setting('interface_theme_options[default_layout]', array(
		'default'				=> 'right-sidebar',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('interface_default_layout', array(
		'label'					=> __('Layouts','interface'),
		'section'				=> 'interface_default_layout',
		'settings'				=> 'interface_theme_options[default_layout]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
		'choices'				=> array(
			'no-sidebar'			=> __('No Sidebar','interface'),
			'no-sidebar-full-width'			=> __('No Sidebar, Full Width','interface'),
			'left-sidebar'				=> __('Left Sidebar','interface'),
			'right-sidebar'				=> __('Right Sidebar','interface'),
		),
	));
	/********************Responsive Layout******************************************/
	$wp_customize->add_section('interface_site_design', array(
		'title'					=> __('Responsive Layout', 'interface'),
		'priority'				=> 230,
		'panel'					=>'interface_layout_options_panel'
	));
	$wp_customize->add_setting('interface_theme_options[site_design]', array(
		'default'				=> 'on',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('interface_site_design', array(
		'label'					=> __('Responsive Layout','interface'),
		'section'				=> 'interface_site_design',
		'settings'				=> 'interface_theme_options[site_design]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
		'choices'				=> array(
			'on'					=> __('ON(Responsive view will be displayed in small devices )','interface'),
			'off'					=> __('OFF(Full site will display as desktop view)','interface'),
		),
	));

	/******************** Design Options ******************************************/
	/******************** Custom Header ******************************************/
	$wp_customize->add_section('custom_header_setting', array(
		'title'					=> __('Custom Header', 'interface'),
		'priority'				=> 200,
		'panel'					=>'interface_design_options_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[hide_header_searchform]', array(
		'default'				=> 0,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control( 'custom_header_setting', array(
		'label'					=> __('Hide Searchform from Header', 'interface'),
		'section'				=> 'custom_header_setting',
		'settings'				=> 'interface_theme_options[hide_header_searchform]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting( 'interface_theme_options[header_logo]',array(
		'sanitize_callback'	=> 'esc_url_raw',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
		$wp_customize,
		'header_logo',
			array(
				'label'				=> __('Header Logo','interface'),
				'section'			=> 'custom_header_setting',
				'settings'			=> 'interface_theme_options[header_logo]'
			)
		)
	);
	$wp_customize->add_setting('interface_theme_options[header_show]', array(
		'default'				=> 'header-text',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('custom_header_display', array(
		'label'					=> __('Show', 'interface'),
		'section'				=> 'custom_header_setting',
		'settings'				=> 'interface_theme_options[header_show]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
			'choices'			=> array(
			'header-logo'		=> __('Header Logo Only','interface'),
			'header-text'		=> __('Header Text Only','interface'),
			'disable-both'		=> __('Disable','interface'),
			),
	));
	/********************Fav Icon ******************************************/
	$wp_customize->add_section('fav_icon_setting', array(
		'title'					=> __('Fav Icon Options', 'interface'),
		'priority'				=> 210,
		'panel'					=>'interface_design_options_panel',
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_favicon]', array(
		'default'				=> 1,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
		$wp_customize->add_control( 'fav_icon_setting', array(
		'label'					=> __('Disable Favicon', 'interface'),
		'section'				=> 'fav_icon_setting',
		'settings'				=> 'interface_theme_options[disable_favicon]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting( 'interface_theme_options[favicon]',array(
		'sanitize_callback'	=> 'esc_url_raw',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
		$wp_customize,
		'favicon',
			array(
				'section'			=> 'fav_icon_setting',
				'settings'			=> 'interface_theme_options[favicon]',
			)
		)
	);
	/********************Web Icon ******************************************/
	$wp_customize->add_section('webclip_icon_setting', array(
		'title'					=> __('Web Clip Icon Options', 'interface'),
		'priority'				=> 220,
		'panel'					=>'interface_design_options_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_webpageicon]', array(
		'default'				=> 1,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control( 'webclip_icon_setting', array(
		'label'					=> __('Disable Web Clip Icon', 'interface'),
		'section'				=> 'webclip_icon_setting',
		'settings'				=> 'interface_theme_options[disable_webpageicon]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting( 'interface_theme_options[webpageicon]',array(
		'sanitize_callback'=> 'esc_url_raw',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
		$wp_customize,
		'webpageicon',
			array(
				'section'			=> 'webclip_icon_setting',
				'settings'			=> 'interface_theme_options[webpageicon]'
			)
		)
	);
	/********************Custom Css ******************************************/
	$wp_customize->add_section( 'interface_custom_css', array(
		'title'					=> __('Custom CSS', 'interface'),
		'priority'				=> 250,
		'panel'					=>'interface_design_options_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[custom_css]', array(
		'default'				=> '',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options',
		'sanitize_callback'	=> 'wp_filter_nohtml_kses'
	));
	$wp_customize->add_control( 'custom_css', array(
		'label'					=> __('Enter your custom CSS styles.','interface'),
		'description'			=> __('This CSS will overwrite the CSS of style.css file.','interface'),
		'section'				=> 'interface_custom_css',
				'settings'				=> 'interface_theme_options[custom_css]',
				'type'					=> 'textarea'
	));

	/******************** Advanced Options ******************************************/
	/******************** Home Slogan Options ******************************************/
	$wp_customize->add_section('home_slogan_options', array(
		'title'					=> __('Home Slogan Options', 'interface'),
		'priority'				=> 410,
		'panel'					=>'interface_advanced_options_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_slogan]', array(
		'default'				=> 0,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control( 'disable_slogan', array(
		'label'					=> __('Disable Slogan Part', 'interface'),
		'section'				=> 'home_slogan_options',
		'settings'				=> 'interface_theme_options[disable_slogan]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting('interface_theme_options[slogan_position]', array(
		'default'				=> 'above-slider',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('slogan_position', array(
		'label'					=> __('Slogan Position', 'interface'),
		'section'				=> 'home_slogan_options',
		'settings'				=> 'interface_theme_options[slogan_position]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
		'choices'				=> array(
			'above-slider'					=> __('Above Slider','interface'),
			'below-slider'					=> __('Below Slider','interface'),
		),
	));
	$wp_customize->add_setting( 'interface_theme_options[home_slogan1]', array(
		'default'				=> '',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options',
		'sanitize_callback'	=> 'esc_textarea'
	));
	$wp_customize->add_control( 'home_slogan1', array(
		'label'					=> __('Home Page Primary Slogan', 'interface'),
		'description'			=> __('TThe appropriate length of the slogan is around 10 words.','interface'),
		'section'				=> 'home_slogan_options',
		'settings'				=> 'interface_theme_options[home_slogan1]',
		'type'					=> 'textarea'
	));
	$wp_customize->add_setting( 'interface_theme_options[home_slogan2]', array(
		'default'				=> '',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options',
		'sanitize_callback'	=> 'esc_textarea'
	));
	$wp_customize->add_control( 'home_slogan2', array(
		'label'					=> __('Home Page Secondary Slogan', 'interface'),
		'description'			=> __('The appropriate length of the slogan is around 10 words.','interface'),
		'section'				=> 'home_slogan_options',
		'settings'				=> 'interface_theme_options[home_slogan2]',
		'type'					=> 'textarea'
	));
	/******************** Homepage Blog Category Setting *********************/
	$wp_customize->add_section(
		'interface_category_section', array(
		'title' 						=> __('Homepage Blog Category Setting','interface'),
		'description'				=> __('Only posts that belong to the categories selected here will be displayed on the front page. ( You may select multiple categories by holding down the CTRL key. ) ','interface'),
		'priority'					=> 420,
		'panel'					=>'interface_advanced_options_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[front_page_category]', array(
		'default'					=>array(),
		'sanitize_callback'		=> 'prefix_sanitize_integer',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control(
		new Interface_Customize_Category_Control(
		$wp_customize,
			'front_page_category',
			array(
			'label'					=> __('Front page posts categories','interface'),
			'section'				=> 'interface_category_section',
			'settings'				=> 'interface_theme_options[front_page_category]',
			'type'					=> 'multiple-select',
			)
		)
	);

	/********************Featured Post/ Page Slider******************************************/
	/********************Slider Options ******************************************************/
		$wp_customize->add_section( 'interface_featured_content_setting', array(
		'title'					=> __('Slider Options', 'interface'),
		'priority'				=> 500,
		'panel'					=>'interface_featured_post_page_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_slider]', array(
		'default'					=> 0,
		'sanitize_callback'		=> 'prefix_sanitize_integer',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control( 'interface_disable_slider', array(
		'priority'					=>510,
		'label'						=> __('Disable Slider', 'interface'),
		'section'					=> 'interface_featured_content_setting',
		'settings'					=> 'interface_theme_options[disable_slider]',
		'type'						=> 'checkbox',
	));
	$wp_customize->add_setting('interface_theme_options[slider_content]', array(
		'default'				=> 'on',
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control('interface_slider_content', array(
		'priority'				=> 520,
		'label'					=> __('Slider Content','interface'),
		'section'				=> 'interface_featured_content_setting',
		'settings'				=> 'interface_theme_options[slider_content]',
		'type'					=> 'radio',
		'checked'				=> 'checked',
		'choices'				=> array(
			'on'					=> __('ON (Slider Content will be displayed)','interface'),
			'off'					=> __('OFF (Slider Content will not be displayed)','interface'),
		),
	));
	$wp_customize->add_setting('interface_theme_options[slider_quantity]', array(
		'default'					=> '4',
		'sanitize_callback'		=> 'interface_sanitize_delay_transition',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('slider_quantity', array(
		'priority'					=>530,
		'label'						=> __('Number of Slides', 'interface'),
		'section'					=> 'interface_featured_content_setting',
		'settings'					=> 'interface_theme_options[slider_quantity]',
		'type'						=> 'text',
	) );
	$wp_customize->add_setting('interface_theme_options[transition_effect]', array(
		'default'					=> 'fade',
		'sanitize_callback'		=> 'interface_sanitize_effect',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('transition_effect', array(
		'priority'					=>540,
		'label'						=> __('Transition Effect', 'interface'),
		'section'					=> 'interface_featured_content_setting',
		'settings'					=> 'interface_theme_options[transition_effect]',
		'type'						=> 'select',
		'choices'					=> array(
			'fade'					=> __('Fade','interface'),
			'wipe'					=> __('Wipe','interface'),
			'scrollUp'				=> __('Scroll Up','interface' ),
			'scrollDown'			=> __('Scroll Down','interface' ),
			'scrollLeft'			=> __('Scroll Left','interface' ),
			'scrollRight'			=> __('Scroll Right','interface' ),
			'blindX'					=> __('Blind X','interface' ),
			'blindY'					=> __('Blind Y','interface' ),
			'blindZ'					=> __('Blind Z','interface' ),
			'cover'					=> __('Cover','interface' ),
			'shuffle'				=> __('Shuffle','interface' ),
		),
	));
	$wp_customize->add_setting('interface_theme_options[transition_delay]', array(
		'default'					=> '4',
		'sanitize_callback'		=> 'interface_sanitize_delay_transition',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('transition_delay', array(
		'priority'					=>550,
		'label'						=> __('Transition Delay', 'interface'),
		'section'					=> 'interface_featured_content_setting',
		'settings'					=> 'interface_theme_options[transition_delay]',
		'type'						=> 'text',
	) );
	$wp_customize->add_setting('interface_theme_options[transition_duration]', array(
		'default'					=> '1',
		'sanitize_callback'		=> 'interface_sanitize_delay_transition',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('transition_duration', array(
		'priority'					=>560,
		'label'						=> __('Transition Length', 'interface'),
		'section'					=> 'interface_featured_content_setting',
		'settings'					=> 'interface_theme_options[transition_duration]',
		'type'						=> 'text',
	) );
	/******************** Featured Post/ Page Slider Options  *********************************************/
		$wp_customize->add_section( 'interface_page_post_options', array(
			'title' 						=> __('Featured Post/ Page Slider Options','interface'),
			'priority'					=> 570,
			'panel'					=>'interface_featured_post_page_panel'
		));
		$wp_customize->add_setting('interface_theme_options[exclude_slider_post]', array(
			'default'					=>0,
			'sanitize_callback'		=>'prefix_sanitize_integer',
			'type' 						=> 'option',
			'capability' 				=> 'manage_options'
		));
		$wp_customize->add_control( 'exclude_slider_post', array(
			'priority'					=>580,
			'label'						=> __('Check to exclude', 'interface'),
			'description'				=>__('Exclude Slider post from Homepage posts?','interface'),
			'section'					=> 'interface_page_post_options',
			'settings'					=> 'interface_theme_options[exclude_slider_post]',
			'type'						=> 'checkbox',
		));
		// featured post/page
		for ( $i=1; $i <= $options['slider_quantity'] ; $i++ ) {
			$wp_customize->add_setting('interface_theme_options[featured_post_slider]['. $i.']', array(
				'default'					=>'',
				'sanitize_callback'		=>'prefix_sanitize_integer',
				'type' 						=> 'option',
				'capability' 				=> 'manage_options'
			));
			$wp_customize->add_control( 'featured_post_slider]['. $i .']', array(
				'priority'					=> 590 . $i,
				'label'						=> __(' Featured Slider Post/Page #', 'interface') . ' ' . $i ,
				'section'					=> 'interface_page_post_options',
				'settings'					=> 'interface_theme_options[featured_post_slider]['. $i .']',
				'type'						=> 'text',
			));
		}
	/******************** Contact / Social Links  *****************************************/
	/******************** Contact Info Bar ******************************************************/
	$wp_customize->add_section('contact_info_bar', array(
		'title'					=> __('Contact Info Bar', 'interface'),
		'priority'				=> 610,
		'panel'					=>'interface_contact_social_panel'
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_top]', array(
		'default'				=> 0,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control( 'disable_top', array(
		'priority'				=> 620,
		'label'					=> __('Disable Top Info Bar', 'interface'),
		'section'				=> 'contact_info_bar',
		'settings'				=> 'interface_theme_options[disable_top]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting( 'interface_theme_options[disable_bottom]', array(
		'default'				=> 0,
		'sanitize_callback'	=> 'prefix_sanitize_integer',
		'type' 					=> 'option',
		'capability' 			=> 'manage_options'
	));
	$wp_customize->add_control( 'disable_bottom', array(
		'priority'				=> 630,
		'label'					=> __('Disable Bottom Info Bar', 'interface'),
		'section'				=> 'contact_info_bar',
		'settings'				=> 'interface_theme_options[disable_bottom]',
		'type'					=> 'checkbox',
	));
	$wp_customize->add_setting('interface_theme_options[social_phone]', array(
		'default'					=>'',
		'sanitize_callback'		=> 'prefix_sanitize_phone',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('social_phone', array(
		'priority'					=>640,
		'label'						=> __('Phone Number', 'interface'),
		'description'				=> __('Enter your Phone number only','interface'),
		'section'					=> 'contact_info_bar',
		'settings'					=> 'interface_theme_options[social_phone]',
		'type'						=> 'text',
	));
	$wp_customize->add_setting('interface_theme_options[social_email]', array(
		'default'					=>'',
		'sanitize_callback'		=> 'sanitize_email',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('social_email', array(
		'priority'					=>650,
		'label'						=> __('Email ID Only', 'interface'),
		'description'				=> __('Enter your Email ID','interface'),
		'section'					=> 'contact_info_bar',
		'settings'					=> 'interface_theme_options[social_email]',
		'type'						=> 'text',
	));
	$wp_customize->add_setting('interface_theme_options[social_location]', array(
		'default'					=>'',
		'sanitize_callback'		=> 'sanitize_text_field',
		'type' 						=> 'option',
		'capability' 				=> 'manage_options'
	));
	$wp_customize->add_control('social_location', array(
		'priority'					=>660,
		'label'						=> __('Location Only', 'interface'),
		'description'				=> __('Enter your Address','interface'),
		'section'					=> 'contact_info_bar',
		'settings'					=> 'interface_theme_options[social_location]',
		'type'						=> 'text',
	));
	/******************** Social Links ******************************************/
	$wp_customize->add_section(
		'interface_sociallinks_section', array(
		'title' 						=> __('Social Links','interface'),
		'priority'					=> 670,
		'panel'					=>'interface_contact_social_panel'
	));
	$social_links = array(); 
		$social_links_name = array();
		$social_links_name = array( __( 'Facebook', 'interface' ),
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
										'Linked'			=> 'social_linkedin',
										'Flickr'			=> 'social_flickr',
										'Tumblr'			=> 'social_tumblr',
										'RSS'				=> 'social_rss' 
									);
		$i = 0;
		foreach( $social_links as $key => $value ) {
			$wp_customize->add_setting( 'interface_theme_options['. $value. ']', array(
				'default'					=>'',
				'sanitize_callback'		=> 'esc_url',
				'type' 						=> 'option',
				'capability' 				=> 'manage_options'
			));
			$wp_customize->add_control( $value, array(
					'label'					=> $social_links_name[ $i ],
					'section'				=> 'interface_sociallinks_section',
					'settings'				=> 'interface_theme_options['. $value. ']',
					'type'					=> 'text',
					)
			);
			$i++;
		}
}
/********************Sanitize the values ******************************************/
function prefix_sanitize_integer( $input ) {
	return $input;
}
function interface_sanitize_effect( $input ) {
	if ( ! in_array( $input, array( 'fade', 'wipe', 'scrollUp', 'scrollDown', 'scrollLeft', 'scrollRight', 'blindX', 'blindY', 'blindZ', 'cover', 'shuffle' ) ) ) {
		$input = 'fade';
	}
	return $input;
}
function interface_sanitize_delay_transition( $input ) {
	if(is_numeric($input)){
	return $input;
	}
}
function prefix_sanitize_phone( $input ) {
	$input =  preg_replace("/[^() 0-9+-]/", '', $input);
	return $input;
}
function customize_styles_interface_upgrade( $input ) { ?>
	<style type="text/css">
		#customize-theme-controls #accordion-section-interface_upgrade_to_pro .accordion-section-title:after {
			color: #fff;
		}
		#customize-theme-controls #accordion-section-interface_upgrade_to_pro .accordion-section-title {
			background-color: rgba(74, 137, 195, 0.9);
			color: #fff;
			border: 0 none;
		}
		#customize-theme-controls #accordion-section-interface_upgrade_to_pro .accordion-section-title:hover {
			background-color: rgba(74, 137, 195, 1);
		}
		#customize-theme-controls #accordion-section-interface_upgrade a {
			padding: 5px 0;
			display: block;
		}
		#customize-theme-controls #accordion-section-interface_upgrade_to_pro a {
			color: rgba(74, 137, 195, 1);
		}
		#customize-theme-controls #accordion-section-interface_upgrade_to_pro a:hover {
			text-decoration: underline;
		}
	</style>
<?php }
function interface_upgrade_notice() {
	// Enqueue the script
	wp_enqueue_script(
		'interface-upgrade-pro',
		get_template_directory_uri() . '/inc/admin/js/interface_customizer.js',
		array(), '3.0',
		true
	);
	// Localize the script
	wp_localize_script(
		'interface-upgrade-pro',
		'interfaceproupgrade',
		array(
			'interfaceprourl'		=> esc_url( 'http://themehorse.com/themes/interface-pro/' ),
			'interfaceprolabel'	=> __( 'Upgrade to Interface Pro', 'interface' ),
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'interface_upgrade_notice' );
add_action('customize_register', 'interface_textarea_register');
add_action('customize_register', 'interface_customize_register');
add_action( 'customize_controls_print_styles', 'customize_styles_interface_upgrade');
?>
