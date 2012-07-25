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

$action = isset($_GET['action']) ? $_GET['action'] : 'daily';
switch( $action ){
	// download daily log
	case 'daily' :
		$query = $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."wps_logs WHERE timestamp >= %d ORDER BY id DESC;", time() - 86400 );	
	break;
	// archive and download every log
	case 'full' :
		$query = $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."wps_logs ORDER BY id DESC;" );		
  	 break;
}

$logs 	   = $wpdb->get_results( $query, ARRAY_N );
$outstream = fopen("php://output", 'w');

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=log.csv");
header("Content-Transfer-Encoding: binary");

foreach( $logs as $log ){
	fputcsv( $outstream, $log, ',', '"' );
}

?>