("use strict");
var $ = jQuery;

// accordion js
$(document).ready(function () {
  $( "*" ).each(function( index ) {
    var el = $(this);
    if( $( this ).attr('custom_attributes')){
    var custom_attr =  $( this ).attr('custom_attributes').split(',');
                custom_attr.forEach(function(attrData) {
      
    var split_err =  attrData.split('=');
                    var first_attr = split_err[0];
                    var second_attr = split_err[1];
                    var custom_attr_string = first_attr+'="'+second_attr+'"';
    el.attr(first_attr,second_attr);
    el.removeAttr('custom_attributes');
                })
    }
    });

    $('.wp-block-md-mytable td').on('click',function(){
      $(this).toggleClass("selected")
    })
})
