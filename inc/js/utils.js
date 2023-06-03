jQuery( function( $ ) {
  jQuery(document).ready(function() {
    jQuery(document).on('DOMSubtreeModified', '#bkap_show_stock_status', function(){

      if(jQuery("#bkap_show_stock_status").text()){
        jQuery("#bkap_show_stock_status").get(0).scrollIntoView({block: "center"});
      }
    });
  })
});