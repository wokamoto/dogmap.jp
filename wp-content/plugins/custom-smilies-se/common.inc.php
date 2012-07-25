<?php
$clcs_options = get_option('clcs_options');
if (!is_array($clcs_options)) {
	$clcs_options = array();
}

// enable smilies
if (!get_option('use_smilies')) {
    update_option('use_smilies', 1);
}

function clcs_get_smilies_path() {
	global $clcs_options;
	if (array_key_exists('smilies_path', $clcs_options)) {
		return get_option('siteurl') . $clcs_options['smilies_path'];
	} else {
		return get_option('siteurl') . '/wp-includes/images/smilies';
	}
}

function clcs_get_smilies_dir() {
	global $clcs_options;
	if (array_key_exists('smilies_path', $clcs_options)) {
		return ABSPATH . $clcs_options['smilies_path'];
	} else {
		return ABSPATH . 'wp-includes/images/smilies';
	}
}

function clcs_inner_custom_box() {
	
	?>
    <script type="text/javascript">
    function grin(tag) {
    	var myField;
    	tag = ' ' + tag + ' ';
    	if (document.getElementById('content') && document.getElementById('content').style.display != 'none' && document.getElementById('content').type == 'textarea') {
    		myField = document.getElementById('content');
            if (document.selection) {
        		myField.focus();
        		sel = document.selection.createRange();
        		sel.text = tag;
        		myField.focus();
        	}
        	else if (myField.selectionStart || myField.selectionStart == '0') {
        		var startPos = myField.selectionStart;
        		var endPos = myField.selectionEnd;
        		var cursorPos = endPos;
        		myField.value = myField.value.substring(0, startPos)
        					  + tag
        					  + myField.value.substring(endPos, myField.value.length);
        		cursorPos += tag.length;
        		myField.focus();
        		myField.selectionStart = cursorPos;
        		myField.selectionEnd = cursorPos;
        	}
        	else {
        		myField.value += tag;
        		myField.focus();
        	}
    	} else {
            tinyMCE.execCommand('mceInsertContent', false, tag);
        }
    }
    </script><?php
            $smilies = cs_load_existing_smilies();
            $url = clcs_get_smilies_path();


        	foreach ($smilies as $k => $v) {
            	echo "<img src='{$url}/{$k}' alt='{$v}' onclick='grin(\"{$v}\")' class='wp-smiley-select' /> ";
        	}

}

// smilies options page
function clcs_options_admin_page() {
	global $wpsmiliestrans, $clcs_options;
	
	if ($_POST['update-options']) {
		$updated = false;
		$is_illegal_dir = false;
		if (get_option('cs_list') != $_POST['list']) {
			update_option('cs_list', $_POST['list']);
			$updated = true;
		}
		if (array_key_exists('use-action-comment-form', $_POST)) {
			$clcs_options = get_option('clcs_options');
			if ($clcs_options['use_action_comment_form'] == 0) {
				$updated = true;
			}
			$clcs_options['use_action_comment_form'] = 1;
			update_option('clcs_options', $clcs_options);
		} else {
			$clcs_options = get_option('clcs_options');
			if ($clcs_options['use_action_comment_form'] == 1) {
				$updated = true;
			}
			$clcs_options['use_action_comment_form'] = 0;
			update_option('clcs_options', $clcs_options);
		}
		if (array_key_exists('comment_textarea', $_POST)) {
			$clcs_options = get_option('clcs_options');
			if ($_POST['comment_textarea'] != $clcs_options['comment_textarea']) {
				$clcs_options['comment_textarea'] = $_POST['comment_textarea'];
				update_option('clcs_options', $clcs_options);
				$updated = true;
			}
		}
		if (array_key_exists('popup_win_width', $_POST)) {
			$clcs_options = get_option('clcs_options');
			if (!array_key_exists('popup_win_width', $clcs_options)) {
				$clcs_options['popup_win_width'] = 0;
			}
			if ($_POST['popup_win_width'] != $clcs_options['popup_win_width']) {
				$clcs_options['popup_win_width'] = $_POST['popup_win_width'];
				update_option('clcs_options', $clcs_options);
				$updated = true;
			}
		}
		if (array_key_exists('popup_win_height', $_POST)) {
			$clcs_options = get_option('clcs_options');
			if (!array_key_exists('popup_win_height', $clcs_options)) {
				$clcs_options['popup_win_height'] = 0;
			}
			if ($_POST['popup_win_height'] != $clcs_options['popup_win_height']) {
				$clcs_options['popup_win_height'] = $_POST['popup_win_height'];
				update_option('clcs_options', $clcs_options);
				$updated = true;
			}
		}
		if (array_key_exists('smilies_path', $_POST)) {
			$clcs_options = get_option('clcs_options');
			if (is_dir(ABSPATH . $_POST['smilies_path'])) {
				if (($_POST['smilies_path'] != $clcs_options['smilies_path'])) {
					$clcs_options['smilies_path'] = $_POST['smilies_path'];
					update_option('clcs_options', $clcs_options);
					$updated = true;
				}
			} else {
				$is_illegal_dir = true;
			}
		}
		if ($updated) {
			$clcs_message = __('Preferences updated.', 'custom_smilies');
		} else {
			$clcs_message = __('No changes made.', 'custom_smilies');
		}
		if ($is_illegal_dir) {
			$clcs_message .= __(' The path of smilies that you want to set is illegal.', 'custom_smilies');
		}
		echo '<div id="message" class="updated fade"><p><b>' . $clcs_message . '</b></p></div>';
	}
	
	$clcs_options = get_option('clcs_options');
	if (!is_array($clcs_options)) {
		$clcs_options = array();
	}
	
	// show all or show undefined?
	$su = ($_GET['su'] === '1');
	
	if ($_POST['update-smilies']) {
		// save smilies to file and refresh $wpsmiliestrans
		$wpsmiliestrans = cs_save_smilies($_POST);
		echo '<div id="message" class="updated fade"><p><strong>' . __('Your smilies have been updated.', 'custom_smilies') . '</strong></p></div>';
	}
	
	// $smilies: 1|gif, 2|gif, etc
	// $old_smilies: :D, :(, etc
	$smilies = cs_get_all_smilies();
	$old_smilies = cs_load_existing_smilies();
?>
<div class="wrap">
	<h2><?php _e('Manage Smilies', 'custom_smilies'); ?></h2>
	<p>
<?php
	echo ($su) ? '<a href="' . wp_nonce_url(CLCSOPTURL . '&su=0') . '">' . __('Display all smilies', 'custom_smilies') . '</a>' : '<a href="' . wp_nonce_url(CLCSOPTURL . '&su=1') . '">' . __('Display undefined smilies only', 'custom_smilies') . '</a>';
?>
	</p>
	<p align="right"><?php _e('Please note that your smilies cannot contain any of these characters: \' " \\', 'custom_smilies'); ?></p>
	<form id="manage-smilies-form" method="POST" action="" name="manage-smilies-form">
		<input type="hidden" name="page" value="custom-smilies.php" />
		<table class="widefat" style="text-align:center">
			<thead>
				<tr>
					<th scope="col">
						<div style="text-align: center;"><?php _e('Smilie', 'custom_smilies'); ?></div>
					</th>
					<th scope="col">
						<div style="text-align: center;"><?php _e('What to type', 'custom_smilies'); ?></div>
					</th>
					<th scope="col">
						<div style="text-align: center;"><?php _e('Smilie', 'custom_smilies'); ?></div>
					</th>
					<th scope="col">
						<div style="text-align: center;"><?php _e('What to type', 'custom_smilies'); ?></div>
					</th>
					<th scope="col">
						<div style="text-align: center;"><?php _e('Smilie', 'custom_smilies'); ?></div>
					</th>
					<th scope="col">
						<div style="text-align: center;"><?php _e('What to type', 'custom_smilies'); ?></div>
					</th>
                </tr>
    		</thead>
    		<tbody>
<?php
    if (is_array($smilies)) {
        foreach ($smilies as $smilie) {
            // 1|gif => 1.gif
            $smilie_name = str_replace('.', '|', $smilie);

            if ($su && $old_smilies[$smilie] != '') { // show undefined only
?>
                <input type="hidden" name="<?php echo $smilie_name ?>" value="<?php echo $old_smilies[$smilie] ?>" style="text-align:center" />
<?php
                continue;
            }

            // highlight even rows
            $class = ($count % 6 == 0) ? 'alternate' : '';

            // row starts
            if ($count % 3 == 0) {
?>
                <tr class="<?php echo $class ?>">
<?php
            }
?>
                    <td><img src="<?php echo clcs_get_smilies_path(); ?>/<?php echo $smilie ?>" /></td>
                    <td><input type="text" name="<?php echo $smilie_name ?>" value="<?php echo $old_smilies[$smilie] ?>" style="text-align:center" /></td>
<?php
            // row ends
            if ($count % 3 == 2) {
?>
                </tr>
<?php
            }
            $count++;
        }
    }
?>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" value="<?php _e('Update', 'custom_smilies'); ?>" name="update-smilies"/>
		</p>
	</form>
	
	<h2><?php _e('Smilies Options', 'custom_smilies'); ?></h2>
	<form id="smilies-options-form" method="POST" action="" name="smilies-options-form">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Display these smilies above the comment form by default:', 'custom_smilies'); ?></th>
				<td>
					<input type="text" value="<?php echo get_option('cs_list') ?>" name="list" style="width:95%"><br />
					<?php _e('Put your smilies here, separated by comma. Example: <b>:D, :), :wink:, :(</b>', 'custom_smilies'); ?><br />
					<?php _e('Leave this field blank if you want to display all smilies.', 'custom_smilies'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('The name of the input box for comment:', 'custom_smilies'); ?></th>
				<td>
					<input type="text" value="<?php if (array_key_exists('comment_textarea', $clcs_options)) echo $clcs_options['comment_textarea']; ?>" name="comment_textarea" style="width:95%"><br />
					<?php _e('If you find Custom Smilies can&#39;t be used in your theme, there is a different id for comment input in your theme. So you could enter the id here.', 'custom_smilies'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('The path of the smilies:', 'custom_smilies'); ?></th>
				<td>
					<input type="text" value="<?php if (array_key_exists('smilies_path', $clcs_options)) echo $clcs_options['smilies_path']; ?>" name="smilies_path" style="width:95%"><br />
					<?php _e('This is relative to the WordPress directory. Default is "/wp-includes/images/smilies".', 'custom_smilies'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Popup window width:', 'custom_smilies'); ?></th>
				<td>
					<input type="text" value="<?php if (array_key_exists('popup_win_width', $clcs_options)) echo $clcs_options['popup_win_width']; else echo '0'; ?>" name="popup_win_width" style="width:30%">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Popup window height:', 'custom_smilies'); ?></th>
				<td>
					<input type="text" value="<?php if (array_key_exists('popup_win_height', $clcs_options)) echo $clcs_options['popup_win_height']; else echo '0'; ?>" name="popup_win_height" style="width:30%">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Other options:', 'custom_smilies'); ?></th>
				<td>
					<fieldset>
						<label for="use-action-comment-form">
							<input id="use-action-comment-form" type="checkbox" name="use-action-comment-form" value="1"<?php if (array_key_exists('use_action_comment_form', $clcs_options) && $clcs_options['use_action_comment_form'] == 1) echo ' checked="checked"'; ?> />
							<?php _e('Use the action named comment_form in comments.php if your theme support it. So you don&#39;t need to add cs_print_smilies() in comments.php manually.', 'custom_smilies'); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" value="<?php _e('Update Options', 'custom_smilies'); ?>" name="update-options"/>
		</p>
	</form>
</div>
<?php
}

// scan directory & get all files
function cs_get_all_smilies() {
    if ($handle = opendir(clcs_get_smilies_dir())) {
        while (false !== ($file = readdir($handle))) {
            // no . nor ..
            if ($file != '.' && $file != '..') {
                $smilies[] = $file;
            }
        }
        closedir($handle);
    }
    return $smilies;
}

// load $wpsmiliestrans
function cs_load_existing_smilies() {
    global $wpsmiliestrans;
    return array_flip($wpsmiliestrans);
}

// install & upgrade older version if needed
function cs_activate() {
    global $wpdb, $table_prefix;

    // get out of here if no smileys table exists
    $table_name = $table_prefix.'smileys';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return;
    }

    // get existing smilies from database
    $result = $wpdb->get_results("SELECT * FROM `$table_name` ORDER BY `File`");
    $ext = get_option('csm_ext');
    foreach ($result as $object) {
        $tmp[$object->File.'.'.$ext] = $object->Emot;
    }

    // write to file
    cs_save_smilies($tmp);

    // uninstall
    $wpdb->query("DROP TABLE `{$table_prefix}smileys`");
    delete_option('csm_path');
	delete_option('csm_dbx');
	delete_option('csm_ext');
	delete_option('csm_spl');
}

// save smilies to file
function cs_save_smilies($array) {
    if (!is_array($array)) {
        return;
    }

    foreach ($array as $k => $v) {
        // sanitize smilies: remove \ ' " and trim whitespaces
        $array[$k] = trim(str_replace(array('\'','\\', '"'), '', $v));
    }

    $array = array_flip($array);
    $array4db = array();

    foreach ($array as $k => $v) {
        // sanitize smilies file name
        $array[$k] = $v = str_replace('|', '.', $v);
        if (!in_array($v, array('update-smilies', 'page')) && !in_array($k, array('', 'QAD'))) {
            $array4db[$k] = $v;
        }
    }

	update_option('clcs_smilies', $array4db);

    return $array;
}

// ensure compatibility with older version
function csm_comment_form() {
    cs_print_smilies();
}

// return all smilies
function cs_all_smilies() {
	global $wpsmiliestrans;
	$url = clcs_get_smilies_path();
	foreach ($wpsmiliestrans as $k => $v) {
		$smilies[$k] = "$url/$v";
	}
	return $smilies;
}

// print smilies list @ comment form
function clcs_print_smilies($comment_textarea = 'comment') {
?>

	<!-- Custom Smilies - Version <?php echo CLCSVER; ?> -->
	<style type="text/css">
	img.wp-smiley-select {cursor: pointer;}
	</style>
    <script type="text/javascript">
    function grin(tag) {
    	if (typeof tinyMCE != 'undefined') {
    		grin_tinymcecomments(tag);
    	} else {
    		grin_plain(tag);
    	}
    }
    function grin_tinymcecomments(tag) {
    	tinyMCE.execCommand('mceInsertContent', false, ' ' + tag + ' ');
    }
    
    function grin_plain(tag) {
    	var myField;
    	var myCommentTextarea = "<?php echo $comment_textarea; ?>";
    	tag = ' ' + tag + ' ';
        if (document.getElementById(myCommentTextarea) && document.getElementById(myCommentTextarea).type == 'textarea') {
    		myField = document.getElementById(myCommentTextarea);
    	} else {
    		return false;
    	}
    	if (document.selection) {
    		myField.focus();
    		sel = document.selection.createRange();
    		sel.text = tag;
    		myField.focus();
    	}
    	else if (myField.selectionStart || myField.selectionStart == '0') {
    		var startPos = myField.selectionStart;
    		var endPos = myField.selectionEnd;
    		var cursorPos = endPos;
    		myField.value = myField.value.substring(0, startPos)
    					  + tag
    					  + myField.value.substring(endPos, myField.value.length);
    		cursorPos += tag.length;
    		myField.focus();
    		myField.selectionStart = cursorPos;
    		myField.selectionEnd = cursorPos;
    	}
    	else {
    		myField.value += tag;
    		myField.focus();
    	}
    }
    
    function moreSmilies() {
    	document.getElementById('wp-smiley-more').style.display = 'inline';
    	document.getElementById('wp-smiley-toggle').innerHTML = '<a href="javascript:lessSmilies()">&laquo;&nbsp;less</a></span>';
    }
    
    function lessSmilies() {
    	document.getElementById('wp-smiley-more').style.display = 'none';
    	document.getElementById('wp-smiley-toggle').innerHTML = '<a href="javascript:moreSmilies()">more&nbsp;&raquo;</a>';
    }
    </script>
<?php
    $smilies = cs_load_existing_smilies();
    $url = clcs_get_smilies_path();
    $list = get_option('cs_list');            

    if ($list == '') {
	    foreach ($smilies as $k => $v) {
	        echo "<img src='{$url}/{$k}' alt='{$v}' onclick='grin(\"{$v}\")' class='wp-smiley-select' /> ";
	    }
    } else {
    	$display = explode(',', $list);
    	$smilies = array_flip($smilies);
    	foreach ($display as $v) {
    		$v = trim($v);
    		echo "<img src='{$url}/{$smilies[$v]}' alt='{$v}' onclick='grin(\"{$v}\")' class='wp-smiley-select' /> ";
    		unset($smilies[$v]);    		
    	}
    	echo '<span id="wp-smiley-more" style="display:none">';
    	foreach ($smilies as $k => $v) {
    		echo "<img src='{$url}/{$v}' alt='{$k}' onclick='grin(\"{$k}\")' class='wp-smiley-select' /> ";
    	}
    	echo '</span> <span id="wp-smiley-toggle"><a href="javascript:moreSmilies()">more&nbsp;&raquo;</a></span>';
    }
}


function cs_print_smilies() {
	global $clcs_options;
	clcs_print_smilies($clcs_options['comment_textarea']);
}

if (array_key_exists('use_action_comment_form', $clcs_options) && $clcs_options['use_action_comment_form'] == 1) {
	add_action('comment_form', 'cs_print_smilies');
}
?>