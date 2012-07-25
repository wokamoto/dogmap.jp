<form method="POST">
    <table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
        <tr><td><b>Single alarm layout for email notification</b> <em>(content of {ALARM_ROWS} in email layout)</em></td></tr>
        <tr>
            <td>
                Allowed tokens:
                <table cellpadding="0" cellspacing="5">
                    <tr>
                        <td style="padding: 0px;"><b>{SCOPE}</b></td>
                        <td style="padding: 0px;">GET or POST of the variable.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{VARIABLE}</b></td>
                        <td style="padding: 0px;">The variable name.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{CONTENT}</b></td>
                        <td style="padding: 0px;">The variable content.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{ALARM}</b></td>
                        <td style="padding: 0px;">Name of the alarm that was triggered by the request.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{RULE}</b></td>
                        <td style="padding: 0px;">Regex that matched the alarm.</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <textarea name="email_row_layout" cols=100 rows=3><?php echo htmlentities($email_row_layout); ?></textarea>
            </td>
        </tr>

        <tr><td><b>EMail layout</b></td></tr>
        <tr>
            <td>
                Allowed tokens:
                <table cellpadding="0" cellspacing="5">
                    <tr>
                        <td style="padding: 0px;"><b>{DATE}</b></td>
                        <td style="padding: 0px;">Date of the attack.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{ADDRESS}</b></td>
                        <td style="padding: 0px;">IP address of the attacker.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{USERNAME}</b></td>
                        <td style="padding: 0px;">Wordpress username of the attacker if he's logged.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{REFERER}</b></td>
                        <td style="padding: 0px;">HTTP referer.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{USERAGENT}</b></td>
                        <td style="padding: 0px;">HTTP user-agent.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{ALARM_ROWS}</b></td>
                        <td style="padding: 0px;">Content of the single alarm layouts filled with alarm data.</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td>
                <textarea name="email_layout" cols=100 rows=10><?php echo htmlentities($email_layout); ?></textarea>
            </td>
        </tr>

        <tr><td><b>Single alarm layout for user notification</b> <em>(content of {ALARM_ROWS} in alarm layout)</em></td></tr>
        <tr>
            <td>
                Allowed tokens:
                <table cellpadding="0" cellspacing="5">
                    <tr>
                        <td style="padding: 0px;"><b>{SCOPE}</b></td>
                        <td style="padding: 0px;">GET or POST of the variable.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{VARIABLE}</b></td>
                        <td style="padding: 0px;">The variable name.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{CONTENT}</b></td>
                        <td style="padding: 0px;">The variable content.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{ALARM}</b></td>
                        <td style="padding: 0px;">Name of the alarm that was triggered by the request.</td>
                    </tr>
                    <tr>
                        <td style="padding: 0px;"><b>{RULE}</b></td>
                        <td style="padding: 0px;">Regex that matched the alarm.</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <textarea name="alarm_row_layout" cols=100 rows=8><?php echo htmlentities($alarm_row_layout); ?></textarea>
            </td>
        </tr>

        <tr><td><b>Alarm layout</b></td></tr>
        <tr>
            <td>
                Allowed tokens:
                <table cellpadding="0" cellspacing="5">
                    <tr>
                        <td style="padding: 0px;"><b>{ALARM_ROWS}</b></td>
                        <td style="padding: 0px;">Content of the single alarm layouts filled with alarm data.</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <textarea name="alarm_layout" cols=100 rows=20><?php echo htmlentities($alarm_layout); ?></textarea>
            </td>
        </tr>

        <tr><td><b>Banned layout</b></td></tr>
        <tr>
            <td>
                <textarea name="banned_layout" cols=100 rows=20><?php echo htmlentities($banned_layout); ?></textarea>
            </td>
        </tr>

        <tr>
            <td><input class="button-primary" type="submit" value="Update" name="update"/></td>
        </tr>
    </table>
</form>