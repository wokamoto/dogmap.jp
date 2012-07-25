<?php
/*
Plugin Name: Reject short comments
Plugin URI: http://www.yuriko.net/arc/2008/05/21/reject-short-comments080/
Description: Rejects short comments to keep away from spams.
Author: IKEDA Yuriko
Version: 0.8.1
Author URI: http://www.yuriko.net/cat/wordpress/
*/

/*  Copyright (c) 2008 yuriko

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
if (is_admin()) return false;

define ('TRACKBACK_EXCERPT_LENGTH', 128);
define ('COMMENT_LENGTH', 64);

function reject_short_comments($commentdata) {
	if (! preg_match('/[\x80-\xfc]/', $commentdata['comment_content'])) {
		switch ($commentdata['comment_type']) {
		case 'trackback':
			$excerpt = preg_replace('!^<strong>.*?</strong>\s*!', '', $commentdata['comment_content']);
			if (strlen($excerpt) < TRACKBACK_EXCERPT_LENGTH && ! preg_match('/[\x80-\xfc]/', $excerpt)) {
				trackback_response(1, 'We cannot accept your trackback.');
				exit;
			}
			break;
		default: // comment
			if (strlen($commentdata['comment_content']) < COMMENT_LENGTH) {
				wp_die(__('Error: please type a comment.'));
				exit;
			} else {
				$content = trim(preg_replace('#(<a href=\\\\[\'"]|\[url=|\[link=)?https?://[^>\]]*([\'"]>|\])?[^<\[]*(</a>|\[/url\]|\[/link\])#s', '', $commentdata['comment_content']));
				if (strlen($content) < COMMENT_LENGTH) {
					wp_die('Error: Bad links in content.');
					exit;
				}
			}
			break;
		}
	}
	return $commentdata;
}
add_filter('preprocess_comment', 'reject_short_comments', 1);

/*******************************************************************
 * Reject SPAM IP
 *******************************************************************/
/*
function reject_spam_IP($ip, $email, $date) {
 $spam_IP  = '127.0.0.2';
 $host     = "dnsbl.spam-champuru.livedoor.com";
 $pattern  = '/^(\d{1,3})(\.)(\d{1,3})(\.)(\d{1,3})(\.)(\d{1,3})$/';
 $check_IP = trim(preg_match($pattern, $ip) ? $ip : $_SERVER['REMOTE_ADDR']);
 $spam     = false;
 if (preg_match($pattern, $check_IP)) {
  $host = preg_replace($pattern, "$7$6$5$4$3$2$1", $check_IP) . '.' . $host;
  if (function_exists('dns_get_record')) {
   $check_recs = dns_get_record($host, DNS_A);
   if (isset($check_recs[0]['ip'])) $spam = ($check_recs[0]['ip'] === $spam_IP);
   unset($check_recs);
  } elseif (class_exists('Net_DNS_Resolver')) {
   $resolver = new Net_DNS_Resolver();
   $response = $resolver->query($host, 'A');
   if ($response) {
    foreach ($response->answer as $rr) {
     if ($rr->type === 'A') {
      $spam = ($rr->address === $spam_IP);
      break;
     }
    }
   }
   unset($response);
   unset($resolver);
  } elseif (function_exists('checkdnsrr')) {
   $spam = (checkdnsrr($host, "A") === true);
  }
 }
 if ($spam) {
  wp_die('Error: Your IP Address is registered in the DNSBL (http://spam-champuru.livedoor.com/dnsbl/).');
 }
}
add_action('check_comment_flood', 'reject_spam_IP', 10, 3);
*/

/*******************************************************************
 * Nonce Please
 *******************************************************************/
if (!defined('NONCE_FIELD')) define('NONCE_FIELD', '_wpnonce');
if (!defined('COMMENT_NONCE_ACTION')) define('COMMENT_NONCE_ACTION', 'post-comments_');
if (!defined('TRACKBACK_NONCE_ACTION')) define('TRACKBACK_NONCE_ACTION', 'send-trackbacks_');

function nonce_tick() {
 if (function_exists('wp_nonce_tick')) {
  return wp_nonce_tick();
 } else {
  return ceil(time() / 43200);
 }
}

function create_anon_nonce($action) {
 $i = nonce_tick();
 return substr(wp_hash($i . $action), -12, 10) ;
}

function verify_anon_nonce($nonce, $action = -1) {
 $i = nonce_tick();
 // Nonce generated 0-12 hours ago
 if ( substr(wp_hash($i . $action), -12, 10) == $nonce ) return 1;
 // Nonce generated 12-24 hours ago
 if ( substr(wp_hash(($i - 1) . $action), -12, 10) == $nonce ) return 2;
 // Invalid nonce

 return false;
}

function add_co_nonce($post_id) {
 echo '<input type="hidden" id="' . NONCE_FIELD . '" name="' . NONCE_FIELD . '" value="' . create_anon_nonce(COMMENT_NONCE_ACTION . $post_id) . '" />';
}

function add_tr_nonce($tb_url) {
 global $id;
 return $tb_url . ((strpos($tb_url, '?') !== FALSE) ? '&' : '?') . NONCE_FIELD . '=' . create_anon_nonce(TRACKBACK_NONCE_ACTION . $id);
}

function confirm_nonce($commentdata) {
 switch ($commentdata['comment_type']) {
 case 'trackback':
//  if (! isset($_GET[NONCE_FIELD]) || ! verify_anon_nonce($_GET[NONCE_FIELD], TRACKBACK_NONCE_ACTION . $commentdata['comment_post_ID'])) {
//   trackback_response(1, 'We cannot accept your trackback.');
//   exit;
//  }
  break;
 case 'pingback':
  break;
 default: // comment
  if (! isset($_POST[NONCE_FIELD]) || ! verify_anon_nonce($_POST[NONCE_FIELD], COMMENT_NONCE_ACTION . $commentdata['comment_post_ID'])) {
   wp_die(__('Error: Please back to comment form, and retry submit.', 'nonce_please'));
   exit;
  }
  break;
 }
 return $commentdata;
}
add_action('comment_form', 'add_co_nonce');
//add_action('trackback_url', 'add_tr_nonce');
add_action('preprocess_comment', 'confirm_nonce', 1);


if (function_exists('akismet_init')) {
 remove_action('preprocess_comment', 'akismet_auto_check_comment', 1);
 add_action('preprocess_comment', 'akismet_auto_check_comment', 2);
}
