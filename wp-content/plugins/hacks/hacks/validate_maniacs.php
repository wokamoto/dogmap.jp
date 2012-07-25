<?php
class ValidateManiacsController {
 function ValidateManiacsController() {
 }

 // <embed> tag validator
 function get_object_tag($param, $ktai_flag = false) {
  if (!is_array($param)) return (false);

  $video_param = array(
    'YouTube'     => array('match' => '/\.youtube\.com\//i',      'width' => 425, 'height' => 355)
   ,'metacafe'    => array('match' => '/\.metacafe\.com\//i',     'width' => 400, 'height' => 345)
   ,'liveleak'    => array('match' => '/\.liveleak\.com\//i',     'width' => 450, 'height' => 370)
   ,'googlevideo' => array('match' => '/video\.google\.com\//i',  'width' => 400, 'height' => 326)
   ,'dailymotion' => array('match' => '/\.dailymotion\.com\//i',  'width' => 420, 'height' => 330)
   ,'ifilm'       => array('match' => '/\.(ifilm|spike)\.com\//i','width' => 448, 'height' => 365)
   ,'superdeluxe' => array('match' => '/\.superdeluxe\.com\//i',  'width' => 400, 'height' => 350)
  );

  if (!isset($param["vurl"])) $param["vurl"] = "";
  if (!isset($param["url"])) $param["url"] = "";
  if (!isset($param["title"])) $param["title"] = "";
  if (!isset($param["width"])) $param["width"] = 0;
  if (!isset($param["height"])) $param["height"] = 0;
  if (!isset($param["type"])) $param["type"] = "application/x-shockwave-flash";
  if (!isset($param["class"])) $param["class"] = "";
  if (!isset($param["style"])) $param["style"] = "";
  if (!isset($param["flashvars"])) $param["flashvars"] = "";
  if (!isset($param["allowfullscreen"])) $param["allowfullscreen"] = "";

  $param_tags = array();

  $param["vurl"] = str_replace("&#038;", "&amp;", $param["vurl"]);
  $param["url"]  = str_replace("&#038;", "&amp;", $param["url"]);

  $matched = false;
  foreach($video_param as $key => $val) {
   if (preg_match($val['match'], ($param["url"]!=''?$param["url"]:($param["vurl"]!=''?$param["vurl"]:$param["flashvars"])))) {
    if ($param["width"]  == 0) $val['width'];
    if ($param["height"] == 0) $val['height'];

    switch ($key) {
    case 'YouTube':
     if ($param["vurl"] != '') {
      $vid  = preg_replace("/^.*\.youtube\.com\/v\/(.*)$/i", "$1", $param["vurl"]);
      $param["url"]  = 'http://www.youtube.com/watch?v='.$vid;
     } else {
      $param["url"]  = preg_replace("/&.*$/i", "", $param["url"]);
      $vid  = preg_replace("/^.*\.youtube.*watch.*?v=(.*)$/i", "$1", $param["url"]);
      $param["vurl"] = 'http://www.youtube.com/v/'.$vid;
     }
     $param["flashvars"]='';
     break;

    case 'metacafe':
     if ($param["vurl"] != '' || $param["flashvars"] != '') {
      $vid  = preg_replace("/^.*\.metacafe.*fplayer\/(.*)\.swf$/i", "$1", $param["vurl"]);
      $param["url"]  = 'http://www.metacafe.com/watch/'.$vid.'/';
     } else {
      $vid  = preg_replace("/^.*\.metacafe.*watch\/(.*)$/i", "$1", preg_replace("/\/$/i", "", preg_replace("/\?.*$/", "", $param["url"])));
      $param["vurl"] = 'http://www.metacafe.com/fplayer/'.$vid.'.swf';
     }
     break;

    case 'liveleak':
     if ($param["vurl"] != '' || $param["flashvars"] != '') {
      $vid  = preg_replace("/^.*token=(.*)$/i", "$1", ($param["flashvars"]!=''?$param["flashvars"]:$param["vurl"]));
      $param["url"]  = 'http://www.liveleak.com/view?i='.$vid;
     } else {
      $vid  = preg_replace("/^.*\.liveleak.*view.*?i=(.*)$/i", "$1", $param["url"]);
      $param["vurl"] = 'http://www.liveleak.com/player.swf?autostart=false&amp;token='.$vid;
     }
     $param["flashvars"]='';
     $param_tags = array("quality"=>"high", "flashvars"=>$param["flashvars"]);
     break;

    case 'googlevideo':
     if ($param["vurl"] != '') {
      $vid  = preg_replace("/^.*\?docid=([^\&]*)[\&]?.*$/i", "$1", $param["vurl"]);
      $param["url"] = 'http://video.google.com/videoplay?docid='.$vid.'&amp;hl=en';
     } else {
      $vid  = preg_replace("/^.*video\.google\.com\/videoplay.*docid=(.*)$/i", "$1", $param["url"]);
      $param["vurl"] = 'http://video.google.com/googleplayer.swf?docId='.$vid.'&#038;hl=en';
     }
     $param["flashvars"]='';
     $param_tags = array("flashvars"=>$param["flashvars"]);
     break;

    case 'dailymotion':
     if ($param["vurl"] != '') {
      $param["vurl"] = preg_replace("/&.*$/i", "", $param["vurl"]);
      $vid  = preg_replace("/^.*\/swf\/(.*)$/i", "$1", $param["vurl"]);
      $param["url"]  = 'http://www.dailymotion.com/video/'.$vid;
     } else {
      $vid  = preg_replace("/^.*\.dailymotion\.com\/video\/([^_]*).*$/i", "$1", $param["url"]);
      $param["vurl"] = 'http://www.dailymotion.com/swf/'.$vid;
     }
     $param_tags = array("allowFullScreen"=>"true", "allowScriptAccess"=>"always");
     break;

    case 'ifilm':
     if ($param["flashvars"] != '') {
      $vid  = preg_replace("/^flvbaseclip=([^\&]*)\&$/i", "$1", $param["flashvars"]);
      $param["url"]  = 'http://www.spike.com/video/'.$vid;
     } else {
      $vid  = preg_replace("/^.*\.(ifilm|spike)\.com.*video\/(.*)$/i", "$2", preg_replace('/\/$/i', '', preg_replace('/\?.*$/', '', $param["url"])));
      $param["vurl"] = 'http://www.spike.com/efp';
     }
     $param["flashvars"] = "flvbaseclip=".$vid."&";
     $param_tags = array("flashvars"=>$param["flashvars"], "quality"=>"high", "bgcolor"=>"000000");
     break;

    case 'superdeluxe':
     if ($param["flashvars"] != '') {
      $vid  = preg_replace("/^id=([^\&]*)$/i", "$1", $param["flashvars"]);
      $param["url"]  = 'http://www.superdeluxe.com/sd/contentDetail.do?id='.$vid;
     } else {
      $vid  = preg_replace("/^.*\.superdeluxe\.com\/sd\/contentDetail\.do\?id=(.*)$/i", "$1", $param["url"]);
      $param["vurl"] = 'http://www.superdeluxe.com/static/swf/share_vidplayer.swf';
     }
     $param["flashvars"] = "id=".$vid;
     $param_tags = array("FlashVars"=>$param["flashvars"], "quality"=>"high", "allowFullScreen"=>"true");
     break;

    default:
     break;

    }
    $matched = true;
    break;
   }
  }

  if ($param["title"] == '') $param["title"] = $param["url"];

  if ($ktai_flag) {
   $src = '<a href="'.$param["url"].' title="'.$param["title"].'">'.$param["title"].'</a>';

  } elseif ($matched) {
   $param_tags = array_merge(array("movie"=>$param["vurl"], "wmode"=>"transparent"), $param_tags);
   $src   = '<object data="'.$param["vurl"].'" type="'.$param["type"].'" width="'.$param["width"].'" height="'.$param["height"].'"';
   if ($param["class"] != '') {$src .= ' class="'.$param["class"].'"';}
   if ($param["style"] != '') {$src .= ' style="'.$param["style"].'"';}
   $src .= '>';
   foreach($param_tags as $key => $val) {
    if ($val != '') {$src .= '<param name="'.$key.'" value="'.$val.'" />';}
   }
   if ($param["url"] != '') {$src .= '<a href="'.$param["url"].'" title="'.$param["title"].'">'.$param["title"].'</a>';}
   $src .= '</object>';

  } else {
   $src = false;

  }
  unset($param_tags);
  return ($src);
 }

 // percent encoding uri validator
 function percent_encoding_to_upper($uri) {
   if (preg_match('/(%([a-f][0-9a-fA-F]|[0-9A-F][a-f]))+/', $uri)) {
    return preg_replace_callback('/(%[0-9a-f]{2}?)+/', create_function('$matches','return strtoupper($matches[0]);'), $uri);
   } else {
    return false;
   }
 }

 // Link Filter
 function addLinkFilter($link) {
  $url = $this->percent_encoding_to_upper($link);
  if ($url != false) {$link = $url;}
  return $link;
 }

 // Content Filter
 function addContentFilter($content) {
  $ktai_flag = (function_exists("is_mobile") && is_mobile()) || (function_exists("is_ktai") && is_ktai());

  $search_strings  = array();
  $replace_strings = array();
  $j = 0;

  $search_strings_2  = array();
  $replace_strings_2 = array();
  $k = 0;

  // object tag found
  if(preg_match("/<object [^>]*>/i", $content)) {
   preg_match_all("/<object([^>]*)>([^\[]*)<\/object>/i", $content, $matches, PREG_SET_ORDER);
   for ($i=0; $i<count($matches); $i++) {
    $param = array();
    $matches[$i][1] = preg_replace('/[\r\n]/im', '', $matches[$i][1]);
    $matches[$i][2] = preg_replace('/[\r\n]/im', '', $matches[$i][2]);
    if(preg_match("/<embed [^>]*>/i", $matches[$i][2])) {
     if (preg_match_all("/[ \t]*([^\=]*)\=[\"']([^ ]*)[\"'][ \t]*/i", preg_replace("/^.*<embed ([^\]]*)>.*$/i", "$1", $matches[$i][2]), $options, PREG_SET_ORDER)) {
      for ($k=0; $k<count($options); $k++) {$param[strtolower($options[$k][1])] = $options[$k][2];}
      $param["vurl"] = $param["src"];
     }
    } else {
     if (preg_match_all("/[ \t]*([^\=]*)\=[\"']([^ ]*)[\"'][ \t]*/i", $matches[$i][1], $options, PREG_SET_ORDER)) {
      for ($k=0; $k<count($options); $k++) {$param[strtolower($options[$k][1])] = $options[$k][2];}
      $param["vurl"] = $param["data"];
     }
    }
    if ($param["type"] == 'application/x-shockwave-flash') {
     $search_string  = "/".preg_quote($matches[$i][0],"/")."/i";
     $replace_string = $this->get_object_tag($param, $ktai_flag);
     if ($replace_string != false && $replace_string != $matches[$i][0]) {
      $search_strings[$j]  = $search_string;
      $replace_strings[$j] = $replace_string;
      $j++;
     }
    }
    unset($options);
    unset($param);
   }
   unset($matches);
  }

  // embed tag found
  if(preg_match("/<embed [^>]*>/i", $content)) {
   preg_match_all("/<embed([^>]*)><\/embed>/i", $content, $matches, PREG_SET_ORDER);
   for ($i=0; $i<count($matches); $i++) {
    $param = array();
    $matches[$i][1] = preg_replace('/[\r\n]/im', '', $matches[$i][1]);
    if (preg_match_all("/[ \t]*([^\=]*)\=[\"']([^ ]*)[\"'][ \t]*/i", $matches[$i][1], $options, PREG_SET_ORDER)) {
     for ($k=0; $k<count($options); $k++) {$param[strtolower($options[$k][1])] = $options[$k][2];}
     $param["vurl"] = $param["src"];
    }
    if ($param["type"] == 'application/x-shockwave-flash') {
     $search_string  = "/".preg_quote($matches[$i][0],"/")."/i";
     $replace_string = $this->get_object_tag($param, $ktai_flag);
     if ($replace_string != false && $replace_string != $matches[$i][0]) {
      $search_strings[$j]  = $search_string;
      $replace_strings[$j] = $replace_string;
      $j++;
      $search_strings_2[$k]  = $search_string;
      $replace_strings_2[$k] = $replace_string;
      $k++;
     }
    }
    unset($param);
   }
   unset($matches);
  }

  // a tag (includes [alt], [target="_blank"]) found
  if(preg_match("/<a [^>]*(alt=['\"][^>'\"]*['\"]|target=['\"]_blank['\"])[^>]*>/i", $content)) {
   preg_match_all("/<a [^>]*(alt=['\"][^>'\"]*['\"]|target=['\"]_blank['\"])[^>]*>/i", $content, $matches, PREG_SET_ORDER);
   for ($i=0; $i<count($matches); $i++) {
    $search_string  = "/".preg_quote($matches[$i][0],"/")."/i";
    $replace_string = preg_replace("/[\t ]+(alt=['\"][^>'\"]*['\"]|target=['\"]_blank['\"])/i", '', $matches[$i][0]);
    if ($replace_string != $matches[$i][0]) {
     $search_strings[$j]  = $search_string;
     $replace_strings[$j] = $replace_string;
     $j++;
     $search_strings_2[$k]  = $search_string;
     $replace_strings_2[$k] = $replace_string;
     $k++;
    }
   }
   unset($matches);
  }

  // img tag found
  if(preg_match("/<img [^>]*>/i", $content)) {
   preg_match_all("/<img ([^>]*)\/?>/i", $content, $matches, PREG_SET_ORDER);
   for ($i=0; $i<count($matches); $i++) {
    $search_string  = $matches[$i][0];
    $img_params = array();
    if (preg_match_all("/([^\s\=]*)\=[\"']([^\"']*)[\"']/i", $matches[$i][1], $params, PREG_SET_ORDER)) {
     for ($k=0; $k<count($params); $k++) $img_params[strtolower($params[$k][1])] = $params[$k][2];
    }
    unset($params);

    $filename = (isset($img_params['src']) && $img_params['src'] != '' ? preg_replace('/^.*\//', '', $img_params['src']) : 'image');
    if (!isset($img_params['alt'])   || $img_params['alt'] == '')   $img_params['alt']   = (isset($img_params['title']) ? $img_params['title'] : $filename);
    if (!isset($img_params['title']) || $img_params['title'] == '') $img_params['title'] = (isset($img_params['alt']) ? $img_params['alt'] : $filename);
    $replace_string  = '<img';
    foreach ($img_params as $key => $val) {
     if (preg_match('/(src|alt|title|width|height|class|border|align|rel)/i', $key)) $replace_string .= sprintf(" %s=\"%s\"", $key, $val);
    }
    $replace_string .= ' />';
    unset($img_params);

    if ($replace_string != $search_string) {
     $search_strings[$j]  = "/".preg_quote($search_string, "/")."/i";
     $replace_strings[$j] = $replace_string;
     $j++;
    }
   }
   unset($matches);
  }

  // Percent Encoding URI or ld_archives URI found!
  if(preg_match("/<[aA] ?[^ ]* [hH][rR][eE][fF]=['\"]http:\/\/.*((%([a-f][0-9a-fA-F]|[0-9A-F][a-f]))+|\/ld_archives\/[0-9]+\.html).*['\"]/", $content)){
   preg_match_all("/(http:\/\/[^ \"']*)/i", $content, $matches, PREG_SET_ORDER);
   for ($i=0; $i<count($matches); $i++) {
    if(preg_match("/(%([a-f][0-9a-fA-F]|[0-9A-F][a-f]))+/", $matches[$i][0])) {
     // Percent Encoding URI found!
     $search_string  = "/".preg_quote($matches[$i][0],"/")."/i";
     $replace_string = $this->percent_encoding_to_upper($matches[$i][0]);
     if ($replace_string != false && $replace_string != $matches[$i][0]) {
      $search_strings[$j]  = $search_string;
      $replace_strings[$j] = $replace_string;
      $j++;
      $search_strings_2[$k]  = $search_string;
      $replace_strings_2[$k] = $replace_string;
      $k++;
     }
    }
   }
   unset($matches);
  }

  // <p></p> found
  if(preg_match("/<p>[\s\t]*?<\/p>/i", $content)) {
   $search_strings[$j]  = '/<p>[\s\t]*?<\/p>\n*/i';
   $replace_strings[$j] = '';
   $j++;
   $search_strings_2[$k]  = '/<p>[\s\t]*?<\/p>\n*/i';
   $replace_strings_2[$k] = '';
   $k++;
  }

  // <br clear="all" /><br /> found
  if(preg_match("/(<br[\s\t]*[^>]*[\s\t]*\/?>)<br[\s\t]*\/?>/i", $content)) {
   $search_strings[$j]  = '/(<br[\s\t]*[^>]*[\s\t]*\/?>)<br[\s\t]*\/?>/i';
   $replace_strings[$j] = '$1';
   $j++;
   $search_strings_2[$k]  = '/(<br[\s\t]*[^>]*[\s\t]*\/?>)<br[\s\t]*\/?>/i';
   $replace_strings_2[$k] = '$1';
   $k++;
  }

//  // more-link
//  if(preg_match("/<a href=\"[^\"]*?\#more\-[0-9]+\" class=\"more\-link\">.*?</a>/i", $content)) {
//   preg_match_all("/<a href=\"([^\"]*?)\#more\-[0-9]+\" class=\"more\-link\">(.*?)</a>/i", $content, $matches, PREG_SET_ORDER);
//   for ($i=0; $i<count($matches); $i++) {
//    $search_strings[$j]  = "/".preg_quote($matches[$i][0],"/")."/i";
//    $replace_strings[$j] = sprintf("<a href=\"%s\" class=\"%s\">%s</a>"
//                                  , $matches[$i][1]
//                                  , "more-link"
//                                  , $matches[$i][2]
//                                  );
//    $j++;
//   }
//   unset($matches);
//  }

  if ($j > 0) {
   $content = preg_replace($search_strings, $replace_strings, $content);
  }

  $write_back = ($k > 0 && is_single() && defined('WRITE_BACK') && WRITE_BACK);
  if ($write_back && !$ktai_flag) {
   global $post;
   $post->post_content = preg_replace($search_strings_2, $replace_strings_2, $post->post_content);
   wp_update_post($post);
   echo "<!-- Replaced OK! (Validate Maniacs) -->\n";
  }

  unset($search_strings);
  unset($replace_strings);
  unset($search_strings_2);
  unset($replace_strings_2);
  return ($content);
 }
}

$ValidateManiacs = new ValidateManiacsController();

add_filter('post_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('page_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('tag_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('category_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('preview_post_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('get_comment_author_link', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('get_comment_author_url', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('get_the_guid', array(&$ValidateManiacs, 'addLinkFilter'));
add_filter('the_content', array(&$ValidateManiacs, 'addContentFilter'), 11);

unset($ValidateManiacs);
