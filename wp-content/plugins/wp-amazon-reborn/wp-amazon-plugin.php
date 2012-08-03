<?php
/*
Plugin Name: WP-Amazon 1.x for WordPress 2.5+
Version: 1.4.3.1
Plugin URI: http://wppluginsj.sourceforge.jp/wp-amazon/
Description: WP-Amazon adds the ability to search and include items from Amazon to your entries.  This plugin adds a button called "Amazon" on the post page.  Make sure to configure the plugin before using. This Plugin was based on WP-Amazon Plugin 1.3.2 by Rich Manalang
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: wp-amazon
Domain Path: /languages/

  Notice! Only for WordPress 2.5+

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2008-2009 wokamoto (email : wokamoto1973@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Based:
  This Plugin was based on WP-Amazon Plugin 1.3.2 by Rich Manalang (http://manalang.com/wp-amazon)
  WP-Amazon Plugin 1.3.2
    Copyright (C) 2005 Rich Manalang
    Version 1.3.2 2005.09.29
    Released under the GPL license
*/

//**************************************************************************************
// Define
//**************************************************************************************


//**************************************************************************************
// class WP_Amazon
//**************************************************************************************
class WP_Amazon {
//	const DEFAULT_COUNTRY = 'JP';
//	const OPTION_ACCESS_LEVEL = 'manage_options';

	var $country, $associate_id, $subscription_id, $secret_key;
	var $CountryArray, $SearchIndexArray;

	var $plugin_directory, $plugin_filename;
	var $textdomain_name = 'wp-amazon';

	var $nonce = -1;
	var $xml_list_elements;

	/* Debug flag */
	var $debug = false;

	/*
	* Constructor
	*/
	function WP_Amazon () {
		$this->__construct();
	}
	function __construct() {
		global $wp_version;

		$filename = explode("/", __FILE__);
		if(count($filename) <= 1) {
			$filename = explode("\\", __FILE__);
		}
		$this->plugin_directory = $filename[count($filename) - 2];
		$this->plugin_filename  = $filename[count($filename) - 1];

		// load i18n translations
		$plugins_dir = trailingslashit(
			defined('PLUGINDIR')
			? PLUGINDIR
			: 'wp-content/plugins'
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_directory) . 'languages');
		if ( version_compare($wp_version, '2.6', '>=') && defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain($this->textdomain_name, false, $textdomain_dir);
		} else {
			load_plugin_textdomain($this->textdomain_name, $plugins_dir . $textdomain_dir);
		}

		// initialize all the variables
		$this->country         = get_option('wp-amazon_country');
		$this->associate_id    = get_option('wp-amazon_associate_id');
		$this->subscription_id = get_option('wp-amazon_subscription_id');
		$this->secret_key      = get_option('wp-amazon_secret_key');

		// Set defaults if properties aren't set
		if ( !$this->country || empty($this->country) ) {
			$this->country = 'JP';	// self::DEFAULT_COUNTRY;
		}

		// Set Country Array & Search Index Array
		$this->CountryArray = array (
			"US" => array (
				"BaseURL" => "http://webservices.amazon.com/onca/xml?Service=AWSECommerceService",
				"Descr" => __("United States", $this->textdomain_name),
				"URL" => "Amazon.com"
			),
			"UK" => array (
				"BaseURL" => "http://webservices.amazon.co.uk/onca/xml?Service=AWSECommerceService",
				"Descr" => __("United Kingdom", $this->textdomain_name),
				"URL" => "Amazon.co.uk"
			),
			"DE" => array (
				"BaseURL" => "http://webservices.amazon.de/onca/xml?Service=AWSECommerceService",
				"Descr" => __("Germany", $this->textdomain_name),
				"URL" => "Amazon.de"
			),
			"JP" => array (
				"BaseURL" => "http://webservices.amazon.co.jp/onca/xml?Service=AWSECommerceService",
				"Descr" => __("Japan", $this->textdomain_name),
				"URL" => "Amazon.co.jp"
			),
			"CA" => array (
				"BaseURL" => "http://webservices.amazon.ca/onca/xml?Service=AWSECommerceService",
				"Descr" => __("Canada", $this->textdomain_name),
				"URL" => "Amazon.ca"
			),
			"FR" => array (
				"BaseURL" => "http://webservices.amazon.fr/onca/xml?Service=AWSECommerceService",
				"Descr" => __("France", $this->textdomain_name),
				"URL" => "Amazon.fr"
			)
		);

		$this->SearchIndexArray = array (
			"Blended" =>array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("All Products", $this->textdomain_name)),
			"Books" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Books", $this->textdomain_name)),
			"ForeignBooks" => array ("US" => false, "UK" =>false, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Foreign Books", $this->textdomain_name)),
			"Magazines" => array ("US" => true, "UK" =>false, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Magazines", $this->textdomain_name)),
			"Video" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => false, "Descr" => __("Video", $this->textdomain_name)),
			"DVD" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("DVD", $this->textdomain_name)),
			"VHS" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("VHS", $this->textdomain_name)),
			"Music" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Music", $this->textdomain_name)),
			"MusicTracks" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => false, "FR" => true, "Descr" => __("Music Tracks", $this->textdomain_name)),
			"Classical" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Classical Music", $this->textdomain_name)),
			"DigitalMusic" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Digital Music", $this->textdomain_name)),
			"MusicalInstruments" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Musical Instruments", $this->textdomain_name)),
			"Electronics" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Electronics", $this->textdomain_name)),
			"PCHardware" => array ("US" => true, "UK" =>false, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("PC Hardware", $this->textdomain_name)),
			"Software" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Software", $this->textdomain_name)),
			"Photo" => array ("US" => true, "UK" =>false, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Photo", $this->textdomain_name)),
			"OfficeProducts" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Office Products", $this->textdomain_name)),
			"Wireless" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Wireless", $this->textdomain_name)),
			"WirelessAccessories" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Wireless Accessories", $this->textdomain_name)),
			"VideoGames" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => true, "FR" => true, "Descr" => __("Video Games", $this->textdomain_name)),
			"SoftwareVideoGames" => array ("US" => false, "UK" =>true, "DE" => true, "JP" => false, "CA" => true, "FR" => true, "Descr" => __("Software/Video Games", $this->textdomain_name)),
			"Toys" => array ("US" => true, "UK" =>true, "DE" => false, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Toys", $this->textdomain_name)),
			"Kitchen" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Kitchen", $this->textdomain_name)),
			"Tools" => array ("US" => true, "UK" =>false, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Tools", $this->textdomain_name)),
			"HomeGarden" => array ("US" => false, "UK" =>true, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Home &amp; Garden", $this->textdomain_name)),
			"HealthPersonalCare" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Health &amp; Personal Care", $this->textdomain_name)),
			"Beauty" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Beauty", $this->textdomain_name)),
			"Apparel" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Apparel", $this->textdomain_name)),
			"Watches" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("Watches", $this->textdomain_name)),
			"Jewelry" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Jewelry", $this->textdomain_name)),
			"Baby" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Baby", $this->textdomain_name)),
			"SportingGoods" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => true, "CA" => false, "FR" => false, "Descr" => __("SportingGoods", $this->textdomain_name)),
			"OutdoorLiving" => array ("US" => true, "UK" =>true, "DE" => true, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Outdoor Living", $this->textdomain_name)),
			"GourmetFood" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Gourmet Food", $this->textdomain_name)),
			"Restaurants" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Restaurants", $this->textdomain_name)),
			"Miscellaneous" => array ("US" => true, "UK" =>false, "DE" => false, "JP" => false, "CA" => false, "FR" => false, "Descr" => __("Miscellaneous", $this->textdomain_name))
		);

		// Add admin dashboard
		add_action('admin_menu', array(&$this, 'show_options_page'));

		if ( !$this->associate_id || !$this->subscription_id || !$this->secret_key ) {
			return;
		}

		// media button support
		add_action('media_buttons', array(&$this, 'addMediaButton'), 20);
		add_action('media_upload_amazon', 'media_upload_amazon');
		add_action('admin_head_media_upload_amazon_form', array(&$this, 'addMediaHead'));
		if ( version_compare($wp_version, "2.6", "<") ) {
			add_action('admin_head_media_upload_amazon_form', 'media_admin_css');
		}
	}

	function getAbsoluteUrl() {
		return get_option('siteurl')."/wp-content/plugins/".$this->plugin_directory;
	}

	function options_page() {
		if(isset($_POST['submitted'])) {
			if ( function_exists('check_admin_referer') ) {
				check_admin_referer($this->nonce);
			}

			$this->country = stripslashes($_POST['wp-amazon_country']);
			$this->associate_id = stripslashes($_POST['wp-amazon_associate_id']);
			$this->subscription_id = stripslashes($_POST['wp-amazon_subscription_id']);
			$this->secret_key = stripslashes($_POST['wp-amazon_secret_key']);

			update_option('wp-amazon_country', $this->country);
			update_option('wp-amazon_associate_id', $this->associate_id);
			update_option('wp-amazon_subscription_id', $this->subscription_id);
			update_option('wp-amazon_secret_key', $this->secret_key);

			echo '<div class="updated"><p><strong>' . __('Options saved.', $this->textdomain_name) . '</strong></p></div>';

		} elseif(isset($_POST['options_delete'])) {
			// Check Nonce Field
			if ( function_exists('check_admin_referer') ) {
				check_admin_referer("delete_options");
			}

			// options delete
			delete_option('wp-amazon_country');
			delete_option('wp-amazon_associate_id');
			delete_option('wp-amazon_subscription_id');
			delete_option('wp-amazon_secret_key');

			$this->country         = self::DEFAULT_COUNTRY;
			$this->associate_id    = '';
			$this->subscription_id = '';
			$this->secret_key      = '';

			// Done!
			echo '<div class="updated"><p><strong>' . __('Options deleted.', $this->textdomain_name) . '</strong></p></div>';

		} else {
			$this->country         = get_option('wp-amazon_country');
			$this->associate_id    = get_option('wp-amazon_associate_id');
			$this->subscription_id = get_option('wp-amazon_subscription_id');
			$this->secret_key      = get_option('wp-amazon_secret_key');
		}

		$var[$this->country] = " selected";

		$formaction = $_SERVER['PHP_SELF'] . '?page=' . basename(dirname(__FILE__)) . '/' . basename(__FILE__);

		// Start outputting XHMTL
?>
<div class="wrap">
	<h2><?php _e('General Options', $this->textdomain_name); ?></h2>

	<form name="wp-amazon_options" method="post" action="<?php echo $formaction; ?>">
		<?php $this->make_nonce_field($this->nonce); ?>
		<input type="hidden" name="submitted" value="1" />

		<fieldset class="options">
			<legend><label><?php _e('Default Country', $this->textdomain_name); ?></label></legend>
			<p><?php _e('Which Amazon country site would you like as your default?  Currently, Amazon\'s E-Commerce Service works with the following countries: Canada, France, Germany, Great Britain, Japan, and United States', $this->textdomain_name); ?></p>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"><tbody>
				<tr>
					<th width="33%" valign="top" scope="row"><?php _e('Default Country:', $this->textdomain_name); ?> </th>
					<td>
						<select name="wp-amazon_country">
						<?php
		foreach ($this->CountryArray as $CountryAbbrev => $CountryAttribs) {
			print "<option value=\"{$CountryAbbrev}\"{$var[$CountryAbbrev]}>{$CountryAttribs[Descr]}</option>\n";
		}
						?>
						</select>
					</td>
				</tr>
			</tbody></table>
		</fieldset>

		<fieldset class="options">
			<legend><label><?php _e('Associates ID', $this->textdomain_name); ?></label></legend>
			<p><?php _e('Amazon has an affiliate program called Amazon Associates.  This program allows you to earn money for refering customers to Amazon. To apply for the Associates Program, visit the <a href="http://www.amazon.com/associates">Amazon Associates website</a> for details.', $this->textdomain_name); ?></p>
			<p><?php _e('You can chose to have WP-Amazon apply your Associate ID to any Amazon products you post via WP-Amazon &mdash; just specify your Associate ID here.', $this->textdomain_name); ?></p>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"><tbody>
				<tr>
					<th width="33%" valign="top" scope="row"><?php _e('Associate ID:', $this->textdomain_name); ?> </th>
					<td><input name="wp-amazon_associate_id" type="text" id="wp-amazon_associate_id" value="<?php echo $this->associate_id; ?>" size="50" /><br /></td>
				</tr>
			</tbody></table>
		</fieldset>

		<fieldset class="options">
			<legend><label><?php _e('Amazon E-Commerce Service (ECS) Subscription ID &amp; Secret Key', $this->textdomain_name); ?></label></legend>
			<p><?php _e('Amazon\'s E-Commerce Service is what makes this WordPress plugin possible.  Amazon ECS developers must use an ECS Subscription ID &amp; Secret Key in order to access Amazon\'s web service.', $this->textdomain_name); ?></p>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"><tbody>
				<tr>
					<th width="33%" valign="top" scope="row"><?php _e('Subscription ID:', $this->textdomain_name); ?> </th>
					<td><input name="wp-amazon_subscription_id" type="text" id="wp-amazon_subscription_id" value="<?php echo $this->subscription_id; ?>" size="100" /><br /></td>
				</tr>
				<tr>
					<th width="33%" valign="top" scope="row"><?php _e('Secret Key:', $this->textdomain_name); ?> </th>
					<td><input name="wp-amazon_secret_key" type="text" id="wp-amazon_secret_key" value="<?php echo $this->secret_key; ?>" size="100" /><br /></td>
				</tr>
			</tbody></table>
		</fieldset>

		<p class="submit"><input type="submit" name="Submit"class="button-primary button" value="<?php _e('Update Options &raquo;',$this->textdomain_name); ?>" /></p>
	</form>
</div>

<div class="wrap" style="margin-top:2em;">
	<h2><?php _e('Uninstall', $this->textdomain_name); ?></h2>
	<form method="post" id="delete_options" action="<?php echo $formaction; ?>">
		<?php echo $this->make_nonce_field("delete_options"); ?>
		<p><?php _e('All the settings of &quot;wp-amazon&quot; are deleted.', $this->textdomain_name); ?></p>
		<p class="submit"><input type="submit" name="options_delete" class="button-primary button" value="<?php _e('Delete Options &raquo;', $this->textdomain_name); ?>" /></p>
	</form>
</div>

<?php
	}

	function show_options_page() {
		add_options_page(
			__('WP-Amazon Options', $this->textdomain_name) ,
			__('Amazon', $this->textdomain_name) ,
			'manage_options' , //self::OPTION_ACCESS_LEVEL ,
			__FILE__ ,
			array(&$this, 'options_page')
			);
	}

	function make_nonce_field($action = -1) {
		if ( !function_exists('wp_nonce_field') ) {
			return;
		} else {
			return wp_nonce_field($action);
		}
	}

	function amazonSearch($q = '', $p = '', $u = '') {
		if ($q != '') {
			$ItemInfo = array('SearchIndex'=>array(), 'Items'=>array(), 'Count' => 0, 'FullTitle' => '');

			if ($p == '') {
				$p = 'Blended';
			}

			if ($u == '') {
				foreach ($this->CountryArray as $CountryAbbrev => $CountryAttribs) {
					if ($CountryAbbrev == $this->country || $CountryAbbrev == $c) {
						$u = $CountryAttribs[BaseURL];
						break;
					}
				}
			}

			if(!is_array($this->xml_list_elements)) {
				$this->xml_list_elements = array ("SearchIndex", "ASIN", "Item");
			}

			$q = str_replace("\'","'",$q);
			$Keywords = htmlspecialchars(rawurlencode($q));
			$URL =	$u .
				'&SubscriptionId=' . $this->subscription_id .
				'&AssociateTag=' . $this->associate_id .
				'&Operation=ItemSearch' .
				'&SearchIndex=' . $p .
				'&ResponseGroup=Medium' .
				'&Keywords=' . $Keywords .
				'&Timestamp=' . urlencode(gmdate('Y-m-d\TH:i:s\Z')) .
				'&Version=2005-07-27'; // append Amazon ECS version

			if ($this->secret_key) {
				$parsed_url = parse_url($URL);
				$parsed_query = explode('&', $parsed_url['query']);
				sort($parsed_query);
				$query = implode('&', $parsed_query);
				$signature =
					"GET\n" .
					$parsed_url['host'] . "\n" .
					$parsed_url['path'] . "\n" .
					$query;
				$signature = base64_encode(hash_hmac('sha256', $signature, $this->secret_key, true));
				$URL =	$parsed_url['scheme'] . '://' .
					$parsed_url['host'] .
					$parsed_url['path'] .
					'?' . $query .
					'&Signature=' . str_replace('%7E', '~', rawurlencode($signature));
				unset($parsed_query);
				unset($parsed_url);
			}

			$Results = $this->makeXMLTree_wpa($URL);

			if ($this->debug) {
				print "<pre>";
				print_r($Results);
				print "</pre>";
			}

			$ItemInfo['SearchIndex'] = $Results['ItemSearchResponse']['Items']['SearchResultsMap']['SearchIndex'];
			$ItemInfo['Items']       = $Results['ItemSearchResponse']['Items']['Item'];
			$ItemInfo['Count']       = count($ItemInfo['Items']);
			$ItemInfo['FullTitle']   = '[ ' . sprintf(__("%s (%s matches)", "wp-amazon"), $q, $ItemInfo['Count']) . ' ]';

			return ($ItemInfo);

		} else {
			return false;
		}
	}

	/*
	* Wordpress 2.5 - New media button support
	*/
	function addMediaButton() {
		global $post_ID, $temp_ID;

		$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
		$media_upload_iframe_src = "media-upload.php?post_id={$uploading_iframe_ID}";

		$type = "amazon";
		$tab  = "amazon";
		$amazon_iframe_src = apply_filters('media_upload_amazon_iframe_src', "{$media_upload_iframe_src}&amp;type=$type&amp;tab=$tab");
		$amazon_title = __('Insert item from Amazon.com', $this->textdomain_name);
		$link_markup = "<a href=\"{$amazon_iframe_src}&amp;keepThis=true&amp;TB_iframe=true&amp;height=500&amp;width=640\" class=\"thickbox\" title=\"$amazon_title\"><img src=\"".$this->getAbsoluteUrl()."/images/amazon-media.png\" alt=\"$amazon_title\" /></a>\n";

		echo $link_markup;
	}

	function amazonMediaBrowse() {
		$areMagicQuotesOn = get_magic_quotes_gpc();

		$title = __('Insert item from Amazon.com', $this->textdomain_name);

		$c = stripslashes($_REQUEST['c']);
		$p = stripslashes($_REQUEST['p']);
		$q = stripslashes($_REQUEST['q']);

		$this->xml_list_elements = array ("SearchIndex", "ASIN", "Item");

		$post_id = intval(stripslashes($_REQUEST['post_id']));
		$type = "amazon";
		$tab  = "amazon";
		$form_action_url = get_option('siteurl')."/wp-admin/media-upload.php?post_id={$post_id}&amp;type={$type}&amp;tab={$tab}";

		print "<form name=\"search\" method=\"post\" action=\"{$form_action_url}\">\n";
		print "<fieldset id=\"search\">\n";

		// Country pull-down menu
		print "<select name=\"c\" id=\"country\">\n";
		foreach ($this->CountryArray as $CountryAbbrev => $CountryAttribs) {
			if ($CountryAbbrev == $this->country || $CountryAbbrev == $c) {
				$BaseURL = $CountryAttribs[BaseURL];
				print "<option value=\"{$CountryAbbrev}\" selected>{$CountryAttribs[Descr]}</option>";
			} else {
				print "<option value=\"{$CountryAbbrev}\">{$CountryAttribs[Descr]}</option>";
			}
		}
		print "</select>";

		// Search index pull-down menu
		print "<select name=\"p\" id=\"searchtype\">\n";
		foreach ($this->SearchIndexArray as $SearchIndex => $SearchIndexAttribs) {
			if ($SearchIndexAttribs[$this->country]) {
				if ($SearchIndex == $p) {
					print "<option value=\"{$SearchIndex}\" selected>{$SearchIndexAttribs[Descr]}</option>\n";
				} else {
					print "<option value=\"{$SearchIndex}\">{$SearchIndexAttribs[Descr]}</option>\n";
				}
			}
		}
		print "</select>";

		$q_decoded = urldecode(str_replace("\'","'",$q));
		print "<input type=\"text\" name=\"q\" id=\"query\" value=\"{$q_decoded}\" />" .
			"<input type=\"submit\" class=\"button\" value=\"".__("Go","wp-amazon")."\" />" .
			"</fieldset></form>";

		/**** Search Results ****/
		if (isset ($p) && isset ($q) && !isset ($_POST['url'])) {
			$ItemInfo = $this->amazonSearch($q, $p, $BaseURL);
			$SearchIndices = $ItemInfo['SearchIndex'];
			$Items = $ItemInfo['Items'];
			$Count = $ItemInfo['Count'];
			$FullTitle = $ItemInfo['FullTitle'];
			unset($ItemInfo);

			/* Print result title */
			print "<div id=\"results\">";
			print "<h2>".__("Results for", $this->textdomain_name) . ": {$FullTitle}</h2>";

			/* Show result */
			if ($p == 'Blended') {
				$ItemIndex = array ();
				for ($i = 0; $i < count($Items); $i ++) {
					$ItemIndex[$Items[$i]['ASIN'][0]] = $i;
				}

				for ($i = 0; $i < count($SearchIndices); $i ++) {
					$SearchIndex = $SearchIndices[$i];
					$ASINarry = $SearchIndex['ASIN'];
					$HTML = "";

					for ($j = 0; $j < count($ASINarry); $j ++) {
						$ItemNumber = $ItemIndex[$ASINarry[$j]];
						$ASIN = htmlspecialchars($ASINarry[$j]);
						$URL = htmlspecialchars($Items[$ItemNumber]['DetailPageURL']);
						$ProductName = htmlspecialchars($Items[$ItemNumber]['ItemAttributes']['Title']);
						$Price = htmlspecialchars($Items[$ItemNumber]['ItemAttributes']['ListPrice']['FormattedPrice']);
						$ImageURLSml = htmlspecialchars(
							isset($Items[$ItemNumber]['SmallImage']['URL'])
							? $Items[$ItemNumber]['SmallImage']['URL']
							: $Items[$ItemNumber]['ImageSets']['ImageSet']['SmallImage']['URL']
							);
						$ImageURLMed = htmlspecialchars(
							isset($Items[$ItemNumber]['MediumImage']['URL'])
							? $Items[$ItemNumber]['MediumImage']['URL']
							: $Items[$ItemNumber]['ImageSets']['ImageSet']['MediumImage']['URL']
							);
						$ImageURLLrg = htmlspecialchars(
							isset($Items[$ItemNumber]['LargeImage']['URL'])
							? $Items[$ItemNumber]['LargeImage']['URL']
							: $Items[$ItemNumber]['ImageSets']['ImageSet']['LargeImage']['URL']
							);
						$p = htmlspecialchars($p);
						$HTML = $this->constructItems($HTML, $ASIN, $URL, $ProductName, $Price, $ImageURLSml, $ImageURLMed, $ImageURLLrg, $p, $q, $form_action_url);
					}
					print "<div class=\"mode\">";
					if ($HTML != null) {
						printf("<h3>".__("%s (%s total results)", $this->textdomain_name)."</h3>\n", __(htmlspecialchars($SearchIndex['IndexName']), "wp-amazon"), count($ASINarry));
						print $HTML;
					}
					print "</div>\n";
				}

			} else {
				for ($i = 0; $i < count($Items); $i ++) {
					$Item = $Items[$i];
					$ASIN = $Item['ASIN'];
					$URL = htmlspecialchars($Item['DetailPageURL']);
					$ProductName = htmlspecialchars($Item['ItemAttributes']['Title']);
					$Price = htmlspecialchars($Item['OfferSummary']['LowestNewPrice']['FormattedPrice']);
					$ImageURLSml = htmlspecialchars(
						isset($Item['SmallImage']['URL'])
						? $Item['SmallImage']['URL']
						: $Item['ImageSets']['ImageSet']['SmallImage']['URL']
						);
					$ImageURLMed = htmlspecialchars(
						isset($Item['MediumImage']['URL'])
						? $Item['MediumImage']['URL']
						: $Item['ImageSets']['ImageSet']['MediumImage']['URL']
						);
					$ImageURLLrg = htmlspecialchars(
						isset($Item['LargeImage']['URL'])
						? $Item['LargeImage']['URL']
						: $Item['ImageSets']['ImageSet']['LargeImage']['URL']
						);

					$p = htmlspecialchars($p);
					$HTML = $this->constructItems($HTML, $ASIN, $URL, $ProductName, $Price, $ImageURLSml, $ImageURLMed, $ImageURLLrg, $p, $q, $form_action_url);
				}
				print $HTML;
			}
		}

		/**** Item has been selected ****/
		$URL = $_POST['url'];
		$ProductName = $_POST['prodname'];
		if ($areMagicQuotesOn) {
			$ProductName = addslashes($ProductName);
		}
		$ProductNameCleaned = stripslashes($_POST['prodname']);
		$ImageURLSml = htmlspecialchars($_POST['imgurlsml']);
		$ImageURLMed = htmlspecialchars($_POST['imgurlmed']);
		$ImageURLLrg = htmlspecialchars($_POST['imgurllrg']);
		$p = htmlspecialchars($_POST['p']);
		$q = htmlspecialchars(urlencode(str_replace("\'","'",$_POST['q'])));

		if (isset ($URL) && isset ($ProductName)) {
			$URL = htmlspecialchars($URL);
			$ProductName = htmlspecialchars($ProductName);
			print "<div id=\"results\">";
			print "<h2><a href=\"{$URL}\" title=\"".__("Click here for more information on this product", $this->textdomain_name)."\" onclick=\"window.open('{$URL}');return false;\">{$ProductNameCleaned}</a></h3>";
			print "<fieldset id=\"options\">";
			print "<legend>".__("How to Include?", "wp-amazon")."</legend>\n";
			print "<a href=\"{$URL}\" title=\"".__("Click here for more information on this product", "wp-amazon")."\" onclick=\"window.open('{$URL}');return false;\">";

			if ($ImageURLSml != null ) {
				print "<img src=\"{$ImageURLSml}\" align=\"left\" /></a>\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"link\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '');\" />".__("Link only", "wp-amazon")."</label><br />\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"sml\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '{$ImageURLSml}');\" />".__("Link with small image", "wp-amazon")."</label><br />\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"med\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '{$ImageURLMed}');\" />".__("Link with medium image", "wp-amazon")."</label><br />\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"lrg\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '{$ImageURLLrg}');\" />".__("Link with large image", "wp-amazon")."</label>\n";
			} else {
				print "<img src=\"".$this->getAbsoluteUrl()."/images/no_image.png\" align=\"left\" style=\"width:53px;height:75px;\" /></a>\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"link\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '');\" />".__("Link only", "wp-amazon")."</label><br />\n";
				print "<label><input type=\"radio\" class=\"radio\" name=\"type\" value=\"sml\" onclick=\"generateHTMLCode(this.value, '{$ProductName}', '{$URL}', '".$this->getAbsoluteUrl()."/images/no_image.png');\" />".__("Link with small image", "wp-amazon")."</label><br />\n";
			}

			print "</fieldset>\n";
			print "<fieldset id=\"options\">\n";
			print "<legend>".__("HTML Code", "wp-amazon")."</legend>\n";
			print "<textarea name=\"htmlcode\" id=\"htmlcode\"></textarea>\n";
			print "</fieldset>\n";
			print "<input type=\"button\" class=\"button\" name=\"addtopost\" value=\"".__("Add to HTML Post", "wp-amazon")."\" onclick=\"javascript:top.send_to_editor(document.getElementById('htmlcode').value );\" />\n";
			print "<input type=\"button\" class=\"button\" name=\"close\" value=\"".__("Close Window", "wp-amazon")."\" onclick=\"javascript:top.tb_remove();\" />\n";
			print "&nbsp;<a href=\"{$form_action_url}&amp;p={$p}&amp;q={$q}\">".__("Back", "wp-amazon")."</a>\n";
			print "</div>\n";
		}
	}

	function & composeArray($array, $elements, $value = array ()) {
		// get current element
		$element = array_shift($elements);

		// does the current element refer to a list
		if (in_array($element, $this->xml_list_elements)) {
			// more elements?
			if (sizeof($elements) > 0) {
				$array[$element][sizeof($array[$element]) - 1] = & $this->composeArray($array[$element][sizeof($array[$element]) - 1], $elements, $value);
			} else {
				$array[$element][sizeof($array[$element])] = $value;
			}
		} else {
			// more elements?
			if (sizeof($elements) > 0) {
				$array[$element] = & $this->composeArray($array[$element], $elements, $value);
			} else {
				$array[$element] = $value;
			}
		}
		return $array;
	}

	function makeXMLTree_wpa($file) {
		/* Added check for curl... use curl when possible
		 * because of security concerns with allow_url_fopen */
		if (function_exists('curl_init')) {
			// John White read file
			$ch = curl_init($file);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close($ch);   // John White end of curl read file fopen replace
		} else {
			// curl not available use fopen instead
			$open_file = fopen($file, "r");
			$data = "";
			while ($r = fread($open_file, 8192)) {
				$data .= $r;
			}
		}

		// create parser
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $data, $values, $tags);
		xml_parser_free($parser);

		// we store our path here
		$hash_stack = array ();

		// this is our target
		$ret = array ();
		foreach ($values as $key => $val) {
			switch ($val['type']) {
				case 'open' :
					array_push($hash_stack, $val['tag']);
					$ret = (
						isset ($val['attributes'])
						? $this->composeArray($ret, $hash_stack, $val['attributes'])
						: $this->composeArray($ret, $hash_stack)
						);
					break;
				case 'close' :
					array_pop($hash_stack);
					break;
				case 'complete' :
					array_push($hash_stack, $val['tag']);
					$ret = $this->composeArray($ret, $hash_stack, $val['value']);
					array_pop($hash_stack);
					// handle attributes
					if (isset ($val['attributes'])) {
						while (list ($a_k, $a_v) = each($val['attributes'])) {
							$hash_stack[] = $val['tag']."_attribute_".$a_k;
							$ret = $this->composeArray($ret, $hash_stack, $a_v);
							array_pop($hash_stack);
						}
					}
					break;
			}
		}

		if ($this->debug) {
			print '<pre>';
			print_r($ret);
			print '</pre>';
		}

		return $ret;
	}

	// Constructs result items into one HTML var
	function constructItems($HTML, $ASIN, $URL, $ProductName, $Price, $ImageURLSml, $ImageURLMed, $ImageURLLrg, $p, $q, $FormActionURL) {
		if ($ProductName != null) {
			$HTML .= '<div class="item">';
			if ($ImageURLSml != null) {
				$HTML .= "<a href=\"{$URL}\" title=\"".__("Click here for more information on this product", "wp-amazon")."\" ";
				$HTML .= "onclick=\"window.open('{$URL}');return false;\">";
				$HTML .= "<img src=\"{$ImageURLSml}\" alt=\"{$ProductName}\" /></a>\n";
			} else {
				$HTML .= "<a href=\"{$URL}\" title=\"".__("Click here for more information on this product", "wp-amazon")."\" ";
				$HTML .= "onclick=\"window.open('{$URL}');return false;\">";
				$HTML .= "<img src=\"".$this->getAbsoluteUrl()."/images/no_image.png"."\" alt=\"{$ProductName}\" width=\"53px\" height=\"75px;\" /></a>\n";
			}
			$HTML .= "<h4><a href=\"{$URL}\" title=\"".__("Click here for more information on this product", "wp-amazon")."\" ";
			$HTML .= "onclick=\"window.open('{$URL}');return false;\">{$ProductName}</a><br />";
			if ($Price != null) {
				$HTML .= __("Price", "wp-amazon").": {$Price} <br />";
			}
			if ($ASIN != null && !is_array($ASIN)) {
				$HTML .= __("ASIN", "wp-amazon").": {$ASIN}";
			}
			$HTML .= "<br /></h4>\n";
			$HTML .= "<form name=\"select\" method=\"post\" action=\"{$FormActionURL}\">\n";
			$HTML .= "<input type=\"hidden\" name=\"url\" value=\"{$URL}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"prodname\" value=\"{$ProductName}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"imgurlsml\" value=\"{$ImageURLSml}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"imgurlmed\" value=\"{$ImageURLMed}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"imgurllrg\" value=\"{$ImageURLLrg}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"p\" value=\"{$p}\" />\n";
			$HTML .= "<input type=\"hidden\" name=\"q\" value=\"{$q}\" />\n";
			$HTML .= "<input type=\"submit\" class=\"button\" value=\"".__("Select","wp-amazon")."\" />\n";
			$HTML .= "</form>\n";
			$HTML .= "</div>\n";
		}
		return $HTML;
	}

	function addMediaHead() {
		$post_id = intval($_REQUEST['post_id']);
?>
<style type="text/css">/* <![CDATA[ */
#search {padding:5px;}
#query {width:200px;}
#results {margin:.25em .5em; border:1px solid #ccc; padding:0 5px 5px 5px; background: #eee;}
#results h2 {font-size:1.25em; font-weight:bold; margin:5px 0 10px 0; padding:0; border-bottom:1px solid;}
#results h2 a {text-decoration:none; border-bottom:none;}
#results .mode {margin: .5em; padding: .5em;}
#results .mode h3 {font-size: 1em; color:#333; margin: 0; padding: 0; border-bottom:1px #333 solid; text-transform:capitalize;}
#results .item {margin: 0; padding: 5px; border-bottom:1px solid #ccc; clear:both;}
#results .item:after {content: "."; display: block; height: 0; clear: both; visibility: hidden;}
#results h4 {display: inline; font-size: .9em; font-weight: normal; margin: 0; padding: 0;}
#results form {padding: 0; margin:0; display:block; text-align:right !important;}
#results img {float:left; margin:5px; border:1px solid #000;}
/* #results input {margin: 10px 0 0 0;} */
#results textarea {width:95%; height:150px; margin:5px;}
#options {padding: 5px;}
.radio {border:0; background:transparent;}
/* ]]> */</style>
<script type="text/javascript"> //<![CDATA[
var post_id = <?php echo (int)$post_id; ?>;
function generateHTMLCode(format, prodname, url, imgurl) {
	var html =
		'<'+'a href="'+url+'" title="<?php _e('View product details at Amazon', $this->textdomain_name); ?>">' +
		(format=='link' ? prodname : '<'+'img src="'+imgurl+'" alt="'+prodname+'" />') +
		'<'+'/a>';
	document.getElementById("htmlcode").value = html;
}
//]]> </script>
<?php
	}

	function modifyMediaTab($tabs) {
		return array('amazon' => __('Search Amazon', $this->textdomain_name));
	}
} // Class WP_Amazon

function media_upload_amazon() {
	wp_iframe('media_upload_amazon_form');
}
function media_upload_amazon_form() {
	global $wp_amazon;

	add_filter('media_upload_tabs', array(&$wp_amazon, 'modifyMediaTab'));

	echo "<div id=\"media-upload-header\">\n";
	media_upload_header();
	echo "</div>\n";

	$wp_amazon->amazonMediaBrowse();
}

//**************************************************************************************
// Add actions to call the function
//**************************************************************************************
global $wp_version;

if ( version_compare($wp_version, "2.5", ">=") && is_admin() ) {
	global $wp_amazon;

	$wp_amazon = new WP_Amazon();
}
?>