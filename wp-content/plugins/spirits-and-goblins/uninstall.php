<?php

if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();


if ( !class_exists('SpiritsAndGoblins_Admin') )
	require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins_Admin.php');
if ( !class_exists('SpiritsAndGoblins') )
	require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins.php');

delete_option(SpiritsAndGoblins_Admin::OPTION_KEY);

global $wpdb;

$wpdb->query($wpdb->prepare(
	"delete from {$wpdb->usermeta} where meta_key like %s or meta_key like %s",
	'%'.SpiritsAndGoblins::META_KEY_SEED,
	'%'.SpiritsAndGoblins::META_KEY_SEQ
	));
