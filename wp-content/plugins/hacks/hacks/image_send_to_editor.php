<?php
/* イメージタグの拡張 */
function add_image_tag($html, $id=0, $alt='', $title='', $align='', $size='') {
 $id = ( 0 < (int) $id ) ? 'attachment_' . $id : '';

 if ($alt == '' && preg_match("/ alt=[\"'][^\"']*[\"'] ?/i", $html)) $alt = preg_replace("/^.* alt=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
 if ($title == '' && preg_match("/ title=[\"'][^\"']*[\"'] ?/i", $html)) $title = preg_replace("/^.* title=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
 if ($alt == ''   && $title != '') $alt   = $title;
 if ($title == '' && $alt != '')   $title = $alt;

 if (preg_match("/^<img [^>]*>/i", $html)) {
  $img_tag    = preg_replace("/^<img ([^>]*)>.*$/i", "$1", $html);
  $img_params = array();

  if (preg_match_all("/([^\s\=]*)\=[\"']([^\"']*)[\"']/i", $img_tag, $matches, PREG_SET_ORDER)) {
   $img_tag = '';
   for ($i=0; $i<count($matches); $i++) $img_params[strtolower($matches[$i][1])] = $matches[$i][2];

   if (isset($img_params['class']))  {
    if ($align == '') $align = preg_replace('/^.*align([^ ]*) ?.*$/i', "$1", $img_params['class']);
    if ($size  == '') $size  = preg_replace('/^.*size\-([^ ]*) ?.*$/i', "$1", $img_params['class']);
   }
   if (!isset($img_params['alt'])   || $img_params['alt'] == '')   $img_params['alt']   = $alt;
   if (!isset($img_params['title']) || $img_params['title'] == '') $img_params['title'] = $title;
   $img_params['border'] = "0";
   if (preg_match("/^(left|right)$/i", $align)) $img_params['align']  = $align;

   $img_tag .= '<img';
   foreach ($img_params as $key => $val) {
    if (preg_match('/(src|alt|title|width|height|class|border|align|rel)/i', $key)) $img_tag .= sprintf(" %s=\"%s\"", $key, $val);
   }
   $img_tag .= ' />';
  }
  if ($img_tag != '') $html = preg_replace("/<img [^>]*>/i", $img_tag, $html);

  unset ($img_params);
  unset ($matches);
 }

 return $html;
}
add_filter('get_image_tag','add_image_tag');

function add_image_send_to_editor($html, $id=0, $alt='', $title='', $align='', $url='', $size='') {
 $id = ( 0 < (int) $id ) ? 'attachment_' . $id : '';

 if ($alt == '' && preg_match("/ alt=[\"'][^\"']*[\"'] ?/i", $html)) $alt = preg_replace("/^.* alt=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
 if ($title == '' && preg_match("/ title=[\"'][^\"']*[\"'] ?/i", $html)) $title = preg_replace("/^.* title=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
 if ($alt == ''   && $title != '') $alt   = $title;
 if ($title == '' && $alt != '')   $title = $alt;

 if (preg_match("/^<a [^>]*>/i", $html)) {
  $a_tag      = preg_replace("/^<a ([^>]*)>.*$/i", "$1", $html);
  $a_params   = array();

  if (preg_match_all("/([^\s\=]*)\=[\"']([^\"']*)[\"']/i", $a_tag, $matches, PREG_SET_ORDER)) {
   $a_tag = '';
   for ($i=0; $i<count($matches); $i++) $a_params[strtolower($matches[$i][1])] = $matches[$i][2];

   if (!isset($a_params['title']) || $a_params['title'] == '') $a_params['title'] = $title;
   $a_params['rel'] = "lightbox";

   $a_tag .= '<a';
   foreach ($a_params as $key => $val) {
    if (preg_match('/(href|title|rel|class)/i', $key)) $a_tag .= sprintf(" %s=\"%s\"", $key, $val);
   }
   $a_tag .= '>';
  }
  if ($a_tag != '') $html = preg_replace("/<a [^>]*>/i", $a_tag, $html);

  unset ($a_params);
  unset ($matches);
 }

 return $html;
}
add_filter('image_send_to_editor','add_image_send_to_editor');
