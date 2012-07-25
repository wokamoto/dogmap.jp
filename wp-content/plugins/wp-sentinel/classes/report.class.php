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

class WPSReport
{
    const FORMAT_LOG    = 0;
    const FORMAT_EMAIL  = 1;
    const FORMAT_USER   = 2;
    const FORMAT_BANNED = 3;

    static $aLayoutTokens = array
    (
        '{ALARM_ROWS}',
        '{DATE}',
        '{ADDRESS',
        '{USERNAME}',
        '{REFERER}',
        '{USERAGENT}'
    );

    private $oDb      = NULL;
    private $aLayouts = NULL;
    private $aAlarms  = NULL;
    private $aUser    = NULL;

    public function __construct( $oWpUser, $oWpDb )
    {
        $this->aLayouts = array
        (
            'banned'    => @file_get_contents( WPS_PATH.'layouts/banned.layout' ),
            'email_row' => @file_get_contents( WPS_PATH.'layouts/email.row.layout' ),
            'email'     => @file_get_contents( WPS_PATH.'layouts/email.layout' ),
            'alarm_row' => @file_get_contents( WPS_PATH.'layouts/alarm.row.layout' ),
            'alarm'     => @file_get_contents( WPS_PATH.'layouts/alarm.layout' )
        );

        $this->aAlarms = array();

        $this->aUser = array
        (
            'login'   => isset( $oWpUser->user_level ) ? $oWpUser->user_login : '',
            'address' => WPSentinel::getAddress(),
            'referer' => isset($_SERVER['HTTP_REFERER'])    ? htmlentities($_SERVER['HTTP_REFERER'])    : '',
            'agent'   => isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : ''
        );

        $this->oDb = $oWpDb;
    }

    public function addAlarm( $sScope, $sVariableName, $sVariableData, $sAlarmDescription, $sRule )
    {
        $this->aAlarms[] = array
        (
            'scope'    => $sScope,
            'variable' => htmlentities($sVariableName),
            'content'  => htmlentities($sVariableData),
            'alarm'    => $sAlarmDescription,
            'rule'     => htmlentities($sRule)
        );
    }

    public function get( $iFormat )
    {
        $sReport = '';

        switch( $iFormat )
        {
            case self::FORMAT_LOG :

                foreach( $this->aAlarms as $aAlarm )
                {
                    $sReport .= sprintf
                    (
                        "%d : %s %s : %s : %s : %s : %s : %s : %s\r\n",
                        time(),
                        $aAlarm['address'],
                        $this->aUser['login'],
                        $this->aUser['referer'],
                        $this->aUser['agent'],
                        $aAlarm['scope'],
                        $aAlarm['variable'],
                        @base64_encode( $aAlarm['content'] ),
                        $aAlarm['alarm']
                    );
                }

            break;

            case self::FORMAT_EMAIL :

                $sRows      = '';
                $sRowLayout = $this->aLayouts['email_row'];
                $sReport    = $this->aLayouts['email'];

                foreach( $this->aAlarms as $aAlarm )
                {
                    $sRow = $sRowLayout;
                    foreach( array_keys($aAlarm) as $sKey )
                    {
                        $sRow = str_replace( '{'.strtoupper($sKey).'}', $aAlarm[$sKey], $sRow );
                    }

                    $sRows .= $sRow;
                }

                $aLayoutReplace = array
                (
                    $sRows,
                    strftime('%c'),
                    $this->aUser['address'],
                    $this->aUser['login'],
                    $this->aUser['referer'],
                    $this->aUser['agent']
                );

                $sReport = str_replace( self::$aLayoutTokens, $aLayoutReplace, $sReport );

            break;

            case self::FORMAT_USER :

                $sRows      = '';
                $sRowLayout = $this->aLayouts['alarm_row'];

                foreach( $this->aAlarms as $aAlarm )
                {
                    $sRow = $sRowLayout;
                    foreach( array_keys($aAlarm) as $sKey )
                    {
                        $sRow = str_replace( '{'.strtoupper($sKey).'}', $aAlarm[$sKey], $sRow );
                    }

                    $sRows .= $sRow;
                }

                $sReport = str_replace( '{ALARM_ROWS}', $sRows, $this->aLayouts['alarm'] );

            break;

            case self::FORMAT_BANNED :

                $sReport = $this->aLayouts['banned'];

            break;

            default :

                $sReport = $this->get( self::FORMAT_LOG );
        }

        return $sReport;
    }

    public function log( )
    {
        foreach( $this->aAlarms as $aAlarm )
        {
           $sQuery = $this->oDb->prepare
           (
             'INSERT INTO '.$this->oDb->prefix.'wps_logs (timestamp,address,agent,referer,username,scope,variable,content,message,rule) VALUES (
                %d, %s, %s, %s, %s, %s, %s, %s, %s, %s
             )',
             time(),
             $this->aUser['address'],
             $this->aUser['agent'],
             $this->aUser['referer'],
             $this->aUser['login'],
             $aAlarm['scope'],
             $aAlarm['variable'],
             $aAlarm['content'],
             $aAlarm['alarm'],
             $aAlarm['rule']
           );

           $this->oDb->query($sQuery);
        }
    }

    public function hasAlarms()
    {
        return ( count( $this->aAlarms ) > 0 );
    }

    public function alarms()
    {
        return $this->aAlarms;
    }

    public function entries()
    {
        return count($this->aAlarms);
    }
}
