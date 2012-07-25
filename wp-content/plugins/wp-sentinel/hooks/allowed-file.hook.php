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

class AllowedFileHook implements IWPSHook
{
    public function __construct( $oSentinel )
    {

    }

    public function run( $mArg )
    {
        list( $sVariableName, $sVariableValue, $oRule ) = $mArg;

        if( $oRule->label == 'RFI' )
        {
            $sRemoteFile = trim($sVariableValue);
            $sHash       = md5($sVariableValue);
            $sLocalFile  = WPS_PATH."vectors/$sHash.dat";
            $sContent	 = NULL;

            // file already cached :)
            if( file_exists( $sLocalFile ) )
                $sContent = @file_get_contents($sLocalFile);
            // fetch the file and add it to the cache
            else
            {
                $sContent = @file_get_contents($sRemoteFile);
            	@file_put_contents( $sLocalFile, $sContent );
            }
            // the file is not harmful, skip the alarm
            if( !preg_match( '/.*<\s*\?\s*(php)?.+\??\s*>?.*/im', $sContent ) )
                return self::ACTION_SKIP;
        }

        return self::ACTION_NONE;
    }
}

$this->addHook( new AllowedFileHook($this), IWPSHook::ALARM_HOOK );

?>