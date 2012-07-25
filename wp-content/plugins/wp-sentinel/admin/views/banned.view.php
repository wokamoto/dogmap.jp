<div style="background-color: white; border: 1px dotted gray; padding: 10px;">
    <a href="<?php echo WPS_ADMIN_PAGE; ?>&tab=ban&action=unban">Unban everyone</a>
    <div style="float:right">
        <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=ban&action=ban'>Ban every attacker for 24 hours</a>
    </div>
</div>
<?php if( $nBans ): ?>

<br>
<table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
    <tr><td>
        <b>Banned <?= $num_rows ;?> address<?= ($num_rows == 1 ? '' : 'es') ?>.</b>
        <br>
        <br>
        <table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
            <tr>
                <th style="font-weight: bold; background-color: #CCC; padding:3px;"><center>Started On</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:100px;"><center>Duration</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px;"><center>Time Left</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px;"><center>Address</center></th>
                <th style="font-weight: bold; background-color: #CCC; padding:3px; width:25px;"><center>Remove</center></th>
            </tr>
            <?php foreach( $aBans as $i => $ban ): ?>
            <tr style='<?php echo $i % 2 != 0 ? "background-color: #EEE;" : ""; ?>'>
                <td style='padding:1px;' align='center'><?php echo strftime( "%c", $ban->timestamp); ?></td>
                <td style='padding:1px;' align='center'><?php echo wps_format_time( $ban->duration ); ?></td>
                <td style='padding:1px;' align='center'><?php echo wps_format_time( ($ban->timestamp + $ban->duration) - time() ); ?></td>
                <td style='padding:1px;' align='center'>
                    <a href='http://whois.domaintools.com/<?php echo $ban->address; ?>' target='_blank'><?php echo $ban->address; ?></a>
                </td>
                <td style='padding:1px;' align='center'>
                    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=ban&delete=<?php echo $ban->id; ?>'>
                        <img src='<?php echo WPS_ADMIN_IMAGES ?>delete.png' style='border:0px;'/>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

        </table>
    </td>
    </tr>
    <tr>
        <td colspan="5" align="center">
            <?php require_once 'pager.view.php'; ?>
        </td>
    </tr>
</table>

<?php else: ?>
<br>
<table width="100%" cellpadding="0" cellspacing="1" style="background-color: white; border: 1px dotted gray; padding: 0;">
    <tr><td>No current bans.</td></tr>
</table>

<?php endif; ?>