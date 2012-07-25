<?php
// enable smilies
if (!get_option('use_smilies')) {
    update_option('use_smilies', 1);
}

// add admin pages
add_action('admin_menu', 'cs_add_pages');

// add docking box
add_action('dbx_post_sidebar', cs_add_box);
add_action('dbx_page_sidebar', cs_add_box);

// install & upgrade
cs_activate();

// add pages
function cs_add_pages() {
    add_management_page('Manage smilies', 'Smilies', 8, CLCSABSFILE, cs_manage_smilies);
    add_options_page('Smilies Options', 'Smilies', 8, CLCSABSFILE, cs_options);
}

// smilies options page
function cs_options() {
	if ($_POST['update-options']) {
		$updated = false;
        if (get_option('cs_list') != $_POST['list']) {
        	update_option('cs_list', $_POST['list']);
        	$updated = true;
        }
        if ($updated) {
			echo '<div id="message" class="updated fade"><p><b>Preferences updated.</b> Click <a href="../wp-admin/edit.php?page=custom-smilies.php">here</a> if you want to go to Smilies Management page.</p></div>';
		} else {
			echo '<div id="message" class="updated fade"><p><b>No changes made.</b> Click <a href="../wp-admin/edit.php?page=custom-smilies.php">here</a> if you want to go to Smilies Management page.</p></div>';
		}
    }
?>
	<div class="wrap">
        <h2>Smilies Options</h2>
        <form id="smilies-options-form" method="POST" action="" name="smilies-options-form">
        	<table class="optiontable">
				<tr valign="top">
					<th scope="row">
						Display these smilies above the comment form by default:
					</th>				
					<td>
						<input type="text" value="<?php echo get_option('cs_list') ?>" name="list" style="width:95%"><br />
						Put your smilies here, separated by comma. Example: <b>:D, :), :wink:, :(</b><br />
						Leave this field blank if you want to display all smilies.
					</td>
				</tr>
			</table>      
			<p class="submit">
                <input type="submit" value="Update Options &raquo;" name="update-options"/>
            </p>        	
        </form>
      </div>
      <div class="wrap">
        <p>Love this plugin? Why not supporting me by donating a few bucks?</p>
        <center><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
</form></center>
      </div>
<?php
}

// manage smilies page
function cs_manage_smilies() {
    global $wpsmiliestrans;

    // show all or show undefined?
    $su = ($_GET['su'] === '1');

    if ($_POST['update-smilies']) {
        // save smilies to file and refresh $wpsmiliestrans
        $wpsmiliestrans = cs_save_smilies($_POST);
        echo '<div id="message" class="updated fade"><p><strong>Your smilies have been updated.</strong></p></div>';
    }

    // $smilies: 1|gif, 2|gif, etc
    // $old_smilies: :D, :(, etc
    $smilies = cs_get_all_smilies();
    $old_smilies = cs_load_existing_smilies();

?>
    <div class="wrap">
        <h2>Manage Smilies</h2>
        <p>
<?php
        echo ($su) ? '<a href="?page=custom-smilies.php&su=0">Display all smilies</a>' : '<a href="?page=custom-smilies.php&su=1">Display undefined smilies only</a>';
?>
		</p>
		<p align="right">Please note that your smilies cannot contain any of these characters: ' " \</p>        
        <form id="manage-smilies-form" method="POST" action="" name="manage-smilies-form">
            <input type="hidden" name="page" value="custom-smilies.php" />
            <table class="widefat" style="text-align:center">
            <thead>
                <tr>
                    <th scope="col">
                        <div style="text-align: center;">Smilie</div>
                    </th>
                    <th scope="col">
                        <div style="text-align: center;">What to type</div>
                    </th>
                    <th scope="col">
                        <div style="text-align: center;">Smilie</div>
                    </th>
                    <th scope="col">
                        <div style="text-align: center;">What to type</div>
                    </th>
                    <th scope="col">
                        <div style="text-align: center;">Smilie</div>
                    </th>
                    <th scope="col">
                        <div style="text-align: center;">What to type</div>
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
                    <td><img src="../wp-includes/images/smilies/<?php echo $smilie ?>" /></td>
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
                <input type="submit" value="Update &raquo;" name="update-smilies"/>
            </p>
        </form>
    </div>
<?php
}

// scan directory & get all files
function cs_get_all_smilies() {
    if ($handle = opendir('../wp-includes/images/smilies')) {
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
	$url = get_bloginfo('wpurl').'/wp-includes/images/smilies';
	foreach ($wpsmiliestrans as $k => $v) {
		$smilies[$k] = "$url/$v";
	}
	return $smilies;
}

// print smilies list @ comment form
function cs_print_smilies() {
?>
    <script type="text/javascript">
    function grin(tag) {
    	var myField;
    	tag = ' ' + tag + ' ';
        if (document.getElementById('comment') && document.getElementById('comment').type == 'textarea') {
    		myField = document.getElementById('comment');
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
    $url = get_bloginfo('wpurl').'/wp-includes/images/smilies';
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

// add docking box
function cs_add_box() {
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
    </script>
    <fieldset id="smiliesbox" class="dbx-box">
	    <h3 class="dbx-handle">Smilies</h3>
		<div class="dbx-content">
<?php
            $smilies = cs_load_existing_smilies();
            $url = get_bloginfo('wpurl').'/wp-includes/images/smilies';


        	foreach ($smilies as $k => $v) {
            	echo "<img src='{$url}/{$k}' alt='{$v}' onclick='grin(\"{$v}\")' class='wp-smiley-select' /> ";
        	}
?>
        </div>
    </fieldset>
<?php
}
?>