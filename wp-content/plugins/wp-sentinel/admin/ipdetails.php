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
else if( !isset($_GET['ip']) || !preg_match( "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_GET['ip']) ){
	die( "Invalid address!" );
}

$ip    = $_GET['ip'];
$query = $wpdb->prepare( 
"SELECT 
COUNT( id ) AS total, 
(SELECT COUNT(id) FROM ".$wpdb->prefix."wps_logs WHERE timestamp >= %d AND address = %s) AS today,
MIN( TIMESTAMP ) AS first_seen, 
MAX( TIMESTAMP ) AS last_seen, 
agent
FROM ".$wpdb->prefix."wps_logs
WHERE address = %s", time() - 86400, $ip, $ip );
$info = $wpdb->get_row($query);

if( !$info ){
	die( "No info!" );
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
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">ip</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><a href='http://whois.domaintools.com/<?= $ip ?>' target='_blank'><?= $ip ?></a></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">hostname</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= gethostbyaddr($ip) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">Total Events</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= $info->total ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">Today's Events</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= $info->today ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">First Seen</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= strftime( "%c", $info->first_seen ) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">Last Seen</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= strftime( "%c", $info->last_seen ) ?></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">user-agent</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><?= htmlentities(stripslashes($info->agent)) ?></td>
	</tr>
</table>
</body>
</html>