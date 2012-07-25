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

class AllowedTagsHook implements IWPSHook
{
    public function __construct( $oSentinel )
    {
        $this->aAllowedTags = $oSentinel->getConfiguration()->allowed_html;
    }

    public function run( $mArg )
    {
        list( $sVariableName, $sVariableValue, $oRule ) = $mArg;

        if( $oRule->label == 'XSS' )
        {
            if( preg_match_all( "/<\s*\/?\s*([^ >]+)[^>]*>/m", $sVariableValue, $aTags ) )
            {
                $aTags = $aTags[1];
                /*
                 * These are all allowed tags, skip this alarm.
                 */
                if( array_intersect( $aTags, $this->aAllowedTags ) == $aTags )
                    return self::ACTION_SKIP;
            }
        }

        return self::ACTION_NONE;
    }
}

$this->addHook( new AllowedTagsHook($this), IWPSHook::ALARM_HOOK );

?>