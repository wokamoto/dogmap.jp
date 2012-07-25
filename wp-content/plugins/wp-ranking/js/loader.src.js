window.onload = function(){
setTimeout(function(){
    var uri = '%s';
    jQuery.getJSON(uri, function(){return true;});
}, %d);
}
