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

if( !defined('WPS_INCLUDE') ){ die (); }

class IsAdminHook implements IWPSHook
{
    public function __construct( $oSentinel )
    {
        $this->aEnv = $oSentinel->getEnvironment();
    }

    public function run( $mArg )
    {      
      // if the user is an administrator
      if( WPSentinel::isBlogAdmin() )
      {
        // if the request target is the admin panel, perform cross site request forgery checking
        if( preg_match( '|/wp\-admin/|i', $_SERVER["REQUEST_URI"] ) )
        {
          // any post request ?
          if( count( array_diff( array_values($this->aEnv['POST']), array('') ) ) )
          {
            $sValidReferer     = get_bloginfo('wpurl');
            $sValidRefererRule = '^'.preg_quote( $sValidReferer, '/' );

            // referer not set or not allowed to POST to the admin panel
            if( !isset($_SERVER['HTTP_REFERER']) || !preg_match( "/$sValidRefererRule/i", $_SERVER['HTTP_REFERER'] ) )
            {
              // add CSRF alarm!!!
              WPSentinel::addAlarm( 'HTTP_REFERER', 'HTTP_REFERER', $_SERVER['HTTP_REFERER'], 'invalid or null referer for wordpress administration panel request', $sValidRefererRule );

              return self::ACTION_ALARM;
            }
          }
        }

        // User is an admin and no alarm was triggered, skip next rules checkings.
        return self::ACTION_SKIP;
      }

      return self::ACTION_NONE;
    }
}

$this->addHook( new IsAdminHook($this), IWPSHook::PRE_HOOK );

?>