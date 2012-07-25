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

class CheckBruteforceHook implements IWPSHook
{
    public function __construct( $oSentinel )
    {
        $this->oDb     = $oSentinel->getDatabase();
        $this->oConfig = $oSentinel->getConfiguration();
    }

    private function getAttempt( $sAddress )
    {
        $oAttempt = $this->oDb->get_row
        (
            $this->oDb->prepare
            (
                'SELECT id, timestamp, attempts FROM '.$this->oDb->prefix.'wps_logins WHERE address = %s',
                $sAddress
            )
        );

        // Fix get_row stupidity
        if( $oAttempt )
        {
            $oAttempt->id        = (int)$oAttempt->id;
            $oAttempt->timestamp = (int)$oAttempt->timestamp;
            $oAttempt->attempts  = (int)$oAttempt->attempts;
        }

        return $oAttempt;
    }

    public function run( $mArg )
    {
        // Login attempt ?
        if( isset($_POST['log']) && isset($_POST['pwd']) )
        {
            $sAddress = WPSentinel::getAddress();
            $oAttempt = $this->getAttempt($sAddress);

            // first login attempt, create the record
            if( !$oAttempt )
            {
                $this->oDb->query
                (
                    $this->oDb->prepare
                    (
                        'INSERT INTO '.$this->oDb->prefix.'wps_logins (timestamp,address,attempts) VALUES ( %d, %s, 1 )',
                        time(),
                        $sAddress
                    )
                );
            }
            // threshold not exceeded, update the record
            else if( $oAttempt->attempts < $this->oConfig->max_logins )
            {
                $this->oDb->query
                (
                    $this->oDb->prepare
                    (
                        'UPDATE '.$this->oDb->prefix.'wps_logins SET timestamp = %d, attempts = attempts + 1 WHERE id = %d',
                        time(),
                        $oAttempt->id
                    )
                );
            }
            // max login attempts exceeded, but the record is too old, reset the timestamp and number of attempts
            else if( (time() - $oAttempt->timestamp) > $this->oConfig->flood_time )
            {
                $this->oDb->query
                (
                    $this->oDb->prepare
                    (
                        'UPDATE '.$this->oDb->prefix.'wps_logins SET timestamp = %d, attempts = 1 WHERE id = %d',
                        time(),
                        $oAttempt->id
                    )
                );
            }
            // ok, here we are > max attempts and < min time lapse, we are under bruteforcing!!!
            else
            {
                WPSentinel::addAlarm( 'POST', 'login && password', $_POST['log'].':'.$_POST['pwd'], 'wordpress login bruteforcing', 'CheckBruteforceHook' );

                return self::ACTION_ALARM;
            }
        }
        else

        return self::ACTION_NONE;
    }
}

$this->addHook( new CheckBruteforceHook($this), IWPSHook::PRE_HOOK );

?>