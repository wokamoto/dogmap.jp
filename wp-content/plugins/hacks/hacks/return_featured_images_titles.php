<?php
// return back title tag for images removed in WP 3.5
add_filter('get_image_tag','image_tag_add', 10, 5);
function image_tag_add($html, $id=0, $alt='', $title='', $align='', $size='') {
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
      for ($i=0; $i<count($matches); $i++) {
        $img_params[strtolower($matches[$i][1])] = $matches[$i][2];
      }

      if (!isset($img_params['alt'])   || $img_params['alt'] == '')
        $img_params['alt']   = $alt;
      if (!isset($img_params['title']) || $img_params['title'] == '')
        $img_params['title'] = $title;

      $img_tag .= '<img';
      foreach ($img_params as $key => $val) {
        if (preg_match('/(src|alt|title|width|height|class|border|align|rel)/i', $key))
          $img_tag .= sprintf(" %s=\"%s\"", $key, $val);
      }
      $img_tag .= ' />';
   }
   if ($img_tag != '')
     $html = preg_replace("/<img [^>]*>/i", $img_tag, $html);

     unset ($img_params);
     unset ($matches);
   }

   return $html;
}

function return_featured_images_titles($attr, $attachment) {
  if (!isset($attr['title']) && isset($attachment->post_title) && $attachment->post_title !='') {
    $attr['title'] = $attachment->post_title;
  }
  return $attr;
}

add_filter('media_send_to_editor', 'return_featured_images_titles_media_send_to_editor', 11, 3);
function return_featured_images_titles_media_send_to_editor($html, $attachment_id, $attachment) {
  $attachment = get_post($attachment_id);
  $title = $attachment->post_title;
  $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
  $html = preg_replace(
    array('/(title=")[^"]*(")/i', '/(alt=")[^"]*(")/i'),
    array('$1'.$title.'$2', '$1'.$alt_text.'$2'),
    $html);
  return $html;
}
