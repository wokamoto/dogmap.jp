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

define( 'HAVE_GPC', ( function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ) );
define( "WPS_PATH", ABSPATH.'wp-content/plugins/wp-sentinel/' );

include_once WPS_PATH.'classes/ihook.class.php';
include_once WPS_PATH.'classes/report.class.php';

class WPSentinel
{
    const VERSION       = '2.0.3';
    const VERSION_FIELD = 'wp_sentinel_version';

    const RULE_FILE     = 'rules.json';
    const CONFIG_FILE   = 'configuration.json';

    const HOOKS_PATH    = 'hooks';

    private static $oInstance = NULL;

    public static $aScopes = array
    (
      'GET',
      'POST',
      'COOKIE'
    );

    private $oConfiguration = NULL;
    private $aRules         = NULL;
    private $aEnvironment   = NULL;
    private $oDatabase      = NULL;
    private $oReport        = NULL;
    private $aHooks         = NULL;

    private static $aReservedAddressRanges = array
    (
        array( '0.0.0.0',       '2.255.255.255'   ),
        array( '10.0.0.0',      '10.255.255.255'  ),
        array( '127.0.0.0',     '127.255.255.255' ),
        array( '169.254.0.0',   '169.254.255.255' ),
        array( '172.16.0.0',    '172.31.255.255'  ),
        array( '192.0.2.0',     '192.0.2.255'     ),
        array( '192.168.0.0',   '192.168.255.255' ),
        array( '255.255.255.0', '255.255.255.255' )
    );

    private static $aAddressFields = array
    (
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );

    /*
     * Create WP-Sentinel tables.
     */
    public static function createTables( $bOverwrite = TRUE )
    {
        global $wpdb;

        require_once( ABSPATH.'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE ".($bOverwrite ? '' : 'IF NOT EXISTS')." ".$wpdb->prefix."wps_logs (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    timestamp BIGINT(11) DEFAULT '0' NOT NULL,
                    address VARCHAR(15) NOT NULL,
                    agent VARCHAR(255) NOT NULL,
                    referer VARCHAR(255) NOT NULL,
                    username VARCHAR(25) NOT NULL,
                    scope VARCHAR(25) NOT NULL,
                    variable VARCHAR(50) NOT NULL,
                    content TEXT NOT NULL,
                    message TEXT NOT NULL,
                    rule VARCHAR(50) NOT NULL,
                    UNIQUE KEY id (id)
                );";

        $wpdb->query($sql);

        $sql = "CREATE TABLE ".($bOverwrite ? '' : 'IF NOT EXISTS')." ".$wpdb->prefix."wps_logins (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    timestamp BIGINT(11) DEFAULT '0' NOT NULL,
                    address VARCHAR(15) NOT NULL,
                    attempts INT NOT NULL,
                    UNIQUE KEY id (id)
                );";

        $wpdb->query($sql);


        $sql = "CREATE TABLE ".($bOverwrite ? '' : 'IF NOT EXISTS')." ".$wpdb->prefix."wps_bans (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    timestamp BIGINT(11) DEFAULT '0' NOT NULL,
                    address VARCHAR(15) NOT NULL,
                    duration BIGINT(11) DEFAULT '0' NOT NULL,
                    UNIQUE KEY id (id)
                );";

        $wpdb->query($sql);
    }

    /*
     * Handle activation and installation.
     */
    public static function install()
    {
        $sInstalledVersion = get_option( self::VERSION_FIELD );
        /*
         * If not installed or too old to have those tables.
         */
        if( !$sInstalledVersion || version_compare( $sInstalledVersion, '1.0.6' ) >= 0 )
        {
            self::createTables();
        }

        update_option( self::VERSION_FIELD, self::VERSION );
    }

    /*
     * Check if a given address is valid.
     */
    public static function isValidAddress( $sAddress )
    {
        $iAddress = ip2long($sAddress);
        if( !empty($sAddress) && $iAddress != -1 )
        {
            foreach( self::$aReservedAddressRanges as $aRange )
            {
                $iMin = ip2long( $aRange[0] );
                $iMax = ip2long( $aRange[1] );

                if( $iAddress >= $iMin && $iAddress <= $iMax )
                    return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    /*
     * Return the real address of the connected client.
     */
    public static function getAddress()
    {
        foreach( self::$aAddressFields as $sField )
        {
            if( isset($_SERVER[$sField]) )
            {
                $aAddresses = explode( ',', $_SERVER[$sField] );
                foreach( $aAddresses as $sAddress )
                {
                    $sAddress = trim( $sAddress );
                    if( $sAddress && self::isValidAddress($sAddress) )
                        return $sAddress;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    public static function getInstance()
    {
        return self::$oInstance;
    }
    
    public static function isBlogAdmin()
    {
      return isset( self::getInstance()->aEnvironment['USER']->caps['administrator'] ) && self::getInstance()->aEnvironment['USER']->caps['administrator'] == 1; 
    }

    public static function addAlarm( $sScope, $sVariableName, $sVariableData, $sAlarmDescription, $sRule )
    {
        self::getInstance()->oReport->addAlarm( $sScope, $sVariableName, $sVariableData, $sAlarmDescription, $sRule );
    }

    public function addHook( $oHook, $iType )
    {
        $this->aHooks[ $iType ][] = $oHook;
    }

    private function isBanned( $sAddress )
    {
        return $this->oDatabase->get_var
        (
            $this->oDatabase->prepare
            (
                'SELECT COUNT(1) AS banned FROM '.$this->oDatabase->prefix.'wps_bans WHERE ( timestamp + duration ) > %d AND address = %s',
                time(),
                $sAddress
            )
        );
    }

    public static function isAddressBanned( $sAddress )
    {
        global $wpdb;

        return $wpdb->get_var
        (
            $wpdb->prepare
            (
                'SELECT COUNT(1) AS banned FROM '.$wpdb->prefix.'wps_bans WHERE ( timestamp + duration ) > %d AND address = %s',
                time(),
                $sAddress
            )
        );
    }

    private function addBan( $sAddress )
    {
        $this->oDatabase->query
        (
            $this->oDatabase->prepare
            (
                'INSERT INTO '.$this->oDatabase->prefix.'wps_bans (timestamp,address,duration) VALUES( %d, %s, %d );',
                time(),
                $sAddress,
                $this->oConfiguration->ban_time * 3600
            )
        );
    }

    private function getHits( $sAddress )
    {
        return $this->oDatabase->get_var
        (
            $this->oDatabase->prepare
            (
                'SELECT COUNT(id) FROM '.$this->oDatabase->prefix.'wps_logs WHERE address = %s',
                $sAddress
            )
        );
    }

    private function isFlooding( $sAddress )
    {
        $iLastHit = (INT)$this->oDatabase->get_var
        (
            $this->oDatabase->prepare
            (
                'SELECT MAX(timestamp) FROM '.$this->oDatabase->prefix.'wps_logs WHERE address = %s',
                $sAddress
            )
        );

        if( $iLastHit )
        {
            // yep, we are getting flooded!
            if( ( time() - $iLastHit ) < $this->oConfiguration->flood_time )
                return TRUE;
        }

        return FALSE;
    }

    private function render( $iFormat )
    {
        /* disable caching for this page */
        @define( 'DONOTCACHEPAGE', TRUE );
        @define( 'WP_CACHE', 	  FALSE );

        header('HTTP/1.1 403 Forbidden');

        die( $this->oReport->get( $iFormat ) );
    }

    private function runHooks( $iLevel, $mArg = NULL )
    {
        $aReturns = array();

        foreach( $this->aHooks[ $iLevel ] as $oHook )
        {
            $aReturns[] = $oHook->run( $mArg );
        }

        return $aReturns;
    }

    public function __construct( $oDb )
    {
        self::$oInstance = $this;

        /*
         * Load configuration and rules.
         */
        $this->oConfiguration = json_decode( @file_get_contents( WPS_PATH.self::CONFIG_FILE ) );

        if( $this->oConfiguration === NULL )
            die( 'ERROR: Could not load WP-Sentinel configuration from '.WPS_PATH.self::CONFIG_FILE );

        $this->aRules = json_decode( @file_get_contents( WPS_PATH.self::RULE_FILE ) );

        if( $this->aRules === NULL )
            die( 'ERROR: Could not load WP-Sentinel rules from '.WPS_PATH.self::RULE_FILE );

        /*
         * Prepare environment to be processed.
         */
        $oWpUser         = wp_get_current_user();
        $this->oReport   = new WPSReport( $oWpUser, $oDb );
        $this->oDatabase = $oDb;

        $this->aEnvironment = array
        (
            'GET'    => $_GET,
            'POST'   => $_POST,
            'COOKIE' => $_COOKIE,
            'USER'   => $oWpUser
        );

    	// eventually flatten arrays
        foreach( self::$aScopes as $sScope )
        {
            $aData = $this->aEnvironment[$sScope];
            foreach( $aData as $sName => $mValue )
            {
                if( is_array($mValue) )
                {
                    foreach( $mValue as $sKey => $sItem )
                    {
                        $this->aEnvironment[$sScope][ $sName.'_'.$sKey ] = is_array($sItem) ? array_values($sItem) : $sItem;
                    }

                    unset( $this->aEnvironment[$sScope][$sName] );
                }
            }
        }

        // handle values
        foreach( self::$aScopes as $sScope )
        {
            $aData = $this->aEnvironment[$sScope];
            foreach( $aData as $sName => $sValue )
            {
                // if magic quotes are on, remove slashes from value
                $sValue = HAVE_GPC ? @stripslashes($sValue) : $sValue;
                // urldecode the value
                $sValue = @urldecode($sValue);
                // trim it
                $sValue = @trim($sValue);
                // encode to ASCII encoding
                if( function_exists('iconv') )
                    $sValue = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $sValue );

                // update the environment
                $this->aEnvironment[$sScope][$sName] = $sValue;
            }
        }

        /*
        * Load hooks.
        */
        $this->aHooks = array
        (
            IWPSHook::PRE_HOOK   => array(),
            IWPSHook::ALARM_HOOK => array()
        );

        if( ( $hDir = opendir( WPS_PATH.self::HOOKS_PATH ) ) )
        {
            while( ( $sFileName = readdir($hDir)) !== FALSE )
            {
                if( preg_match( '/^.+\.hook\.php$/i', $sFileName ) )
                {
                    require_once WPS_PATH.self::HOOKS_PATH.'/'.$sFileName;
                }
            }

            closedir($hDir);
        }        
    }

    public function run()
    {
      if( $this->isEnabled() == FALSE )
        return;

      $sAddress = self::getAddress();
      
      /*
       * First of all, check if the address is banned.
       */
      if( $this->isBanned( $sAddress ) && !self::isBlogAdmin() )
          $this->render( WPSReport::FORMAT_BANNED );

      /*
       * Run pre hooks.
       */
      $aPreHooksResults = $this->runHooks( IWPSHook::PRE_HOOK );

      // Do we have a ban action ?
      if( in_array( IWPSHook::ACTION_BAN, $aPreHooksResults ) && !self::isBlogAdmin() )
      {
        $this->addBan($sAddress);
        $this->render( WPSReport::FORMAT_BANNED );
      }
      // If none of the pre hooks specified to skip the rules checks ...
      else if( in_array( IWPSHook::ACTION_SKIP, $aPreHooksResults ) == FALSE )
      {
        /*
         * Perform basic rules check for normal users (both logged and not logged).
         */
        foreach( self::$aScopes as $sScope )
        {
          $aData = $this->aEnvironment[$sScope];
          // loop each request variable
          foreach( $aData as $sName => $sValue )
          {
            // skip whitelisted variables
            if( in_array( $sName, $this->oConfiguration->whitelist ) )
              continue;

            /* loop each rule */
            foreach( $this->aRules as $oRule )
            {
              // do we have a match?
              if( preg_match( '/'.$oRule->pattern.'/i', $sValue ) )
              {
                // Run ALARM hooks
                $aAlarmHooksResults = $this->runHooks( IWPSHook::ALARM_HOOK, array( $sName, $sValue, $oRule ) );
                // Do we have a ban action ?
                if( in_array( IWPSHook::ACTION_BAN, $aAlarmHooksResults ) )
                {
                  $this->addBan($sAddress);
                  $this->render( WPSReport::FORMAT_BANNED );
                }
                // Add the alarm only if ACTION_SKIP was not specified by any hook
                else if( in_array( IWPSHook::ACTION_SKIP, $aAlarmHooksResults ) == FALSE )
                  $this->oReport->addAlarm( $sScope, $sName, $sValue, $oRule->type, $oRule->pattern );
              }
            }
          }
        }

        if( $this->oReport->hasAlarms() )
        {
          // is autoban enabled ?
          if( $this->oConfiguration->autoban )
          {
            // if current hits + total hits >= max attacks then ban this guy
            if( ( $this->getHits($sAddress) + $this->oReport->entries() ) >= $this->oConfiguration->ban_attacks )
              $this->addBan( $sAddress );
          }

          // log and notify only if we are not getting flooded
          if( $this->isFlooding( $sAddress ) == FALSE )
          {
            // handle logging
            if( $this->oConfiguration->logging )
              $this->oReport->log();

            // handle email notification
            if( $this->oConfiguration->notification )
            {
              $sAdminEmail = get_bloginfo("admin_email");
              $sMessage    = $this->oReport->get( WPSReport::FORMAT_EMAIL );
              $sSubject    = 'WP-Sentinel Alarm Report';
              $sHeaders    = "From: $sAdminEmail\r\n" .
                             "X-Mailer: PHP/".phpversion();

              @mail( $sAdminEmail, $sSubject, $sMessage, $sHeaders );
            }
          }

          $this->render( WPSReport::FORMAT_USER );
        }
      }        
    }

    public function isEnabled()
    {
        return $this->oConfiguration->enabled;
    }

    public function getEnvironment()
    {
        return $this->aEnvironment;
    }

    public function getDatabase()
    {
        return $this->oDatabase;
    }

    public function getConfiguration()
    {
        return $this->oConfiguration;
    }
}

?>