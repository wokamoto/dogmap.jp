<?php
require_once('../../../wp-load.php');

//echo get_option('siteurl');
$imagesdirurl = get_option('siteurl') . '/wp-includes/images/smilies/';
$clcs_smilies = get_option('clcs_smilies');
if ($clcs_smilies == false) {
	$clcs_smilies = array(
	':mrgreen:' => 'icon_mrgreen.gif',
	':neutral:' => 'icon_neutral.gif',
	':twisted:' => 'icon_twisted.gif',
	  ':arrow:' => 'icon_arrow.gif',
	  ':shock:' => 'icon_eek.gif',
	  ':smile:' => 'icon_smile.gif',
	    ':???:' => 'icon_confused.gif',
	   ':cool:' => 'icon_cool.gif',
	   ':evil:' => 'icon_evil.gif',
	   ':grin:' => 'icon_biggrin.gif',
	   ':idea:' => 'icon_idea.gif',
	   ':oops:' => 'icon_redface.gif',
	   ':razz:' => 'icon_razz.gif',
	   ':roll:' => 'icon_rolleyes.gif',
	   ':wink:' => 'icon_wink.gif',
	    ':cry:' => 'icon_cry.gif',
	    ':eek:' => 'icon_surprised.gif',
	    ':lol:' => 'icon_lol.gif',
	    ':mad:' => 'icon_mad.gif',
	    ':sad:' => 'icon_sad.gif',
	      '8-)' => 'icon_cool.gif',
	      '8-O' => 'icon_eek.gif',
	      ':-(' => 'icon_sad.gif',
	      ':-)' => 'icon_smile.gif',
	      ':-?' => 'icon_confused.gif',
	      ':-D' => 'icon_biggrin.gif',
	      ':-P' => 'icon_razz.gif',
	      ':-o' => 'icon_surprised.gif',
	      ':-x' => 'icon_mad.gif',
	      ':-|' => 'icon_neutral.gif',
	      ';-)' => 'icon_wink.gif',
	       '8)' => 'icon_cool.gif',
	       '8O' => 'icon_eek.gif',
	       ':(' => 'icon_sad.gif',
	       ':)' => 'icon_smile.gif',
	       ':?' => 'icon_confused.gif',
	       ':D' => 'icon_biggrin.gif',
	       ':P' => 'icon_razz.gif',
	       ':o' => 'icon_surprised.gif',
	       ':x' => 'icon_mad.gif',
	       ':|' => 'icon_neutral.gif',
	       ';)' => 'icon_wink.gif',
	      ':!:' => 'icon_exclaim.gif',
	      ':?:' => 'icon_question.gif',
	);
}


$smilies_sum = count($clcs_smilies);
$smilies_counter = 0;
$smilies_col = 5;
$smilies_row = ceil($smilies_sum/5);
$smilies_space = $smilies_row * $smilies_col - $smilies_sum;
?>
var smilies_list = "\
<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\
<?php foreach ($clcs_smilies as $k => $v) : ?>
<?php if ($smilies_count % $smilies_col == 0) { ?>
	<tr>\
<?php }?>
		<td><a href=\"javascript:grin('<?php echo $k?>', 'content');smilies_win_hide();void(0);\"><img src=\"<?php echo $imagesdirurl; ?><?php echo $v?>\" border=\"0\" alt=\"smilies\" title=\"smilies\" /></a></td>\
<?php
if ($smilies_count >= $smilies_sum - 1) {
	for ($i = 0; $i < $smilies_space; $i++) {
		echo "		<td></td>\\\n";
		$smilies_count++;
	}
}
?>
<?php if ($smilies_count % $smilies_col == $smilies_col - 1) { ?>
	</tr>\
<?php }?>
<?php $smilies_count++; ?>
<?php endforeach; ?>
</table>";