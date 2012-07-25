<form method="POST">
    <table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">

        <tr>
            <td><b>Sentinel Status</b></td>
            <td>
                <input type='radio' name='enabled' <?php echo ($cfg->enabled == 1 ? 'checked' : ''); ?> value="1"> <font color='green'>Enabled</font>&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='radio' name='enabled' <?php echo ($cfg->enabled != 1 ? 'checked' : ''); ?> value="0"> <font color='red'>Disabled</font>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Enable or disable the plugin, use carefully!</em></td>
        </tr>

        <tr>
            <td><b>EMail Notifications</b></td>
            <td>
                <input type='radio' name='notification' <?php echo ($cfg->notification == 1 ? 'checked' : ''); ?> value="1"> Enabled&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='radio' name='notification' <?php echo ($cfg->notification != 1 ? 'checked' : ''); ?> value="0"> Disabled
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Enable or disable email notifications to the administrator.</em></td>
        </tr>

        <tr>
            <td><b>DB Logging</b></td>
            <td>
                <input type='radio' name='logging' <?php echo ($cfg->logging == 1 ? 'checked' : ''); ?> value="1"> <font color='green'>Enabled</font>&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='radio' name='logging' <?php echo ($cfg->logging != 1 ? 'checked' : ''); ?> value="0"> <font color='red'>Disabled</font>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Enable or disable db logging during an attack.</em></td>
        </tr>

        <tr>
            <td><b>Flood Delay</b></td>
            <td>
                <input type="text" name="flood_time" value="<?php echo $cfg->flood_time; ?>"/>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Delay, in seconds, below which attacks will be considered as flooding and will not be logged, this delay is used as anti bruteforcing delay too.</em></td>
        </tr>

        <tr>
            <td><b>Autoban</b></td>
            <td>
                <input type='radio' name='autoban' <?php echo ($cfg->autoban == 1 ? 'checked' : ''); ?> value="1"> <font color='green'>Enabled</font>&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='radio' name='autoban' <?php echo ($cfg->autoban != 1 ? 'checked' : ''); ?> value="0"> <font color='red'>Disabled</font>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Enable or disable ip auto banning.</em></td>
        </tr>

        <tr>
            <td><b>Autoban Threshold</b></td>
            <td>
                <input type="text" name="ban_attacks" value="<?php echo $cfg->ban_attacks; ?>"/>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Number of attacks to get auto banned.</em></td>
        </tr>

        <tr>
            <td><b>Autoban Time</b></td>
            <td>
                <input type="text" name="ban_time" value="<?php echo $cfg->ban_time; ?>"/>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Hours of auto banning.</em></td>
        </tr>

        <tr>
            <td><b>Whitelisted Variables</b></td>
            <td>
                <input type="text" name="whitelist" value="<?php echo  implode( ', ', $cfg->whitelist ); ?>" size="200"/>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>Those comma separated variables are not going to be checked by WP-Sentinel.</em></td>
        </tr>

        <tr>
            <td><b>Allowed HTML Tags</b></td>
            <td>
                <input type="text" name="allowed_html" value="<?php echo  implode( ', ', $cfg->allowed_html ); ?>" size="200"/>
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px; border-bottom: 1px dotted gray;"><em>A list of comma separated HTML tags that users are allowed to use inside comments ecc.</em></td>
        </tr>

        <tr>
            <td><b>Max Login Attempts</b></td>
            <td>
                <input type="text" name="max_logins" value="<?php echo $cfg->max_logins; ?>" />
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top" style="padding-top: 0px;"><em>Number of maximum login attempts after the wich the user will be unable to login for 'flood delay' seconds.</em></td>
        </tr>

        <tr>
            <td><input class="button-primary" type="submit" value="Update" name="update"/></td>
            <td>
            </td>
        </tr>

    </table>
</form>