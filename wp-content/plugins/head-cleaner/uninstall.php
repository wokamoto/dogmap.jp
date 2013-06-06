<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();


require_once(dirname(__FILE__).'/head-cleaner.php');

if (!isset($head_cleaner))
	$head_cleaner = new HeadCleaner(true);
$head_cleaner->uninstall();