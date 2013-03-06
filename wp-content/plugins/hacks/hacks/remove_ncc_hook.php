<?php
add_action('wp_enqueue_scripts', function(){
	remove_action('wp_print_footer_scripts', 'NginxChampuru_Caching::wp_print_footer_scripts_admin_ajax');
	remove_action('wp_print_footer_scripts', 'NginxChampuru_Caching::wp_print_footer_scripts_wp_clon');
}, 11);
