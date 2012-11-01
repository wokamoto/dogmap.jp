/*
    javascript number_format();
*/
String.prototype.number_format = function(){
    if( isNaN(this) ){
        return NaN;
    }
    var num = this + "";
    num = num.split(".",2);
    var is_minus = 0;
    if( parseInt( num[0] ) < 0 ){
        num[0] = num[0].substring( 1, num[0].length );
        is_minus = 1;
    }
    var len = num[0].length;
    var place = new Array;
    for( var i=3; i<=len+2; i+=3 ){
        place[place.length] = num[0].substring( len-i, len-i+3 );
    }
    var value = "";
    for( var i=place.length-1; i>=0; i--){
        value += place[i]+",";
    }
    value = value.replace(/,$/,"");
    if(num[1]){
        value += "."+num[1];
    }
    if( is_minus ){
        return '-' + value;
    }else{
        return value;
    }
}

String.prototype.number_unformat = function(){
    var place = (this+"").match(/[\d.]*/g);
    var value   = "";
    for(var i=0;i<place.length;i++){
        value += place[i];
    }
    return value-0;
}

Number.prototype.number_format    = String.prototype.number_format;
Number.prototype.number_unformat = String.prototype.number_unformat;

(function($){
if (categories.length) {
    for (var i=0; i<categories.length; i++) {
        $('#cats').append('<th>'+categories[i]+'</th>');
        $('#points').append('<td>'+used[i].number_format()+'</td>');
        $('#req').append('<td>'+requests[i].number_format()+'</td>');
        $('#tra').append('<td>'+transfers[i].number_format()+'</td>');
    }
} else {
    $('#booster-table').hide();
    $('.balance').hide();
    $('<p style="font-size:300%;">No Data. Please wait...</p>').insertBefore('#booster-table');
}
})(jQuery);

