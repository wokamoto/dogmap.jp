<?php
/***************************************************************************
 *   @brief WP-Sentinel - Wordpress Security System .                      *
 *   @author Simone Margaritelli (aka evilsocket) <evilsocket@gmail.com>   *
 *                       		                                               *
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

if( !defined('WPS_INCLUDE') ){ die (); }

define( "BLOG_URL",         get_bloginfo('wpurl') );
define( 'WPS_ADMIN_PAGE',   BLOG_URL.'/wp-admin/options-general.php?page=wp-sentinel' );
define( 'WPS_ADMIN_URL',    BLOG_URL.'/wp-content/plugins/wp-sentinel/admin/' );
define( 'WPS_ADMIN_IMAGES', WPS_ADMIN_URL.'images/' );

/*
 * Little routine to get UTF-8 encoded data from layout files.
 */
function file_get_contents_utf8($filename)
{
    return utf8_decode(file_get_contents($filename));
}

function wps_install_menu()
{
    add_options_page( 'WP-Sentinel Options', 'WP-Sentinel', 'manage_options', 'wp-sentinel', 'wps_admin_main' );
}

function wps_admin_redirect( $sWhere )
{
    if( headers_sent() )
        die( "<script>document.location.href = '$sWhere';</script>" );
    else
    {
        header( "Location: $sWhere" );
        die();
    }
}

function wps_format_time( $time )
{
    $value = array();

    if($time >= 31556926){
        $v = floor($time/31556926);
        $value[ "year".($v > 1 ? 's' : '') ] = $v;
        $time = ($time % 31556926);
    }
    if($time >= 86400){
        $v = floor($time/86400);
        $value[ "day".($v > 1 ? 's' : '') ] = $v;
        $time = ($time % 86400);
    }
    if($time >= 3600){
        $v = floor($time/3600);
        $value[ "hour".($v > 1 ? 's' : '') ] = $v;
        $time = ($time % 3600);
    }
    if($time >= 60){
        $v = floor($time/60);
        $value[ "minute".($v > 1 ? 's' : '') ] = $v;
        $time = ($time % 60);
    }
    if( ($left = floor($time)) > 0 ){
        $value[ "second".($left > 1 ? 's' : '') ] = $left;
    }

    $repr = array();
    foreach( $value as $name => $v ){
        $repr[] = "$v $name";
    }

    return implode( ', ', $repr );
}

function wps_admin_daily()
{
    global $wpdb;

    /*
     * Load view.
     */
    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab     = 'daily';
    $sCurrentTabView = 'daily.view.php';

    $aErrors         = array();
    $aNotices        = array();

    /*
     * Dirty trick to create tables from 1.0.5 in case of update.
     */
    WPSentinel::createTables( FALSE );

    /*
     * Check for wipe or download action.
     */
    if( isset($_GET['wipe']) )
    {
        $wpdb->query
        (
            $wpdb->prepare
            (
                "DELETE FROM ".$wpdb->prefix."wps_logs WHERE timestamp >= %d",
                $_GET['wipe'] == 'daily' ? time() - 86400 : 0
            )
        );

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
    }
    else if( isset($_GET['delete']) && is_numeric($_GET['delete']) )
    {
        $wpdb->query
        (
            $wpdb->prepare
            (
                "DELETE FROM ".$wpdb->prefix."wps_logs WHERE id = %d;", $_GET['delete']
            )
        );

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
    }

    /*
     * Check for bans.
     */
    if( isset($_POST['ban']) && isset($_POST['ip']) && isset($_POST['duration']) )
    {
        if( !preg_match( "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_POST['ip'] ) )
            $aErrors[] = 'Invalid address.';

        else if( !is_numeric($_POST['duration']) )
            $aErrors[] = 'Invalid duration.';

        else
        {
            $address  = $_POST['ip'];
            $duration = (int)$_POST['duration'];
            $query	  = $wpdb->prepare( "INSERT INTO ".$wpdb->prefix."wps_bans (timestamp,address,duration) VALUES( %d, %s, %d );", time(), $address, $duration * 3600 );

            $wpdb->query($query);

            $aNotices[] = "<b>'$address'</b> will be banned for $duration hours from now.";
        }
    }
    else if( isset($_GET['unban']) )
    {
        $address = $_GET['unban'];

        if( !preg_match( "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $address ) )
            $aErrors[] = 'Invalid address.';

        else
        {
            $wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."wps_bans WHERE address = %s;", $address ) );

            wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
        }
    }

    /*
     * First of all, do some writability checkings
     */
    $checks = array( 'vectors', 'layouts' );
    foreach( $checks as $check )
    {
        $item = WPS_PATH.$check;
        if( !is_writable($item) )
            $aErrors[] = "<b>'$item'</b> is not writable!!!";
    }

    $aLogs = $wpdb->get_results
    (
        $wpdb->prepare
        (
            "SELECT * FROM ".$wpdb->prefix."wps_logs WHERE timestamp >= %d ORDER BY id DESC",
            time() - 86400
        )
    );

    $nLogs = count($aLogs);

    require_once 'views/main.view.php';
}

function wps_admin_full()
{
    global $wpdb;

    /*
     * Load view.
     */
    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab     = 'full';
    $sCurrentTabView = 'full.view.php';

    $aErrors         = array();
    $aNotices        = array();

    /*
     * Check for wipe or download action.
     */
    if( isset($_GET['wipe']) )
    {
        $wpdb->query
        (
            $wpdb->prepare
            (
                "DELETE FROM ".$wpdb->prefix."wps_logs WHERE timestamp >= %d",
                $_GET['wipe'] == 'daily' ? time() - 86400 : 0
            )
        );

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
    }
    else if( isset($_GET['delete']) && is_numeric($_GET['delete']) )
    {
        $wpdb->query
        (
            $wpdb->prepare
            (
                "DELETE FROM ".$wpdb->prefix."wps_logs WHERE id = %d;", $_GET['delete']
            )
        );

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
    }

    /*
     * Check for bans.
     */
    if( isset($_POST['ban']) && isset($_POST['ip']) && isset($_POST['duration']) )
    {
        if( !preg_match( "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_POST['ip'] ) )
            $aErrors[] = 'Invalid address.';

        else if( !is_numeric($_POST['duration']) )
            $aErrors[] = 'Invalid duration.';

        else
        {
            $address  = $_POST['ip'];
            $duration = (int)$_POST['duration'];
            $query	  = $wpdb->prepare( "INSERT INTO ".$wpdb->prefix."wps_bans (timestamp,address,duration) VALUES( %d, %s, %d );", time(), $address, $duration * 3600 );

            $wpdb->query($query);

            $aNotices[] = "<b>'$address'</b> will be banned for $duration hours from now.";
        }
    }
    else if( isset($_GET['unban']) )
    {
        $address = $_GET['unban'];

        if( !preg_match( "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $address ) )
            $aErrors[] = 'Invalid address.';

        else
        {
            $wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."wps_bans WHERE address = %s;", $address ) );

            wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=daily' );
        }
    }

    /*
     * First of all, do some writability checkings
     */
    $checks = array( 'vectors', 'layouts' );
    foreach( $checks as $check )
    {
        $item = WPS_PATH.$check;
        if( !is_writable($item) )
            $aErrors[] = "<b>'$item'</b> is not writable!!!";
    }

    if( isset($_GET['pageno']) && is_numeric($_GET['pageno']) )
        $pageno = (int)$_GET['pageno'];

    else
        $pageno = 1;

    $num_rows = $wpdb->get_var( "SELECT COUNT(id) FROM ".$wpdb->prefix."wps_logs;" );
    $max_rows = 20;
    $lastpage = ceil($num_rows/$max_rows);

    if( $pageno > $lastpage || $pageno < 1 )
        $pageno = 1;

    $aLogs   = $wpdb->get_results
    (
        $wpdb->prepare
        (
            "SELECT * FROM ".$wpdb->prefix."wps_logs ORDER BY id DESC LIMIT %d,%d;",
            ($pageno - 1) * $max_rows,
            $max_rows
        )
    );

    $nLogs = count($aLogs);

    require_once 'views/main.view.php';
}

function wps_admin_banned()
{
    global $wpdb;

    /*
     * Load view.
     */
    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab     = 'ban';
    $sCurrentTabView = 'banned.view.php';

    $aErrors         = array();
    $aNotices        = array();

    /*
	 * Delete expired bans.
	 */
    $wpdb->query( "DELETE FROM ".$wpdb->prefix."wps_bans WHERE timestamp + duration <= ".time() );

    if( isset($_GET['delete']) && is_numeric($_GET['delete']) )
    {
        $wpdb->query
        (
            $wpdb->prepare
            (
                "DELETE FROM ".$wpdb->prefix."wps_bans WHERE id = %d",
                $_GET['delete']
            )
        );

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=ban' );
    }
    else if( isset($_GET['action']) )
    {
        if( $_GET['action'] == "unban" )
            $wpdb->query( "DELETE FROM ".$wpdb->prefix."wps_bans" );

        else if( $_GET['action'] == "ban" )
        {
            $attackers = $wpdb->get_results( "SELECT DISTINCT(address) FROM ".$wpdb->prefix."wps_logs", ARRAY_A );

            foreach( $attackers as $address )
            {
                $address = $address['address'];
                if( WPSentinel::isAddressBanned($address) )
                {
                    $wpdb->query
                    (
                        $wpdb->prepare
                        (
                            "UPDATE ".$wpdb->prefix."wps_bans SET timestamp = %d, duration = %d WHERE address = %s",
                            time(),
                            24 * 3600,
                            $address
                        )
                    );
                }
                else
                {
                    $wpdb->query
                    (
                        $wpdb->prepare
                        (
                            "INSERT INTO ".$wpdb->prefix."wps_bans (timestamp,address,duration) VALUES( %d, %s, %d )",
                            time(),
                            $address,
                            24 * 3600
                        )
                    );
                }
            }
        }

        wps_admin_redirect( WPS_ADMIN_PAGE.'&tab=ban' );
    }

    if( isset($_GET['pageno']) && is_numeric($_GET['pageno']) )
        $pageno = (int)$_GET['pageno'];
    else
        $pageno = 1;

    $num_rows = $wpdb->get_var( "SELECT COUNT(id) FROM ".$wpdb->prefix."wps_bans" );
    $max_rows = 20;
    $lastpage = ceil($num_rows/$max_rows);

    if( $pageno > $lastpage || $pageno < 1 )
        $pageno = 1;

    $aBans = $wpdb->get_results
    (
        $wpdb->prepare
        (
            "SELECT * FROM ".$wpdb->prefix."wps_bans ORDER BY id DESC LIMIT %d,%d",
            ($pageno - 1) * $max_rows,
            $max_rows
        )
    );

    $nBans = count($aBans);

    require_once 'views/main.view.php';
}

function wps_admin_layouts()
{
    /*
     * Load view.
     */
    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab     = 'layouts';
    $sCurrentTabView = 'layouts.view.php';

    $aErrors         = array();
    $aNotices        = array();

    if( isset($_POST['update']) )
    {
        if( !is_writable(WPS_PATH."layouts/email.row.layout") )
            $aErrors[] = "email.row.layout is not writable!";

        else if( !is_writable(WPS_PATH."layouts/email.layout") )
            $aErrors[] = "email.layout is not writable!";

        else if( !is_writable(WPS_PATH."layouts/alarm.row.layout") )
            $aErrors[] = "alarm.row.layout is not writable!";

        else if( !is_writable(WPS_PATH."layouts/alarm.layout") )
            $aErrors[] = "alarm.layout is not writable!";

        else if( !is_writable(WPS_PATH."layouts/banned.layout") )
            $aErrors[] = "banned.layout is not writable!";

        foreach( $_POST as $key => $value )
        {
            $_POST[$key] = html_entity_decode($value);
            if( HAVE_GPC )
                $_POST[$key] = stripslashes($_POST[$key]);

            $_POST[$key] = urldecode($_POST[$key]);
        }

        if( !file_put_contents( WPS_PATH."layouts/email.row.layout", $_POST['email_row_layout'] ) )
            $aErrors[] = "Error during file update.";

        else if( !file_put_contents( WPS_PATH."layouts/email.layout", $_POST['email_layout'] ) )
            $aErrors[] = "Error during file update.";

        else if( !file_put_contents( WPS_PATH."layouts/alarm.row.layout", $_POST['alarm_row_layout'] ) )
            $aErrors[] = "Error during file update.";

        else if( !file_put_contents( WPS_PATH."layouts/alarm.layout", $_POST['alarm_layout'] ) )
            $aErrors[] = "Error during file update.";

        else if( !file_put_contents( WPS_PATH."layouts/banned.layout", $_POST['banned_layout'] ) )
            $aErrors[] = "Error during file update.";

        if( !$aErrors )
            $aNotices[] = 'Succesfully updated.';
    }

    $email_row_layout = @file_get_contents_utf8(WPS_PATH."layouts/email.row.layout");
    $email_layout     = @file_get_contents_utf8(WPS_PATH."layouts/email.layout");
    $alarm_row_layout = @file_get_contents_utf8(WPS_PATH."layouts/alarm.row.layout");
    $alarm_layout     = @file_get_contents_utf8(WPS_PATH."layouts/alarm.layout");
    $banned_layout    = @file_get_contents_utf8(WPS_PATH."layouts/banned.layout");

    require_once 'views/main.view.php';
}

function wps_admin_settings()
{
    /*
    * Load view.
    */
    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab     = 'settings';
    $sCurrentTabView = 'settings.view.php';

    $aErrors         = array();
    $aNotices        = array();

    $config_path = WPS_PATH.WPSentinel::CONFIG_FILE;

    if( isset($_POST['update']) )
    {
        if( !is_writable($config_path) )
            $aErrors[] = "Configuration file '$config_path' is not writable!";

        $tmp 	   = explode( ',', $_POST['whitelist'] );
        $whitelist = array();
        foreach( $tmp as $item )
        {
            $item = trim($item);
            if( $item != '' ){
                $whitelist[] = $item;
            }
        }

        $tmp 	      = explode( ',', $_POST['allowed_html'] );
        $allowed_html = array();
        foreach( $tmp as $item )
        {
            $item = trim($item);
            if( $item != '' ){
                $allowed_html[] = $item;
            }
        }

        $aConfig = array
        (
            /* enable or disable wp-sentinel filtering */
            "enabled"      => $_POST['enabled'] == 1 ? TRUE : FALSE,
            /* enable or disable email notifications to the blog administrator */
            "notification" => $_POST['notification'] == 1 ? TRUE : FALSE,
            /* enable or disable logging */
            "logging"      => $_POST['logging'] == 1 ? TRUE : FALSE,
            /* flood delay time in seconds */
            "flood_time"   => (INT)$_POST['flood_time'],
            /* enable or disable ip auto banning */
            "autoban"	   => $_POST['autoban'] == 1 ? TRUE : FALSE,
            /* number of attacks to get banned */
            "ban_attacks"  => (INT)$_POST['ban_attacks'],
            /* hours of auto banning */
            "ban_time"	   => (INT)$_POST['ban_time'],
            /* variables to be ignored */
            "whitelist"    => $whitelist,
            /* allowed HTML tags */
            "allowed_html" => $allowed_html,
            /* max login attempts before getting blocked */
            "max_logins"   => (INT)$_POST['max_logins']
        );

        $data = json_encode($aConfig);

        if( !file_put_contents( $config_path, $data ) )
            $aErrors[] = "Error during file update.";

        else
            $aNotices[] = "Succesfully updated.";
    }

    $cfg = json_decode( @file_get_contents( $config_path ) );

    if( !$cfg )
        $aErrors[] = "Could not read configuration file $config_path.";

    require_once 'views/main.view.php';
}

function wps_admin_main()
{
    if( !current_user_can('manage_options') )
    {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    $aAvailableTabs = array
    (
        'daily'    => 'Daily Logs',
        'full'     => 'Full History',
        'ban'	   => 'Manage Bans',
        'layouts'  => 'Layouts',
        'settings' => 'Settings'
    );

    $sCurrentTab = isset($_GET['tab']) && @in_array( $_GET['tab'], array_keys( $aAvailableTabs ) ) ? $_GET['tab'] : 'daily';

    switch( $sCurrentTab )
    {

        case 'full'     : wps_admin_full();     break;
        case 'ban'		: wps_admin_banned();   break;
        case 'layouts'  : wps_admin_layouts();  break;
        case 'settings' : wps_admin_settings(); break;

        default 		: wps_admin_daily();
    }
}
?>