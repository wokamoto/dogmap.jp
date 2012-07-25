<?php

/* ==================================================
 *   KtaiStyle_Config class
   ================================================== */

class KtaiStyle_Config {
	const OPTIONS        = 'ktai_style';
	const THEME_OPTIONS  = 'ktai_style_theme';
	const COLOR_OPTIONS  = 'ktai_style_color';
	const DELETE_OPTIONS = 'ktai_style_delete';
	const LINK_COLOR_URL_EXAMPLE = 'http://wppluginsj.sourceforge.jp/';
	const THEMES_PER_PAGE = 6;
	const THEME_COLS = 3;

public function __construct() {
	add_action('admin_menu',  array($this, 'add_menu'));
	add_action('admin_init', array($this, 'add_comment_meta'));
	add_filter('plugin_action_links', array($this, 'add_link'), 10, 2);
	if ( $_GET['page'] == self::THEME_OPTIONS ) {
		remove_action('setup_theme', 'preview_theme');
		add_action('setup_theme', array('KtaiThemes', 'preview_theme'));
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function add_link($links, $file) {
	global $Ktai_Style;
	if ( $file == $Ktai_Style->get('plugin_basename') ) {
		array_unshift($links, '<a href="' . admin_url('admin.php?page=' . self::THEME_OPTIONS) . '">' . __('Themes') . '</a>');
		array_unshift($links, '<a href="' . admin_url('admin.php?page=' . self::OPTIONS) . '">' . __('Settings') . '</a>');
	}
	return $links;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function add_menu() {
	global $Ktai_Style;
	add_menu_page(__('Ktai Style Configuration', 'ktai_style'), __('Mobile Output', 'ktai_style'), 'manage_options', self::OPTIONS, array($this, 'misc_page'), $Ktai_Style->get('plugin_url') . KtaiStyle::CONFIG_DIR . '/icon16.png');
	add_submenu_page(self::OPTIONS, __('Ktai Style Configuration', 'ktai_style'), __('Behavior', 'ktai_style'), 'manage_options', self::OPTIONS, array($this, 'misc_page'));
	add_submenu_page(self::OPTIONS, __('Theme for mobile', 'ktai_style'), __('Theme', 'ktai_style'), 'switch_themes', self::THEME_OPTIONS, array($this, 'theme_page'));
	add_action('load-' . get_plugin_page_hookname(self::THEME_OPTIONS, ''), array($this, 'theme_page_header'));
	add_action('load-' . get_plugin_page_hookname(self::THEME_OPTIONS, ''), array($this, 'update_common_theme'));
	add_submenu_page(self::OPTIONS, __('Mobile Theme Configuration', 'ktai_style'), __('Color &amp; Format', 'ktai_style'), 'manage_options', self::COLOR_OPTIONS, array($this, 'color_page'));
	add_submenu_page(self::OPTIONS, __('Delete Configuration', 'ktai_style'), __('Delete Options', 'ktai_style'), 'manage_options', self::DELETE_OPTIONS, array($this, 'delete_page'));
	add_action('admin_print_styles', array($this, 'icon_style'));
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function icon_style() {
	global $Ktai_Style;
?>
<link rel="stylesheet" type="text/css" id="ktai-style" href="<?php echo $Ktai_Style->get('plugin_url') . KtaiStyle::CONFIG_DIR; ?>/config.css" />
<?php
}

/* ==================================================
 * @param	none
 * @return	none
 * @sinde   0.??
 */
public function misc_page() {
	global $Ktai_Style;

	register_setting(self::OPTIONS, 'ks_allow_pictograms');
	register_setting(self::OPTIONS, 'ks_separate_comments');
	register_setting(self::OPTIONS, 'ks_require_term_id');
	register_setting(self::OPTIONS, 'ks_treat_as_internal');

	if (isset($_POST['update_option'])) {
		check_admin_referer(self::OPTIONS);
		$this->update_options();
		?>
<div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>
<?php
	}
	$allow_pictograms  = $this->set_radio_button($Ktai_Style->get_option('ks_allow_pictograms'));
	$separate_comments = $this->set_radio_button($Ktai_Style->get_option('ks_separate_comments'));
	$require_term_id   = $this->set_radio_button($Ktai_Style->get_option('ks_require_term_id'));
	$treat_as_internal = $Ktai_Style->get_option('ks_treat_as_internal');
?>
<div class="wrap">
<div id="icon-ktaistyle" class="icon32"><br /></div>
<h2><?php _e('Ktai Style Configuration', 'ktai_style'); ?></h2>
<form name="form" method="post" action="">
<input type="hidden" name="action" value="update" />
<?php wp_nonce_field(self::OPTIONS); ?>
<h3 id="design"><?php _e('Behavior', 'ktai_style'); ?></h3>
<table class="form-table"><tbody>
<tr>
<th><label for="allow_pictograms"><?php _e('Typing pictograms at comment forms, post contents', 'ktai_style'); ?></label></th> 
<td>
  <label><input type="radio" name="ks_allow_pictograms" id="ks_allow_pictograms-0" value="0"<?php echo $allow_pictograms[0]; ?> /> <?php _e('Deny', 'ktai_style'); ?></label><br />
  <label><input type="radio" name="ks_allow_pictograms" id="ks_allow_pictograms-1" value="1"<?php echo $allow_pictograms[1]; ?> /> <?php _e('Allow', 'ktai_style'); ?></label>
</td>
</tr><tr>
<th><label for="ks_separate_comments"><?php _e('Comments and Pings', 'ktai_style'); ?></label></th> 
<td>
  <label><input type="radio" name="ks_separate_comments" id="ks_separate_comments-0" value="0"<?php echo $separate_comments[0]; ?> /> <?php _e('Mix them at each posts and/or recent comments.', 'ktai_style'); ?></label><br />
  <label><input type="radio" name="ks_separate_comments" id="ks_separate_comments-1" value="1"<?php echo $separate_comments[1]; ?> /> <?php _e('Separate comments and pings.', 'ktai_style'); ?></label>
</td>
</tr><tr>
<th><label for="ks_require_term_id"><?php _e('Terminal ID of comment poster', 'ktai_style'); ?></label></th> 
<td>
  <label><input type="radio" name="ks_require_term_id" id="ks_require_term_id-0" value="0"<?php echo $require_term_id[0]; ?> /> <?php _e('Not required', 'ktai_style'); ?></label><br />
  <label><input type="radio" name="ks_require_term_id" id="ks_require_term_id-1" value="1"<?php echo $require_term_id[1]; ?> /> <?php _e('Required to send', 'ktai_style'); ?></label><br />
  <small><?php _e('Note: Terminal ID is sensitive private information because the same ID is sent to any mobile sites.<br />Collecting the IDs is a risk of keeping private information of others.<br />Please select "Not required" unless you can get much merit than that risk.', 'ktai_style'); ?></small>
</td>
</tr><tr>
<th><label for="ks_treat_as_internal"><?php _e('Websites to link directly', 'ktai_style'); ?></label></th>
<td><textarea name="ks_treat_as_internal" id="ks_treat_as_internal" cols="60" rows="2"><?php echo $treat_as_internal; ?></textarea><br /><?php _e('(Not using the redirect page for these sites; space separated)', 'ktai_style'); ?></td>
</tr>
</tbody></table>
<p class="submit">
<input type="submit" name="update_option" class="button-primary" value="<?php _e('Save Changes'); ?>" />
</p>
</form>
</div>
<?php
} 

/* ==================================================
 * @param	none
 * @return	none
 * @since   2.0.0
 */
public function theme_page_header() {
	add_thickbox();
	wp_enqueue_script( 'theme-preview' );
}

/* ==================================================
 * @param	none
 * @return	none
 * @sinde   2.0.0
 */
public function theme_page() {
	global $Ktai_Style;

	register_setting(self::THEME_OPTIONS, 'ks_theme');
	register_setting(self::THEME_OPTIONS, 'ks_theme_touch');
	register_setting(self::THEME_OPTIONS, 'ks_theme_mova');
	register_setting(self::THEME_OPTIONS, 'ks_theme_foma');
	register_setting(self::THEME_OPTIONS, 'ks_theme_ezweb');
	register_setting(self::THEME_OPTIONS, 'ks_theme_sb_pdc');
	register_setting(self::THEME_OPTIONS, 'ks_theme_sb_3g');
	register_setting(self::THEME_OPTIONS, 'ks_theme_willcom');
	register_setting(self::THEME_OPTIONS, 'ks_theme_emobile');

	if (isset($_GET['activated'])) { ?>
<div class="updated fade"><p><strong><?php _e('New theme activated.', 'ktai_style'); ?></strong></p></div>
<?php
	} elseif (isset($_POST['update_option'])) {
		check_admin_referer(self::THEME_OPTIONS);
		$this->update_themes(); ?>
<div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>
<?php
	}
	$theme         = $Ktai_Style->get_option('ks_theme');
	$theme_touch   = $Ktai_Style->get_option('ks_theme_touch');
	$theme_mova    = $Ktai_Style->get_option('ks_theme_mova');
	$theme_foma    = $Ktai_Style->get_option('ks_theme_foma');
	$theme_ezweb   = $Ktai_Style->get_option('ks_theme_ezweb');
	$theme_sb_pdc  = $Ktai_Style->get_option('ks_theme_sb_pdc');
	$theme_sb_3g   = $Ktai_Style->get_option('ks_theme_sb_3g');
	$theme_willcom = $Ktai_Style->get_option('ks_theme_willcom');
	$theme_emobile = $Ktai_Style->get_option('ks_theme_emobile');
	$paged_themes = $themes = KtaiThemes::installed(); // assume included inc/themes.php at init_pc()
	$ct = new KtaiThemes;
	unset($paged_themes[$ct->get('theme')]);
	$theme_total = count($paged_themes);
	if (isset($_GET['pagenum'])) {
		$page = absint($_GET['pagenum']);
		if (empty($page)) {
			$page = 1;
		}
	} else {
		$page = 1;
	}
	$start = $offset = ( $page - 1 ) * self::THEMES_PER_PAGE;
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'pagenum', '%#%' ) . '#themenav',
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($theme_total / self::THEMES_PER_PAGE),
		'current' => $page
	));
	$paged_themes = array_slice($paged_themes, $start, self::THEMES_PER_PAGE);
?>
<div class="wrap">
<div id="icon-ktaistyle" class="icon32"><br /></div>
<h2><?php _e('Theme for mobile', 'ktai_style'); ?></h2>
<p><?php _e('Additonal themes are available at <a href="http://ks-theme.sourceforge.jp/">Ktai Style Themes</a> website.', 'ktai_style'); ?></p>
<h3><?php _e('Current Theme'); ?></h3>
<div id="current-theme">
<?php 
	$theme_data = $ct->get('theme_data');
	if (isset($theme_data['Screenshot'])) { ?>
<img src="<?php echo $theme_data['Screenshot']; ?>" alt="<?php _e('Current theme preview'); ?>" style="width:120px" />
<?php } ?>
<h4><?php
	printf(__('%1$s %2$s by %3$s', 'ktai_style'), $theme_data['Name'], $theme_data['Version'], $theme_data['Author']) ; ?></h4>
<p class="theme-description"><?php echo $theme_data['Description']; ?></p>
</div>
<p><?php printf(__('All of this theme&#8217;s files are located in <code>%2$s</code>.'), $theme_data['Name'], str_replace( WP_CONTENT_DIR, '', $ct->get('template_dir') )); ?></p>
<div class="clear"></div>
<h3><?php _e('Available Themes'); ?></h3>
<?php if ($theme_total) {

if ( $page_links ) : ?>
<div class="tablenav">
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'ktai_style' ) . '</span>%s',
	number_format_i18n( $start + 1 ),
	number_format_i18n( min( $page * self::THEMES_PER_PAGE, $theme_total ) ),
	number_format_i18n( $theme_total ),
	$page_links
); echo $page_links_text; ?></div>
</div>
<?php endif; ?>

<table id="availablethemes" cellspacing="0" cellpadding="0">
<?php
$style = '';

$theme_names = array_keys($paged_themes);
natcasesort($theme_names);

$table = array();
$rows = ceil(count($theme_names) / self::THEME_COLS);
for ( $row = 1; $row <= $rows; $row++ )
	for ( $col = 1; $col <= self::THEME_COLS; $col++ )
		$table[$row][$col] = array_shift($theme_names);

foreach ( $table as $row => $cols ) {
?>
<tr>
<?php
foreach ( $cols as $col => $theme_name ) {
	$class = array('available-theme');
	if ( $row == 1 ) $class[] = 'top';
	if ( $col == 1 ) $class[] = 'left';
	if ( $row == $rows ) $class[] = 'bottom';
	if ( $col == 3 ) $class[] = 'right';
	$t = new KtaiThemes($theme_name);
	$theme_data = $t->get('theme_data');
?>
	<td class="<?php echo join(' ', $class); ?>">
<?php if ( !empty($theme_name) ) :
	$title = $theme_data['Name'];
	$version = $theme_data['Version'];
	$description = $theme_data['Description'];
	$author = $theme_data['Author'];
	$screenshot = $theme_data['Screenshot'];
	$template_dir = $t->get('template_dir');
	$theme_root =$theme_data['Theme Root'];
	$theme_root_uri = $$theme_data['Theme Root URI'];
	$preview_link = esc_attr(get_option('home') . '/');
	if ( is_ssl() ) {
		$preview_link = str_replace( 'http://', 'https://', $preview_link );
	}
	$preview_link = htmlspecialchars( add_query_arg( array(
		'preview' => 1,
		'mobile' => 1,
		'template' => urlencode($theme_name),
		'_wpnonce' => $Ktai_Style->create_anon_nonce('switch-theme_' . $theme_name),
		'TB_iframe' => 'true',
	), $preview_link ) );
	$preview_text = esc_attr( sprintf( __('Preview of &#8220;%s&#8221;'), $title ) );
	$thickbox_class = 'thickbox thickbox-preview';

	$activate_link = wp_nonce_url(sprintf('admin.php?page=%1$s&amp;pagenum=%2$d&amp;action=activate&amp;template=%3$s', self::THEME_OPTIONS, $page, urlencode($theme_name)), 'switch-theme_' . $theme_name);
	$activate_text = esc_attr( sprintf( __('Activate &#8220;%s&#8221;'), $title ) );
	$actions = array();
	$actions[] = '<a href="' . $activate_link .  '" class="activatelink" title="' . $activate_text . '">' . __('Activate') . '</a>';
	$actions[] = '<a href="' . $preview_link . '" class="thickbox thickbox-preview" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $theme_name)) . '">' . __('Preview') . '</a>';
	$actions = apply_filters('theme_action_links', $actions, $themes[$theme_name]);
	$actions = implode ( ' | ', $actions );
	if ( $screenshot ) { ?>
		<a href="<?php echo $preview_link; ?>" class="<?php echo $thickbox_class; ?> screenshot" style="width:180px;height:450px;"><img src="<?php echo $screenshot; ?>" alt="" style="width:180px;height:450px;" /></a>
	<?php } ?>
<h3><?php
	/* translators: 1: theme title, 2: theme version, 3: theme author */
	printf(__('%1$s %2$s by %3$s', 'ktai_style'), $title, $version, $author) ; ?></h3>
<p class="description"><?php echo $description; ?></p>
<span class='action-links'><?php echo $actions ?></span>
	<p><?php printf(__('All of this theme&#8217;s files are located in <code>%2$s</code>.'), $title, str_replace( WP_CONTENT_DIR, '', $template_dir ), str_replace( WP_CONTENT_DIR, '', $stylesheet_dir ) ); ?></p>
<?php endif; // end if not empty theme_name ?>
	</td>
<?php } // end foreach $cols ?>
</tr>
<?php } // end foreach $table ?>
</table>

<?php } else { ?>
<p><?php _e('You only have one theme installed at the moment so there is nothing to show you here.  Maybe you should download some more to try out.'); ?></p>
<?php } // end if $theme_total?>
<br class="clear" />
<?php if ( $page_links ) : ?>
<div class="tablenav">
<?php echo "<div class='tablenav-pages'>$page_links_text</div>"; ?>
<br class="clear" />
</div>
<?php endif; ?>
<h3><?php _e('Theme for each career', 'ktai_style'); ?></h3>
<form name="form" method="post" action="<?php echo sprintf('admin.php?page=%s', self::THEME_OPTIONS) . ($page > 1 ? ('&pagenum=' . $page) : ''); ?>">
<input type="hidden" name="action" value="update" />
<?php wp_nonce_field(self::THEME_OPTIONS); ?>
<table class="form-table"><tbody>
<?php
	$careers[]  = array('label' => 'theme_touch', 
	                    'desc'  => __('For iPhone, Android', 'ktai_style'));
	$careers[]  = array('label' => 'theme_mova', 
	                    'desc'  => __('For mova (docomo)', 'ktai_style'));
	$careers[]  = array('label' => 'theme_foma', 
	                    'desc'  => __('For FOMA (docomo)', 'ktai_style'));
	$careers[]  = array('label' => 'theme_ezweb', 
	                    'desc'  => __('For EZweb (au)', 'ktai_style'));
	$careers[]  = array('label' => 'theme_sb_pdc', 
	                    'desc'  => __('For SoftBank PDC', 'ktai_style'));
	$careers[]  = array('label' => 'theme_sb_3g', 
	                    'desc'  => __('For SoftBank 3G', 'ktai_style'));
	$careers[]  = array('label' => 'theme_willcom', 
	                    'desc'  => __('For WILLCOM', 'ktai_style'));
	$careers[]  = array('label' => 'theme_emobile', 
	                    'desc'  => __('For EMobile Handset', 'ktai_style'));
	foreach ($careers as $index => $c) { ?>
<tr><th><label for="ks_<?php echo $c['label']; ?>"><?php echo $c['desc']; ?></label></th>
<td><select name="ks_<?php echo $c['label']; ?>" id="ks_<?php echo $c['label']; ?>">
<?php 
		if (strcmp($c['label'], 'theme_touch') === 0) {
			$current = empty(${$c['label']}) ? ' selected="selected"' : '';
			echo '<option value="0"' . $current . '>' . __("(Don't use Ktai Style)", 'ktai_style') . '</option>';
			?><option value="<?php echo KtaiThemes::SAME_THEME_AS_COMMON; ?>"<?php selected(${$c['label']}, KtaiThemes::SAME_THEME_AS_COMMON); ?>><?php _e('(Same as common theme)', 'ktai_style'); ?></option><?php 
		} elseif (strcmp($c['label'], 'theme') !== 0) {
			$current = empty(${$c['label']}) ? ' selected="selected"' : '';
			echo '<option value="0"' . $current . '>' . __('(Same as common theme)', 'ktai_style') . '</option>';
		}
		foreach($themes as $dir => $name) {
			?><option value="<?php echo esc_attr($dir); ?>"<?php selected($dir, ${$c['label']}); ?>><?php echo esc_attr($name); ?></option><?php 
		} ?>
</select></td></tr>
	<?php } // foreach ?>
<tr><td colspan="2"><?php _e('Other phones (Windows Mobile, Palm, PSP, Nintendo DS, etc) are not able to select an exclusive theme.', 'ktai_style'); ?></td></tr>
</tbody></table>
<p class="submit">
<input type="submit" name="update_option" class="button-primary" value="<?php _e('Save Changes'); ?>" />
</p>
</form>
</div>
<?php
} 

/* ==================================================
 * @param	none
 * @return	none
 * @sinde   0.9x
 */
public function color_page() {
	global $Ktai_Style, $user_identity;

	register_setting(self::COLOR_OPTIONS, 'ks_date_color');
	register_setting(self::COLOR_OPTIONS, 'ks_author_color');
	register_setting(self::COLOR_OPTIONS, 'ks_comment_type_color');
	register_setting(self::COLOR_OPTIONS, 'ks_external_link_color');
	register_setting(self::COLOR_OPTIONS, 'ks_edit_color');
	register_setting(self::COLOR_OPTIONS, 'ks_year_format');
	register_setting(self::COLOR_OPTIONS, 'ks_month_date_format');
	register_setting(self::COLOR_OPTIONS, 'ks_time_format');

	if (isset($_POST['update_option'])) {
		check_admin_referer(self::COLOR_OPTIONS);
		$this->update_colors();
		?>
<div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>
<?php
	}
	$date_color          = $Ktai_Style->get_option('ks_date_color');
	$author_color        = $Ktai_Style->get_option('ks_author_color');
	$comment_type_color  = $Ktai_Style->get_option('ks_comment_type_color');
	$external_link_color = $Ktai_Style->get_option('ks_external_link_color');
	$edit_color          = $Ktai_Style->get_option('ks_edit_color');
	$year_format         = $Ktai_Style->get_option('ks_year_format');
	$month_date_format   = $Ktai_Style->get_option('ks_month_date_format');
	$time_format         = $Ktai_Style->get_option('ks_time_format');
?>
<div class="wrap">
<div id="icon-ktaistyle" class="icon32"><br /></div>
<h2><?php _e('Mobile Theme Configuration', 'ktai_style'); ?></h2>
<form name="form" method="post" action="">
<input type="hidden" name="action" value="update" />
<?php wp_nonce_field(self::COLOR_OPTIONS); ?>
<script type="text/javascript">
function change_sample(field, target) {
	var color = field.value;
	if (color.match("^(#[0-9a-fA-F]{6}|[a-z]+)$")) {
		document.getElementById(target).style.color = color;
	}
}
</script>
<p><?php _e('Note: Settings below may not be reflected with other than standard theme (default, compact).', 'ktai_style'); ?></p>
<h3 id="colors"><?php _e('Text Color', 'ktai_style'); ?></h3>
<p><?php _e('Note: To revert values to default, just empty of the field.', 'ktai_style'); ?></p>
<table class="form-table"><thead>
<tr><th><?php _e('Target', 'ktai_style'); ?></th><td><?php _e('#rrggbb as hex format', 'ktai_style'); ?></td><td><?php _e('Color sample', 'ktai_style'); ?></td></tr>
</thead><tbody>
<tr>
<th><label for="ks_date_color"><?php _e('Date/time for post titles', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo esc_attr($date_color); ?>" name="ks_date_color" id="ks_date_color" onkeyup='change_sample(this, "date_color_sample")' /></td>
<td id="date_color_sample" style="color:<?php echo esc_attr($date_color); ?>;"><?php echo date('Y-m-d H:i'); ?></td>
</tr><tr>
<th><label for="ks_author_color"><?php _e('Author, Date with a post content', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo esc_attr($author_color); ?>" name="ks_author_color" id="ks_author_color" onkeyup='change_sample(this, "author_color_sample")' /></td>
<td id="author_color_sample" style="color:<?php echo esc_attr($author_color); ?>;"><?php echo esc_html($user_identity); ?></td>
</tr><tr>
<th><label for="ks_comment_type_color"><?php _e('Comment types', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo esc_attr($comment_type_color); ?>" name="ks_comment_type_color" id="ks_comment_type_color" onkeyup='change_sample(this, "comment_type_color_sample")' /></td>
<td id="comment_type_color_sample" style="color:<?php echo esc_attr($comment_type_color); ?>;"><?php echo __('Comment', 'ktai_style'), '/', __('Trackback'), '/', __('Pingback'); ?></td>
</tr><tr>
<th><label for="ks_external_link_color"><?php _e('Link text for PC targeted external sites', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo esc_attr($external_link_color); ?>" name="ks_external_link_color" id="ks_external_link_color"  onkeyup='change_sample(this, "external_link_color_sample")' /></td>
<td id="external_link_color_sample" style="color:<?php echo esc_attr($external_link_color); ?>;"><?php echo self::LINK_COLOR_URL_EXAMPLE; ?></td>
</tr><tr>
<th><label for="ks_edit_color"><?php _e('Link of edit posts/pages/commetns for login user', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo esc_attr($edit_color); ?>" name="ks_edit_color" id="ks_edit_color"  onkeyup='change_sample(this, "edit_color_sample")' /></td>
<td id="edit_color_sample" style="color:<?php echo esc_attr($edit_color); ?>;"><?php _e('Edit This'); ?></td>
</tr>
</tbody></table>
<p><?php _e('Note: To configure background color/normal text color/hyperlink color/visited link color, edit &lt;body&gt; element at themes/*/header.php', 'ktai_style'); ?></p>
<h3 id="date_format"><?php _e('Date and time format of posts/comments', 'ktai_style'); ?></h3>
<table class="form-table"><tbody>
<tr>
<th><label for="ks_year_format"><?php _e('Datetime for last year and before<br />(year, month, and date required)', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo $year_format ?>" name="ks_year_format" id="year_format" /></td>
</tr><tr>
<th><label for="ks_month_date_format"><?php _e('Datetime for this year<br />(month and date required)', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo $month_date_format ?>" name="ks_month_date_format" id="ks_month_date_format" /></td>
</tr><tr>
<th><label for="ks_time_format"><?php _e('Datetime for today<br />(date or hour required)', 'ktai_style'); ?></label></th>
<td><input type="text" value="<?php echo $time_format ?>" name="ks_time_format" id="ks_time_format" /></td>
</tr>
</tbody></table>
<p><?php _e('Note: About date format, refer to <a href="http://codex.wordpress.org/Formatting_Date_and_Time">Codex</a> or <a href="http://www.php.net/date">date() function manual</a> of PHP.', 'ktai_style'); ?></p>
<p class="submit">
<input type="submit" name="update_option" class="button-primary" value="<?php _e('Save Changes'); ?>" />
</p>
</form>
</div>
<?php
} 

/* ==================================================
 * @param	none
 * @return	none
 * @sinde   2.0.0
 */
public function delete_page() {
	global $Ktai_Style;

	register_setting(self::DELETE_OPTIONS, 'ks_allow_pictograms');
	register_setting(self::DELETE_OPTIONS, 'ks_separate_comments');
	register_setting(self::DELETE_OPTIONS, 'ks_require_term_id');
	register_setting(self::DELETE_OPTIONS, 'ks_treat_as_internal');
	register_setting(self::DELETE_OPTIONS, 'ks_theme');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_touch');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_mova');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_foma');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_ezweb');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_sb_pdc');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_sb_3g');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_willcom');
	register_setting(self::DELETE_OPTIONS, 'ks_theme_emobile');
	register_setting(self::DELETE_OPTIONS, 'ks_date_color');
	register_setting(self::DELETE_OPTIONS, 'ks_author_color');
	register_setting(self::DELETE_OPTIONS, 'ks_comment_type_color');
	register_setting(self::DELETE_OPTIONS, 'ks_external_link_color');
	register_setting(self::DELETE_OPTIONS, 'ks_edit_color');
	register_setting(self::DELETE_OPTIONS, 'ks_year_format');
	register_setting(self::DELETE_OPTIONS, 'ks_month_date_format');
	register_setting(self::DELETE_OPTIONS, 'ks_time_format');

	if (isset($_POST['delete_option'])) {
		check_admin_referer(self::DELETE_OPTIONS);
		$this->delete_options();
        ?>
<div class="updated fade"><p><strong><?php _e('Options Deleted.', 'ktai_style'); ?></strong></p></div>
        <?php
	}
?>
<div class="wrap">
<div id="icon-ktaistyle" class="icon32"><br /></div>
<h2><?php _e('Delete Options', 'ktai_style'); ?></h2>
<form name="form" method="post" action="">
<input type="hidden" name="action" value="update" />
<?php wp_nonce_field(self::DELETE_OPTIONS); ?>
<p class="submit">
<input type="submit" name="delete_option" value="<?php _e('Delete option values and revert them to default &raquo;', 'ktai_style'); ?>" onclick="return confirm('<?php _e('Do you really delete option values and revert them to default?', 'ktai_style'); ?>')" />
</p>
</form>
</div>
<?php
} 

/* ==================================================
 * @param   boolean $flag
 * @return	array   $radio
 */
private function set_radio_button($flag) {
	if ($flag) {
		$radio[0] = '';
		$radio[1] = ' checked="checked"';
	} else {
		$radio[0] = ' checked="checked"';
		$radio[1] = '';
	}
	return $radio;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   0.9x
 */
private function update_options() {
	$this->update_boolean_option('ks_images_to_link');
	$this->update_boolean_option('ks_allow_pictograms');
	$this->update_boolean_option('ks_separate_comments');
	$this->update_boolean_option('ks_require_term_id');

	if (! empty($_POST['ks_treat_as_internal'])) {
		$sites = preg_split('/\\s+/', stripslashes($_POST['ks_treat_as_internal']), -1, PREG_SPLIT_NO_EMPTY);
		$sites = array_map('clean_url', $sites);
		$sites = preg_replace('#/$#', '', $sites);
		$sites_join = implode(' ', $sites);
		if (! preg_match('/^\\s*$/', $sites_join)) {
			update_option('ks_treat_as_internal', $sites_join);
		} else {
			delete_option('ks_treat_as_internal');
		}
	} else {
		delete_option('ks_treat_as_internal');
	}
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   2.0.0
 */
public function update_common_theme() {
	if (isset($_GET['action']) && isset($_GET['template']) && $_GET['action'] == 'activate') {
		$theme = stripslashes($_GET['template']);
		check_admin_referer('switch-theme_' . $theme);
		KtaiThemes::set_theme($theme);
		$redirect_to = sprintf('admin.php?page=%s&activated=true', self::THEME_OPTIONS);
		$page = absint($_GET['pagenum']);
		if ( $page > 1) {
			$redirect_to .= '&pagenum=' . $page;
		}
		wp_redirect($redirect_to);
		exit;
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   2.0.0
 */
private function update_themes() {
	$theme_opts = KtaiThemes::$target;
	foreach ($theme_opts as $t) {
		$option = KtaiThemes::OPTION_PREFIX . '_' . $t;
		$value = isset($_POST[$option]) ? stripslashes($_POST[$option]) : NULL;
		KtaiThemes::set_theme($value, $t);
	}
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   2.0.0
 */
private function update_colors() {
	$this->update_hex_option('ks_author_color');
	$this->update_hex_option('ks_date_color');
	$this->update_hex_option('ks_comment_type_color');
	$this->update_hex_option('ks_external_link_color');
	$this->update_hex_option('ks_edit_color');
	if (! empty($_POST['ks_year_format'])) {
		if (preg_match('/[Yyo]/', $_POST['ks_year_format']) && preg_match('/[mnMF]/', $_POST['ks_year_format']) && preg_match('/[djz]/', $_POST['ks_year_format'])) {
			update_option('ks_year_format', stripslashes($_POST['ks_year_format']));
		}
	} else {
		delete_option('ks_year_format');
	}

	if (! empty($_POST['ks_month_date_format'])) {
		if (preg_match('/[mnMF]/', $_POST['ks_month_date_format']) && preg_match('/[djz]/', $_POST['ks_month_date_format'])) {
			update_option('ks_month_date_format', stripslashes($_POST['ks_month_date_format']));
		}
	} else {
		delete_option('ks_month_date_format');
	}

	if (! empty($_POST['ks_time_format'])) {
		if (preg_match('/[djz]/', $_POST['ks_time_format']) || preg_match('/[BgGhH]/', $_POST['ks_time_format'])) {
			update_option('ks_time_format', stripslashes($_POST['ks_time_format']));
		}
	} else {
		delete_option('ks_time_format');
	}
	return;
}

/* ==================================================
 * @param	string  $key
 * @return	none
 */
private function update_boolean_option($key) {
	update_option($key, ($_POST[$key] != 0));
}

/* ==================================================
 * @param	string  $key
 * @return	none
 */
private function update_hex_option($key) {
	if (! empty($_POST[$key])) {
		if (preg_match('/^#[0-9a-fA-F]{6}$/', $_POST[$key])) {
			update_option($key, $_POST[$key]);
		}
	} else {
		delete_option($key);
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function delete_options() {
	$theme_opts = array('ks_theme', 'ks_theme_touch', 'ks_theme_mova', 'ks_theme_foma', 'ks_theme_ezweb', 'ks_theme_sb_pdc', 'ks_theme_sb_3g', 'ks_theme_willcom', 'ks_theme_emobile');
	foreach ($theme_opts as $t) {
		delete_option($t);
	}
	delete_option('ks_title_only'); // obsolete option
	delete_option('ks_external_link'); // obsolete option
	delete_option('ks_images_to_link');
	delete_option('ks_allow_pictograms');
	delete_option('ks_separate_comments');
	delete_option('ks_separate_recent_comments'); // obsolete option
	delete_option('ks_treat_as_internal');
	delete_option('ks_require_term_id');
	delete_option('ks_author_color');
	delete_option('ks_date_color');
	delete_option('ks_comment_type_color');
	delete_option('ks_external_link_color');
	delete_option('ks_year_format');
	delete_option('ks_month_date_format');
	delete_option('ks_time_format');
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function add_comment_meta() {
	add_meta_box('term_sub_ID', __("Poster's terminal ID and subscriber ID", 'ktai_style'), array($this, 'show_author_id'), 'comment', 'normal');
}

/* ==================================================
 * @param	object   $comment
 * @param	mix      $box
 * @return	none
 */
public function show_author_id($comment, $box) {
	$id = KtaiServices::read_term_id($comment);
	$author = array();
	if (count($id)) {
		if ($id[0]) {
			$author[] = sprintf(__('Term ID: %s', 'ktai_style'), esc_attr($id[0]));
		}
		if ($id[1]) {
			$author[] = sprintf(__('USIM ID: %s', 'ktai_style'), esc_attr($id[1]));
		}
		if ($id[2]) {
			$author[] = sprintf(__('Sub ID: %s', 'ktai_style'), esc_attr($id[2]));
		}
		if (count($author)) {
			echo implode('<br />', $author);
		} else {
			_e('N/A', 'ktai_style');
		}
	} else {
		_e('N/A', 'ktai_style');
	}
}

// ===== End of class ====================
}
?>