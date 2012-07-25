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

$ip = $_GET['ip'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
</head>
<form method="post">
<body style="font-family: Verdana, Geneva, sans-serif; font-size: 10px; background-color: #fff;">
<table style="padding: 10px; width: 400px;">
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">address</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><input type="text" name="ip" value="<?= $ip ?>" READONLY="true" size="40"/></td>
	</tr>
	<tr>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;font-weight: bold; text-transform: uppercase; padding-right: 10px;">Ban duration (hours)</td>
		<td style="font-family: Verdana, Geneva, sans-serif; font-size: 10px;"><input type="text" name="duration" value="24" size="40"/></td>
	</tr>
	<tr>
		<td colspan="2" align="left">
			<br>
			<input type="submit" name="ban" value="Ban" class="button-primary"/>
		</td>
	</tr>
</table>
</form>
</body>
</html>