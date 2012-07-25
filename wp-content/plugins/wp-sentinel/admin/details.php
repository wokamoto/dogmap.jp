<?php
/***************************************************************************
 *   @brief WP-Sentinel - Wordpress Security System .                      *
 *   @author Simone Margaritelli (aka evilsocket) <evilsocket@gmail.com>   *
 *                       		                                           *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/
 
require_once("../../../../wp-config.php");

if( !current_user_can('manage_options') ){
	die( "You don't have privileges to do this!" ); 
}
else if( !isset($_GET['id']) || !is_numeric($_GET['id']) ){
	die( "Invalid event id!" );
}

$id    = (int)$_GET['id'];
$query = $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."wps_logs WHERE id = %d;", $id );	
$event = $wpdb->get_row($query);

if( !$event ){
	die( "No such event!" );
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
</head>
<body style="font-family: Verdana, Geneva, sans-serif; font-size: 10px; background-color: #fff;">
<table style="padding: 10px; width: 500px;">
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">id</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= $event->id ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">timestamp</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= strftime( "%c", $event->timestamp ) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">address</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= $event->address ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">user-agent</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->agent)) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">referer</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->referer)) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">wp username</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->username)) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">scope</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= $event->scope ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">variable</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->variable)) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">content</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;">
		<?php
		$content = stripslashes($event->content);
		$vector  = ABSPATH."/wp-content/plugins/wp-sentinel/vectors/".md5($content).".dat";
		if( strtolower($event->message) == 'remote file inclusion' && file_exists($vector) ){
			echo "<a href='".get_bloginfo('wpurl')."/wp-content/plugins/wp-sentinel/vectors/".md5($content).".dat' target='_blank'>".htmlentities($content)."</a>";
		}
		else{
			echo htmlentities($content);
		}
		?>
		</td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">message</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->message)) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">rule</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($event->rule)) ?></td>
	</tr>
</table>
</body>
</html>