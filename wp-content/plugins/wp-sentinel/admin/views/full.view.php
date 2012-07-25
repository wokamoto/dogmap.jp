<div style="background-color: white; border: 1px dotted gray; padding: 10px;">
    <b>Download</b> : <a href='<?php echo WPS_ADMIN_URL; ?>logdownload.php?action=daily'>daily</a> - <a href='<?php echo WPS_ADMIN_URL; ?>logdownload.php?action=full'>full</a>
    <div style="float:right">
        <b>Wipe</b> : <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&wipe=daily'>daily</a> - <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&wipe=full'>full</a>
    </div>
</div>
<?php if( $nLogs ): ?>

<br>
<table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
    <tr><td>
        <b>Logged <?php echo $nLogs ;?> attack<?php echo $nLogs == 1 ? '' : 's'; ?>.</b>
        <br>
        <br>
        <table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
            <tr>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:75px;"><center>Time</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px;"><center>Address</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:30px;"><center>Scope</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:40px;"><center>Variable</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px;"><center>Alarm</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:25px;"><center>Details</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:25px;"><center>Delete</center></th>
            </tr>

            <?php foreach( $aLogs as $i => $log ): ?>
            <tr style='<?php echo $i % 2 != 0 ? "background-color: #EEE;" : ""; ?>'>
                <td style='padding:1px;' align='center'><?php echo strftime( "%T", $log->timestamp );?></td>
                <td style='padding:1px;' align='center'>
                    <a href='http://whois.domaintools.com/<?php echo $log->address; ?>' target='_blank'><?php echo $log->address; ?></a>
                    <div style="float:right; position: relative; right: 40px;">
                        <a href='<?php echo WPS_ADMIN_URL; ?>/ipdetails.php?ip=<?php echo $log->address; ?>' rel="facebox" class="tooltip" title="Address details panel.">
                            <img src='<?php echo WPS_ADMIN_IMAGES; ?>view.png' style='border:0px;'/>
                        </a>
                        &nbsp;
                        <?php if( !WPSentinel::isAddressBanned( $log->address ) ): ?>
                        <a href='<?php echo WPS_ADMIN_URL; ?>ipban.php?ip=<?php echo $log->address; ?>' rel="facebox" class="tooltip" title="Manually ban this address.">
                            <img src='<?php echo WPS_ADMIN_IMAGES; ?>ban.png' style='border:0px;'/>
                        </a>
                        <?php else: ?>
                        <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&unban=<?php echo $log->address; ?>' class="tooltip" title="Unban this address.">
                            <img src='<?php echo WPS_ADMIN_IMAGES; ?>unban.png' style='border:0px;'/>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td style='padding:1px;' align='center'><b><?php echo $log->scope; ?></b></td>
                <td style='padding:1px;' align='center'><b><?php echo $log->variable; ?></b></td>
                <td style='padding:1px;' align='center'><b><?php echo $log->message; ?></b></td>
                <td style='padding:1px;' align='center'>
                    <a href='<?php echo WPS_ADMIN_URL; ?>details.php?id=<?php echo $log->id; ?>' rel="facebox">
                        <img src='<?php echo WPS_ADMIN_IMAGES; ?>view.png' style='border:0px;'/>
                    </a>
                </td>
                <td style='padding:1px;' align='center'>
                    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&delete=<?php echo $log->id;?>'>
                        <img src='<?php echo WPS_ADMIN_IMAGES; ?>delete.png' style='border:0px;'/>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </td>
</tr>
<tr>
    <td colspan="7" align="center">
        <?php require_once 'pager.view.php'; ?>
    </td>
</tr>
</table>

<?php else: ?>

<br>
<table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
    <tr><td>No daily activities</td></tr>
</table>

<?php endif; ?>