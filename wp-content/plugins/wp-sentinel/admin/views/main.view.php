<div class="wrap">
    <h2>WP-Sentinel Security System
        <div style="font-size: 12px; font-weight: normal; display: inline;">ver <?php echo WPSentinel::VERSION; ?></div>
        <br>
        <div style="float:left; font-size: 12px; margin-top: -12px;">
            If you like this plugin, <a href="http://twitter.com/evilsocket" target="_blank">say hello</a> to the author and <a href="<?php echo BLOG_URL; ?>/wp-admin/plugin-install.php?tab=plugin-information&amp;plugin=wp-sentinel&amp;TB_iframe=true&amp;width=640&amp;height=698" class="thickbox">vote for it</a> :)
        </div>
    </h2>
    <br>
    <div id="nav">
        <h1 class="themes-php" style="border-bottom: 1px solid #CCC; padding: 0px;">
        <?php foreach( $aAvailableTabs as $sTabName => $sTabLabel ): ?>
            <a class="nav-tab <?php echo $sCurrentTab == $sTabName ? 'nav-tab-active' : ''; ?>" href="<?php echo WPS_ADMIN_PAGE; ?>&tab=<?php echo $sTabName; ?>"><?php echo $sTabLabel; ?></a>
        <?php endforeach; ?>
        </h1>
    </div>
    <table class="form-table">
        <tr>
            <td>
                <?php foreach( $aErrors as $sError ): ?>
                    <div style='background-color: white; color: red; font-size: 13px; padding: 10px; border: 1px dotted gray;'><?php echo $sError; ?></div><br/>
                <?php endforeach; ?>
                <?php foreach( $aNotices as $sNotice ): ?>
                    <div style='background-color: white; color: green; font-size: 13px; padding: 10px; border: 1px dotted gray;'><?php echo $sNotice; ?></div><br/>
                <?php endforeach; ?>

                <?php require_once $sCurrentTabView; ?>
            </td>
        </tr>
    </table>
</div>