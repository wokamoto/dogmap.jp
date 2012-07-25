<?php
if (is_admin()) return false;

class PluginsUsedPlugin {
	public $PluginFiles;

	/**********************************************************
	* Constructor
	***********************************************************/
	public function PluginsUsedPlugin() {
		$this->__construct();
	}
	public function __construct() {
		$this->PluginFiles = $this->getPlugins();
	}

	private function getPluginData($plugin_file) {
		if (trim($plugin_file) == "")
			return '';
		if (!file_exists(ABSPATH . '/wp-content/plugins/' . $plugin_file))
			return '';
		if (!is_readable(ABSPATH . '/wp-content/plugins/' . $plugin_file))
			return '';

		$plugin_data = implode('', file(ABSPATH .'/wp-content/plugins/' . $plugin_file));

		$plugin_name = trim(preg_match("|Plugin Name:(.*)|i", $plugin_data, $plugin_name)
			? $plugin_name[1]
			: '' );
		if ('' == $plugin_name)
			return '';

		$plugin_uri = trim( preg_match("|Plugin URI:(.*)|i", $plugin_data, $plugin_uri)
			? $plugin_uri[1]
			: '' );
		$description = trim(preg_match("|Description:(.*)|i", $plugin_data, $description)
			? $description[1]
			: '' );
		$author_name = trim(preg_match("|Author:(.*)|i", $plugin_data, $author_name)
			? $author_name[1]
			: '' );
		$author_uri = trim(preg_match("|Author URI:(.*)|i", $plugin_data, $author_uri)
			? $author_uri[1]
			: '' );
		$version = trim( preg_match("|Version:(.*)|i", $plugin_data, $version)
			? $version[1]
			: '' );
		$text_domain = trim( preg_match("|Text Domain:(.*)|i", $plugin_data, $text_domain)
			? $text_domain[1]
			: '' );
		$domain_path = trim( preg_match("|Domain Path:(.*)|i", $plugin_data, $domain_path)
			? $domain_path[1]
			: '' );

		if ( !empty($text_domain) ) {
			$plugin_name = __($plugin_name, $text_domain);
			$plugin_uri  = __($plugin_uri,  $text_domain);
			$description = __($description, $text_domain);
			$author_name = __($author_name, $text_domain);
			$author_uri  = __($author_uri,  $text_domain);
		}

		$description = wp_kses(wptexturize($description), array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array()) );

		$plugin = ( '' == $plugin_uri
			? $plugin_name
			: "<a href=\"{$plugin_uri}\" title=\"".__('Visit plugin homepage')."\">{$plugin_name}</a>"
			);

		$author = ('' == $author_uri
			? $author_name
			: "<a href=\"{$author_uri}\" title=\"".__('Visit author homepage')."\">{$author_name}</a>"
			);

		return array(
			'plugin_name' => $plugin_name ,
			'plugin_uri' => $plugin_uri ,
			'description' => $description ,
			'author_name' => $author_name ,
			'author_uri' => $author_uri ,
			'version' => $version ,
			'plugin' => $plugin ,
			'author' => $author ,
			);
	}

	private function getPlugins() {
		$activePluginsResult = get_settings('active_plugins');
		$activePluginsResult = ( is_array($activePluginsResult)
			? $activePluginsResult
			: explode("\n",$activePluginsResult) );
		$PluginFiles = array_values($activePluginsResult);
		if (is_array($PluginFiles[0])) {
			// new style - used the keys, not the values
			$PluginFiles = array_keys($activePluginsResult);
		};
		sort($PluginFiles); // Alphabetize by filename. Better way?
		$PluginFiles = array_unique($PluginFiles);

		$result = array();
		foreach($PluginFiles as $plugin_file) {
			$current = $this->getPluginData($plugin_file);
			if ('' != $current)
				$result[$current['plugin_name']] = $current;
		};
		//uksort($result, create_function('$plug1, $plug2', 'return strnatcasecmp($plug1["plugin_name"], $plug2["plugin_name"]);'));
		return $result;
	}

	public function displayPluginsAsTable($tableId="none", $showDescription="1") {
		$style = '';
		$tableStr = ( $tableId == "none"
			? ' cellpadding="3" cellspacing="3"'
			: ' id="'.$tableId.'"' );
		$tableStr .= ' class="border_on"';

		echo "<table{$tableStr}>\n";
		echo "<tr>\n";
		echo '<th>' . __('Plugin') . "</th>\n";
		echo '<th>' . __('Version') . "</th>\n";
		echo '<th>' . __('Author') . "</th>\n";
		if ($showDescription == "1")
			echo '<th>' . __('Description') . "</th>\n";
		echo "</tr>\n";

	    $style = '';
	    foreach($this->PluginFiles as $plugin_file) {
	//		$style = (' class="alternate"' == $style) ? '' :'class="alternate"';
			$style = '';
			$plugin = $plugin_file['plugin'];
			$plugin_name = $plugin_file['plugin_name'];
			$plugin_uri = $plugin_file['plugin_uri'];
			$author = $plugin_file['author'];
			$author_name = $plugin_file['author_name'];
			$author_uri = $plugin_file['author_uri'];
			$version = $plugin_file['version'];
			$description = $plugin_file['description'];

		    echo "\n<tr{$style}>\n";
			echo "<td>{$plugin}</td>\n";
			echo "<td>{$version}</td>\n";
			echo "<td>{$author}</td>\n";

			if($showDescription)
				echo "<td>{$description}</td>\n";
			echo "\n</tr>\n";
		};

		echo "</table>\n";
	}

	public function displayPluginsAsList() {
		echo "<ul>\n";
		foreach($this->PluginFiles as $plugin_file) {
			$plugin = $plugin_file['plugin'];
			echo "<li> $plugin </li>\n";
		};
		echo "</ul>\n";
	}
}

global $PluginsUsedPlugin;

function displayPluginsAsTable() {
	global $PluginsUsedPlugin;
	if ( !isset($PluginsUsedPlugin) )
		$PluginsUsedPlugin = new PluginsUsedPlugin;
	$PluginsUsedPlugin->displayPluginsAsTable();
};

function displayPluginsAsList() {
	global $PluginsUsedPlugin;
	if ( !isset($PluginsUsedPlugin) )
		$PluginsUsedPlugin = new PluginsUsedPlugin;
	$PluginsUsedPlugin->displayPluginsAsList();
};
