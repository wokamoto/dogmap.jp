<?php
/*
Plugin Name: RSS Footer
Version: 0.9.8
Plugin URI: http://yoast.com/wordpress/rss-footer/
Description: Allows you to add a line of content to the end of your RSS feed articles.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

if ( ! class_exists( 'RSSFooter_Admin' ) ) {

	require_once('yst_plugin_tools.php');

	class RSSFooter_Admin extends Yoast_Plugin_Admin {
		
		var $hook 		= 'rss-footer';
		var $longname	= 'RSS Footer Configuration';
		var $shortname	= 'RSS Footer';
		var $filename	= 'rss-footer/rss-footer.php';
		var $ozhicon	= 'feed_edit.png';
		
		function config_page() {
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the RSS Footer options.', 'rss-footer'));
				check_admin_referer('rssfooter-config');

				foreach( array('footerstring', 'position') as $val ) {
					if (isset($_POST[$val]) && $_POST[$val] != "") 
						$options[$val] 	= $_POST[$val];					
				}
				
				$options['everset'] = 2;
				
				update_option('RSSFooterOptions', $options);
			}
			
			$options  = get_option('RSSFooterOptions');
			
			?>
			<div class="wrap">
				<a href="http://yoast.com/"><div id="yoast-icon" style="background: url(http://cdn.yoast.com/theme/yoast-32x32.png) no-repeat;" class="icon32"><br /></div></a>
				<h2><?php _e("RSS Footer options", 'rss-footer'); ?></h2>
				<div class="postbox-container" style="width:70%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
							<form action="" method="post" id="rssfooter-conf">
							<?php
							if ( function_exists('wp_nonce_field') )
								wp_nonce_field('rssfooter-config');
								
							$rows = array();
							$rows[] = array(
								"id" => "footerstring",
								"label" => __("Content to put in the footer", 'rss-footer'),
								"desc" => __("(HTML allowed)", 'rss-footer'),
								"content" => '<textarea cols="50" onchange="javascript:updatePreview();" rows="10" id="footerstring" name="footerstring">'.stripslashes(htmlentities($options['footerstring'], ENT_QUOTES, get_bloginfo('charset'))).'</textarea>',
							);
							$rows[] = array(
								"label" => __('Explanation', 'rss-footer'),
								"content" => '<p>'.__('You can use the following variables within the content, they will be replaced by the value on the right.', 'rss-footer').'</p>'.
								'<ul>'.
								'<li><strong>%%POSTLINK%%</strong> : '.__('A link to the post, with the title as anchor text.', 'rss-footer').'</li>'.
								'<li><strong>%%BLOGLINK%%</strong> : '.__("A link to your site, with your site's name as anchor text.", 'rss-footer').'</li>'.
								'<li><strong>%%BLOGDESCLINK%%</strong> : '.__("A link to your site, with your site's name and description as anchor text.", 'rss-footer').'</li>'.
								'</ul>'.
								'<p>'.__('If you update the text above, check the preview below:', 'rss-footer').' </p>',
							);
							$this->postbox('rssfootercontent',__('Content of your RSS Footer', 'rss-footer'),$this->form_table($rows));
							$this->postbox('rssfooterpreview',__('Preview of your RSS Footer', 'rss-footer'),'<div id="preview">'.__('You need JavaScript enabled for the preview to work.', 'rss-footer').'</div><script type="text/javascript" charset="utf-8">
								function nl2br(str) {
									return (str + \'\').replace(/([^>]?)\n/g, \'$1\'+ \'<br/>\' +\'\n\');
								}
								jQuery("#footerstring").change( function() {
									var text = jQuery("#footerstring").val();
									text = text.replace("%%POSTLINK%%","<a href=#>Test post</a>");
									text = text.replace("%%BLOGLINK%%","<a href=\''.get_bloginfo('url').'\'>'.get_bloginfo('name').'</a>");
									text = text.replace("%%BLOGDESCLINK%%","<a href=\''.get_bloginfo('url').'\'>'.get_bloginfo('name').' - '.get_bloginfo('description').'</a>");
									jQuery("#preview").html(nl2br(text));									
								}).change();
							</script>');
							$rows = array();
							$rows[] = array(
								"id" => "position",
								"label" => __("Content position", 'rss-footer'),
								"content" => '<select name="position" id="position">
									<option value="after" '.selected($options['position'],"after",false).'>'.__('after', 'rss-footer').'</option>
									<option value="before" '.selected($options['position'],"before",false).'>'.__('before', 'rss-footer').'</option>
								</select>',
							);
							$rows[] = array(
								"label" => __('Explanation', 'rss-footer'),
								"content" => __("The position determines whether the content you've entered above will appear below or above the post.", 'rss-footer')
							);
							$this->postbox('rssfootersettings','Settings',$this->form_table($rows));
							?>
							<div class="submit">
								<input type="submit" class="button-primary" name="submit" value="<?php _e('Update RSS Footer Settings', 'rss-footer') ?> &raquo;" />
							</div>
							</form>
						</div>
					</div>
				</div>
				<div class="postbox-container" style="width:20%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
							<?php
								$this->plugin_like();
								$this->plugin_support();
								$this->news(); 
							?>
						</div>
						<br/><br/><br/>
					</div>
				</div>
			</div>
<?php		}	
	}
	$rssfoot = new RSSFooter_Admin();
}

$options  = get_option('RSSFooterOptions');
if (!isset($options['everset'])) {
	// Set default values
	$options['footerstring'] = "%%POSTLINK%% ".__("is a post from", 'rss-footer').": <a href=\"".get_bloginfo('url')."\">".get_bloginfo('name')."</a>";
	$options['position'] = "after";
	update_option('RSSFooterOptions', $options);
} elseif ($options['everset'] === true) {
	if ($options['position'] == "after") {
		$options['footerstring'] = $options['footerstring']."<br/><br/>%%POSTLINK%%";
	} else {
		$options['footerstring'] = "%%POSTLINK%%<br/><br/>".$options['footerstring'];
	}
	$options['everset'] = 2;
	update_option('RSSFooterOptions', $options);
} 

function embed_rssfooter($content) {
	if(is_feed()) {
		$options  = get_option('RSSFooterOptions');

		$postlink = '<a href="'.get_permalink().'">'.get_the_title()."</a>";
		$bloglink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a>';
		$blogdesclink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').' - '.get_bloginfo('description').'</a>';
		
		$rssfootcontent = stripslashes($options['footerstring']);
		$rssfootcontent = str_replace("%%POSTLINK%%",$postlink,$rssfootcontent);
		$rssfootcontent = str_replace("%%BLOGLINK%%",$bloglink,$rssfootcontent);		
		$rssfootcontent = str_replace("%%BLOGDESCLINK%%",$blogdesclink,$rssfootcontent);		
		
		if ($options['position'] == "before") {
			$content = "<p>" . $rssfootcontent . "</p>\n" . $content;
		} else {
			$content .= "<p>" . $rssfootcontent . "</p>\n";
		}
	}
	return $content;
}

add_filter('the_content', 'embed_rssfooter');
add_filter('the_excerpt_rss', 'embed_rssfooter');

?>