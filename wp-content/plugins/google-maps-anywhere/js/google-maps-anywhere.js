/**
 * Added Google Maps
 * @name googlemaps-anywhere.js
 * @author wokamoto - http://dogmap.jp
 * @version 1.2.4
 * @date March 31, 2010
 * @copyright (c) 2008 - 2010 wokamoto (dogmap.jp)
 * @license  Released under the GPL license (http://www.gnu.org/copyleft/gpl.html)
 * @requires jQuery v1.2.3 or later
 */

if (typeof google.maps === 'undefined')
  google.load('maps', '2', {'language' : (typeof googlemapsAnywhereL10n !== 'undefined' ? googlemapsAnywhereL10n.language : 'ja')});

google.setOnLoadCallback(function(){
  var map_opt = jQuery.extend({
    markerTitle:'Move to the Google map.'
   ,cssPath:'div.googlemap'
   ,language:'ja'
   ,errMsgNoData:"Error: No panorama data was found."
   ,errMsgNoFlash:"Error: Flash doesn't appear to be supported by your browser."
   ,errMsgUnknown:"Error: Unknown Error."
   ,mapsURL:'http://maps.google.com/maps'
   }, googlemapsAnywhereL10n);

  var marker_opt = {title:map_opt.markerTitle};
  var check_url = function(v) { return (/s?https?:\/\/[-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+/i).test(v); };

  jQuery(map_opt.cssPath).each( function(){
    var map, marker, mymap, point, map_svClient = false;
    var map_link  = jQuery(this).children('a:first');
    var map_url   = map_link.attr('href');
    var map_title = map_link.attr('title');
    var map_type  = G_NORMAL_MAP;
    var latlng, yaw, pitch, zoom, type = 'n', kml = '';
    var street_view_flag = false;

    switch (map_url.replace(/^.*(\?|\&|\&amp;)maptype=([^\&]+)[\&]?.*$/i,'$2').toUpperCase()) {
     case 'NORMAL':
     case 'G_NORMAL_MAP':
      map_type = G_NORMAL_MAP;
      type = 'n';
      break;
     case 'SATELLITE':
     case 'G_SATELLITE_MAP':
      map_type = G_SATELLITE_MAP;
      type = 'h';
      break;
     case 'HYBRID':
     case 'G_HYBRID_MAP':
      map_type = G_HYBRID_MAP;
      type = 'h';
      break;
     case 'PHYSICAL':
     case 'G_PHYSICAL_MAP':
      map_type = G_PHYSICAL_MAP;
      type = 'p';
      break;
     case 'STREETVIEW':
      street_view_flag = true;
      break;
    };

    if (!street_view_flag) {
      latlng = map_url.replace(/^.*(\?|\&|\&amp;)ll=([^\&]+)[\&]?.*$/i,'$2').split(",");
      if ( /z=([-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+)/i.test(map_url) ) {
        zoom = Number(map_url.replace(/^.*(\?|\&|\&amp;)z=([\d]+)[\&]?.*$/i,'$2'));
        zoom = ((zoom == NaN ? 0 : zoom) > 0 ? zoom : 14);
      } else {
        zoom = 14;
      }
      if ( /kml=([-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+)/i.test(map_url) ) {
	      kml  = map_url.replace(/^.*(\?|\&|\&amp;)kml=([-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+)[\&]?.*$/i,'$2').replace(/%3A/ig,':').replace(/%2F/ig,'/');
         if (!check_url(kml)) kml = '';
      } else {
         kml = '';
      }
    } else {
      latlng = map_url.replace(/^.*(\?|\&|\&amp;)cbll=([^\&]+)[\&]?.*$/i,'$2').split(",");
      if ( /cbp=([-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+)/i.test(map_url) ) {
        var cbp = map_url.replace(/^.*(\?|\&|\&amp;)cbp=([^\&]+)[\&]?.*$/i,'$2').split(",");
        yaw   = (cbp.length >= 2 ? Number(cbp[1]) : 0);
        zoom  = (cbp.length >= 4 ? Number(cbp[3]) : 5);
        pitch = (cbp.length >= 5 ? Number(cbp[4]) : 0);
      } else {
        yaw   = 0;
        zoom  = 5;
        pitch = 0;
      }
    }

    if (latlng.length >= 2) {
      point = new google.maps.LatLng(Number(latlng[0]), Number(latlng[1]));
      if (point) {
        map_url = map_opt.mapsURL
                + '?f=q'
                + '&hl=' + map_opt.language
                + '&geocode='
                + '&q=' + point.toUrlValue()
                + '&ie=UTF8'
                + '&ll=' + point.toUrlValue()
                + '&t=' + type
                + '&z=' + zoom;
        map_link.attr('href', map_url);

        if (!street_view_flag) {
          map = new google.maps.Map2(this);
          map.addControl(new google.maps.SmallZoomControl());
          map.setCenter(point, zoom, map_type);

          if (kml=='' || !check_url(kml)) {
            marker = new google.maps.Marker(map.getCenter(), marker_opt);
            map.addOverlay(marker);
            google.maps.Event.addListener(marker, 'mouseover', function(){
				map.openInfoWindow(point, document.createTextNode(map_title));
			});
            google.maps.Event.addListener(marker, 'click', function(){
				location.href = map_url;
			});
          } else {
            mymap = new GGeoXml(kml);
            map.addOverlay(mymap);
          }

        } else {
          if (map_svClient == false) map_svClient = new google.maps.StreetviewClient();

          map_link.remove();
          map = new google.maps.StreetviewPanorama(this);
          google.maps.Event.addListener(map, "error", function(errorCode) {
           switch (errorCode) {
            case NO_NEARBY_PANO:    alert(map_opt.errMsgNoData);  break;
            case FLASH_UNAVAILABLE: alert(map_opt.errMsgNoFlash); break;
            default:                alert(map_opt.errMsgUnknown); break;
           }
           return;
          });

          map_svClient.getNearestPanorama(point, function(map_sv_data){
           map.setLocationAndPOV(map_sv_data.location.latlng, {yaw:yaw, pitch:pitch, zoom:zoom});
          });
        }
      }
    }
  });
});
