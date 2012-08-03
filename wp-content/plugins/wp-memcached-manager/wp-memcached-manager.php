<?php
/*
Plugin Name: WP Memcached Manager
Plugin URI: http://warwickp.com/projects/wp-memcached-manager/
Description: A simple tool to manage memcached server(s) from inside the WordPress admin interface. Requires PECL Memcache client.
Author: Warwick Poole
Version: 0.1
Author URI: http://warwickp.com/
*/

/*  Copyright 2009 Warwick Poole (email: wpoole@gmail.com)
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
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class WPMemcachedManager {
	//the servers we have setup
	var $memcached_servers = array();

	//Simple constructor, create top level menu
	function WPMemcachedManager() {
		add_action( 'admin_menu', array(&$this, 'wpmm_menu' ));
		$wpmm_options = get_option( 'wp_memcached_manager' );
		$this->memcached_servers = ( is_serialized( $wpmm_options )) ? unserialize( $wpmm_options ) : $wpmm_options;
		add_action( 'init', array( &$this, 'wpmm_add_all_servers' ));
	}

	function wpmm_menu() {
		// Add a new top-level menu since this is a tool all on it's own
		add_menu_page( 'WP Memcached Manager', 'Memcached', 'administrator', 'wp_memcached_manager', 'wpmm_main_page' );
		// Add a submenu 
		add_submenu_page( 'wp_memcached_manager', 'Edit servers', 'Edit servers', 'administrator', 'wpmm-edit-servers', 'wpmm_edit_page' );

	}

	//Check if we have the PECL memcache client
	function wpmm_check_memcache() {
		if( ! class_exists( 'Memcache' ))
			return false;
		else 
			return true;
	}

	function wpmm_add_all_servers(){
		if ( ! empty( $this->memcached_servers )) {
			foreach( $this->memcached_servers as $memcached_server ) {
				$this->wpmm_add_server( $memcached_server['server_id'], $memcached_server['server_host'], $memcached_server['server_port'] );
			}
		}
		update_option( 'wp_memcached_manager', serialize( $this->memcached_servers ));
	}

	function wpmm_add_new_server( $server_host, $server_port ) {
		$new_server_id = $server_host . "-" . $server_port; 

		// check if server_id is unique
		if( ! empty( $this->memcached_servers )) {
			foreach( $this->memcached_servers as $memcached_server ) {
				if( $memcached_server['server_id'] == $new_server_id ) {
					return false;
				}
			}
		}
		
		$this->memcached_servers[] = array( 'server_id' => $new_server_id, 'server_host' => $server_host, 'server_port' => $server_port );
		$this->wpmm_add_all_servers();
		return true;
	}

	function wpmm_add_server( $server_id, $server_host, $server_port ) {
		$individual_server = new WPMemcachedServer( $server_id, $server_host, $server_port );
	}

	function wpmm_delete_server( $server_id ) {		
		if( ! empty( $this->memcached_servers )) {
			foreach( $this->memcached_servers as $key => $value ) {
				if( $this->memcached_servers[$key]['server_id'] == $server_id ) {
					unset( $this->memcached_servers[$key] );
					$this->wpmm_add_all_servers(true);
					return true;
				}
			}
		}
	}

	/* formatting helpers */
	function wpmm_build_server_select( $title_shown, $selected_server = "" ){
		echo "<option value=''>  $title_shown </option>";

		foreach( $this->memcached_servers as $key => $value ) {
			if ( $selected_server == $this->memcached_servers[$key]['server_id'] )
				$selected = "selected";
			echo "<option value='" . $this->memcached_servers[$key]['server_id'] . "' " . $selected . " >" . $this->memcached_servers[$key]['server_host'] . ":" . $this->memcached_servers[$key]['server_port'] . "</option>";
			$selected = "";
		}
	}
}


class WPMemcachedServer extends Memcache {

	var $memcached_server_host;
	var $memcached_server_port;
	var $memcached_server_id;  //format: host-port

	function WPMemcachedServer( $memcached_server_id, $memcached_server_host, $memcached_server_port ) {
		$this->server_host = $memcached_server_host;
		$this->server_port = $memcached_server_port;
		$this->server_id = $memcached_server_id;
		$this->addServer( $this->server_host, $this->server_port );
	}

	function wpmm_check_status() {
		if ( @$this->connect( $this->server_host, $this->server_port ))
			return $this->getServerStatus( $this->server_host, $this->server_port );
		else
			return false;
	}

	function wpmm_get_version() {
		return $this->getVersion();
	}

	/* 	Get all stats and output the interesting ones 
	 	Code adapted from http://livebookmark.net/journal/2008/05/21/memcachephp-stats-like-apcphp/ 
		By Harun Yayli */
	function wpmm_all_status_table() {
		$status = @$this->getStats();

		$MBSize=(real) $status["limit_maxbytes"]/(1024*1024) ;
		echo "<tr><td>Instance Size</td><td>".$MBSize." MB</td></tr>";
		$hours = intval( intval( $status ["uptime"] ) / 3600 ); 
		echo "<tr><td>Uptime</td><td>".$hours." hours</td></tr>";
		echo "<tr><td>Current Open Connections </td><td>".$status ["curr_connections"]."</td></tr>";
		echo "<tr><td>Total Connections Since Start </td><td>".$status ["total_connections"]."</td></tr>";
		echo "<tr><td>Total Objects Stored Since Start </td><td>".$status ["total_items"]."</td></tr>";

		//Do a set, get and delete test with 'random' key / value which are not massively random, but should do
		$store = "wp-memcache-manager test data. delete anytime. thank you";
		$setkey = time('s') . php_uname('n');
		$setkey = md5( $setkey );

		if ( $this->set( "$setkey" , "$store", 0, 500))    
			echo "<tr><td>SET TEST </td><td style='background: green; color: white;'>SUCCESS</td></tr>";
		else
			echo "<tr><td>SET TEST </td><td style='background: red; color: white;'>FAIL</td></tr>";

		if ( $this->get( "$setkey" )) 
			echo "<tr><td>GET TEST </td><td style='background: green; color: white;'>SUCCESS</td></tr>";
		else
			echo "<tr><td>GET TEST </td><td style='background: red; color: white;'>FAIL</td></tr>";

		//PECL bug in latest stable version is preventing a delete from working in many situations
		//and causing bad issues. Leaving out the delete for now.
		//http://us2.php.net/manual/en/function.memcache-delete.php#94536
		//if ( $this->delete( "$setkey" )) 
		//	echo "<tr><td>DELETE TEST </td><td style='background: green; color: white;'>SUCCESS</td></tr>";
		//else
		//	echo "<tr><td>DELETE TEST </td><td style='background: red; color: white;'>FAIL</td></tr>";

		echo "<tr><td>GETS </td><td>".$status ["cmd_get"]."</td></tr>";
		echo "<tr><td>SETS </td><td>".$status ["cmd_set"]."</td></tr>";

		$percCacheHit=@((real)$status ["get_hits"]/ (real)$status ["cmd_get"] *100);
		$percCacheHit=@number_format( round($percCacheHit,3) );
		$percCacheMiss=number_format( 100-$percCacheHit );
		echo "<tr><td>GET HITS</td><td>". $this->wpmm_google_percent_bar( $percCacheHit ). " ($percCacheHit%) </div></div>  </td></tr>";
		echo "<tr><td>GET MISSES </td><td>". $this->wpmm_google_percent_bar( $percCacheMiss, "FF3300" ). " ($percCacheMiss%) </td></tr>";

		$MBRead= number_format( (real)$status["bytes_read"]/(1024*1024));

		echo "<tr><td>Data Read </td><td>".$MBRead." MB</td></tr>";
		$MBWrite=number_format( (real) $status["bytes_written"]/(1024*1024));
		echo "<tr><td>Data Sent </td><td>".$MBWrite." MB</td></tr>";
		echo "<tr><td>Evictions</td><td>".$status ["evictions"]."</td></tr>";
	}


	/* 	There is no good way to list, or iterate through, all memcached keys or data values.
		This is very VERY rudimentary code to look at SOME data.
		This code is adapted from http://100days.de/serendipity/archives/55-Dumping-MemcacheD-Content-Keys-with-PHP.html
		By Gaylord Aulke 
		And could be bad news on very large memcache instances! */
	function wpmm_slab_walk( $max_entries = 500 ) {
		$list = array();
		$allSlabs = $this->getExtendedStats( 'slabs' );
		$items = $this->getExtendedStats( 'items' );
		foreach( $allSlabs as $server => $slabs ) {
			foreach( $slabs as $slabId => $slabMeta ) {
				$cdump = $this->getExtendedStats ('cachedump',(int)$slabId, $max_entries );
				foreach( $cdump as $server => $entries ) {
					if( $entries ) {
						foreach( $entries AS $eName => $eData ) {
							$the_key = esc_attr( $eName );
							$the_value = esc_attr( $this->get( $the_key ) );
							echo "<tr><td> $the_key </td><td>  $the_value  </td></tr> ";	
						}
					}
				}
			}
		}
	}


	function wpmm_flush(){
		if ( $this->flush() )
			return true;
		else
			return false;
	}

	/* formatting functions */
	function wpmm_google_percent_bar( $percentage, $color = "00CC66" ){
		return "<img src='http://chart.apis.google.com/chart?cht=bhs&chs=150x10&chd=t:$percentage|100&chco=$color,CCCCCC&chbh=5' />";
	}

	function wpmm_command_row( $value, $title ) {
		$title = esc_attr( $title );
		$value = esc_attr( $value );
		echo "<tr><td> $title </td><td> $value </td></tr>";
	}

	/* Does this reduce the number of connections the pecl client keeps open? */
	function __destruct() {
		$this->close();
	}

}

//Headings, etc
function wpmm_page_start(){
	global $wpmm_manager;
	?>
		<div class="wrap"><h2>WP Memcached Manager</h2>
	<?php
	if ( ! $wpmm_manager->wpmm_check_memcache() ) {
		echo '<div id="message" class="error fade"><p> ERROR. You do not have the PHP PECL Memcache extension installed or compiled.</p>';
		echo '<p>Please have a look at the documentation on setting this up at the <a href="http://us.php.net/memcache">PECL site</a></p></div>';
		exit;
	}
}

//Builds the main page content
function wpmm_main_page() {
	global $wpmm_manager;

	wpmm_page_start();

	if ( ! empty( $wpmm_manager->memcached_servers )) { ?>
			<form action="?page=wp_memcached_manager" method="post">
			<div class="alignleft actions">
			<select name="memcache_server_id" class="select-action">
			<?php
		$wpmm_manager->wpmm_build_server_select( "Select memcached server to manage ", $_POST['memcache_server_id'] );

		wp_nonce_field('wpmm-select-server-nonce'); ?>
			<input type="submit" name="manage-action" value="Manage Server" class="button-secondary" />
			</form></div>
			<?php

		//This is the main stats screen of a memcache instance		
		if ( "Manage Server" == $_POST['manage-action'] ) {

			if (check_admin_referer('wpmm-select-server-nonce')) {
				//derive the server info from the server_id
				$strs = explode('-', $_POST['memcache_server_id'] );
				$host = $strs[0];
				$port = $strs[1];

				$single_server = new WPMemcachedServer( $memcached_server['server_id'], $host, $port );
				?>
					<p/>
					<table class="widefat">
					<thead>
					<tr>
					<th scope="col">Item</th>
					<th scope="col">Value</th>

					</tr>
					</thead>
					<tr>
					<td>Memcached Server Status</td>
					<?php
				//If we cannot connect, we throw an error and return
				$status = $single_server->wpmm_check_status();
				if ( false == $status ) {
					?>
						<td style="background: red; color: white;"><strong>Server offline or cannot connect. Please check your server and settings</strong>. </td></tr></table></div>
						<?php
					return;
				}
				else {
					?>
						<td style="background: green; color: white;"><strong>Server online. Connected.</strong></td></tr>
						<?php
				}
				$version = $single_server->wpmm_get_version();
				$single_server->wpmm_command_row( $version, "Memcached Version" ); 
				$single_server->wpmm_all_status_table();
				?>
					</tr>
					</td></tr></table><p/>
					<div class="alignleft actions">
					<form action="?page=wp_memcached_manager" method="post">
					<?php	wp_nonce_field('wpmm-manage-action-nonce'); ?>
				<input type="hidden" name="memcache_server_id" value="<?php echo $_POST['memcache_server_id']; ?>">
				<input type="Submit" name="manage-action" Value="Flush Memcache" class="button-secondary"> or <input type="Submit" name="manage-action" Value="View Data" class="button-secondary">
					</form>
					<?php
			} 	
		}
		//this is a confirmation of a flush action memcache instance
		elseif ( "Flush Memcache" == $_POST['manage-action'] ) {
			if (check_admin_referer('wpmm-manage-action-nonce')) {

				$strs = explode('-', $_POST['memcache_server_id'] );
				$host = $strs[0];
				$port = $strs[1];

				?>	<p/>
					<form action="?page=wp_memcached_manager" method="post">
					<?php	wp_nonce_field('wpmm-confirm-action-nonce'); ?>
					
					<table class="widefat">
					<thead>
					<tr>
					<th scope="col">Action</th>
					<th scope="col">Confirmation</th>
					</tr>
					</thead>
					<tr>
					<td style="background: yellow; color: black;"><strong>Are you sure you want to expire all data stored in this Memcached instance immediately?</strong></td>
					<td> 	Click this button to flush all data: 
							<input type="hidden" name="memcache_server_id" value="<?php echo $_POST['memcache_server_id']; ?>"> 
							<input type="Submit" name="manage-action" Value="Flush Confirm" class="button-secondary">
							</form>
					</td>
					</tr></table></div>	
				<?php
			}	
		}
		//confirmed, perform a flush
		elseif ( "Flush Confirm" == $_POST['manage-action'] ) {
			if (check_admin_referer('wpmm-confirm-action-nonce')) {
				
				$strs = explode('-', $_POST['memcache_server_id'] );
				$host = $strs[0];
				$port = $strs[1];
				
				$single_server = new WPMemcachedServer( $_POST['memcache_server_id'], $host, $port );
				$flushed = $single_server->wpmm_flush(); 
				
				if ( false == $flushed ) { ?>	
					<div id="message" class="error fade"><p>Memcached flush attempt failed for some reason.</p></div>
				<?php
					return;
				}
				else {
					?>
						<div id="message" class="updated fade"><p>Memcached flush successful. All keys expired.</p></div>
					<?php
				}
			}
		}
		//this is a confirmation of a slab dump on a memcache instance. 
		//this could be HORRIBLE on large datasets
		elseif ( "View Data" == $_POST['manage-action'] ) {
			if (check_admin_referer('wpmm-manage-action-nonce')) {

				$strs = explode('-', $_POST['memcache_server_id'] );
				$host = $strs[0];
				$port = $strs[1];

				?>	<p/>
					<form action="?page=wp_memcached_manager" method="post">
					<?php	wp_nonce_field('wpmm-confirm-action-nonce'); ?>
					
					<table class="widefat">
					<thead>
					<tr>
					<th scope="col">Action</th>
					<th scope="col">Confirmation</th>
					</tr>
					</thead>
					<tr>
					<td style="background: yellow; color: black;"><strong>WARNING: Memcached has no good way to list all keys, or iterate through the stored data in an orderly fashion. The view data action you are about to perform could take a really long time on large datasets and *could* be a blocking action on your memcached instance. Are you sure you want to view 100 keys of data stored in this Memcached instance?</strong></td>
					<td width="50%"> Click this button to view 100 keys of data: 
							<input type="hidden" name="memcache_server_id" value="<?php echo $_POST['memcache_server_id']; ?>"> 
							<input type="Submit" name="manage-action" Value="View Confirm" class="button-secondary">
							</form>
					</td>
					</tr></table></div>	
				<?php
			}	
		}
		elseif ( "View Confirm" == $_POST['manage-action'] ) {
			if (check_admin_referer('wpmm-confirm-action-nonce')) {
				//derive the server info from the server_id
				$strs = explode('-', $_POST['memcache_server_id'] );
				$host = $strs[0];
				$port = $strs[1];

				$single_server = new WPMemcachedServer( $memcached_server['server_id'], $host, $port );
				?>
					<p/>
					<table class="widefat">
					<thead>
					<tr>
					<th scope="col">Key</th>
					<th scope="col">Value</th>
					</tr>
					</thead>
			<?php		
					$single_server->wpmm_slab_walk( 100 );
			?>
					</table></div>
					<p/>
					<div class="alignleft actions">
					<form action="?page=wp_memcached_manager" method="post">
					<?php	wp_nonce_field('wpmm-manage-action-nonce'); ?>
				<input type="hidden" name="memcache_server_id" value="<?php echo $_POST['memcache_server_id']; ?>">
				<input type="Submit" name="manage-action" Value="Flush Memcache" class="button-secondary"> 
					</form>
			<?php		
			}
		}				
	}	

	wpmm_page_end();
}

function wpmm_edit_page(){
	global $wpmm_manager;

	wpmm_page_start();

	//if we are adding a new server
	if ($_POST['server_host']) {
		if (check_admin_referer('wpmm-options-add-server-nonce')) {
			if ( $wpmm_manager->wpmm_add_new_server( $_POST['server_host'], $_POST['server_port'] )) {
				?>
					<div id="message" class="updated fade">
					<p>New memcached server added successfully.</p>
					</div>
					<?php
			} else {
				?>
					<div id="message" class="error fade">
					<p>Couldn't add server. You probably have defined this host:port combination already.</p>
					</div>
					<?php
			}
		}
	}

	//if we are deleting an existing server
	if ( $_GET['delete'] ) {
		if ( check_admin_referer( 'wpmm-delete-server-nonce' )) {
			if($wpmm_manager->wpmm_delete_server( $_GET['delete'] )) {
				?>
					<div id="message" class="updated fade">
					<p>Memcached server deleted successfully.</p>
					</div>
					<?php
			} else {
				?>
					<div id="message" class="error fade">
					<p>Strange. I could not delete that server.</p>
					</div>
					<?php
			}
		}	
	}	

	?>
		<h3>
		Add New Memcached Server
		</h3>

		<form action="?page=wpmm-edit-servers" method="post">
		<?php

	wp_nonce_field('wpmm-options-add-server-nonce');

	?>
		<table class="form-table">

		<tr valign="top">
		<th scope="row">Server IP / Hostname:</th>
		<td>
		<input name="server_host" type="text" id="server_host" size="20" /> Eg: 127.0.0.1, or memcache.hostname.com
		</td>
		</tr>

		<tr valign="top">
		<th scope="row">Server Port:</th>
		<td>
		<input name="server_port" type="text" id="server_port" size="20" /> Eg: 11211
		</td>
		</tr>

		</table>

		<p class="submit">
		<input type="submit" name="Submit" value="Add Server" />
		</form>
		</p>


		<h3>Current Servers</h3>
		<table class="widefat">
		<thead>
		<tr>
		<th scope="col">Host</th>
		<th scope="col">Port</th>
		<th scope="col">&nbsp;</th>
		</tr>
		</thead>
		<?php

	if ( ! empty( $wpmm_manager->memcached_servers )) {
		foreach( $wpmm_manager->memcached_servers as $memcached_server ) {

			$single_server = new WPMemcachedServer( $memcached_server['server_id'], $memcached_server['server_host'], $memcached_server['server_port'] );
			print('<tr>');
			print('</td><td>' . $memcached_server['server_host'] . '</td><td>' . $memcached_server['server_port'] . '</td>');
			$delete_server_link = '?page=wpmm-edit-servers&delete=' . $memcached_server['server_id'];
			$delete_server_link = wp_nonce_url($delete_server_link, 'wpmm-delete-server-nonce');
			print('<td><a href="' . $delete_server_link . '"> Delete </a></td>');
			print('</tr>');
		}
	}

	?>
		</table>
		<?php
		
	wpmm_page_end();
}


function wpmm_page_end(){
	echo "</div>";
}

if ( class_exists('WPMemcachedManager') ) {
	$wpmm_manager = new WPMemcachedManager();
}


//safer to omit the final end tag?

