<?php if( $pageno == 1 ): ?>
    <b>&lt;</b> <b>&lt;&lt;</b>
<?php else: ?>
    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&pageno=1' style='text-decoration:none;'><b>&lt;&lt;</b></a>
    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&pageno=<?php echo $pageno - 1; ?>' style='text-decoration:none;'><b>&lt;</b></a>
<?php endif; ?>

( Page <b><?php echo $pageno; ?></b> of <b><?php echo $lastpage; ?></b> )

<?php if( $pageno == $lastpage ): ?>
    <b>&gt;</b> <b>&gt;&gt;</b>
<?php else: ?>
    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&pageno=<?php echo $pageno + 1; ?>' style='text-decoration:none;'><b>&gt;</b></a>
    <a href='<?php echo WPS_ADMIN_PAGE; ?>&tab=full&pageno=<?php echo $lastpage; ?>' style='text-decoration:none;'><b>&gt;&gt;</b></a>
<?php endif; ?>