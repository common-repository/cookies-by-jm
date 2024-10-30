jQuery(document).ready(function($) {

  /*
  * Cookie close button action
   */
  $('.jm-cookie-close').click(function(e) {
    e.preventDefault();

    // If accepts for the close buttin is set
    if ($(this).attr('data-accept'))
    {
      // Set the cookie
      $.ajax({
        url: jm_cookies_ajax_object.ajax_url,
        // dataType: 'json',
        method: 'POST',
        cache: false,
        data: {
          'action': 'jm_cookies_set_cookie_ajax',
          'id': $(this).closest('.jm-cookie-message-layer').attr('data-id'),
          'accept': 1
        }
      })
      .done(function( data ) {
        console.log(data);
      });
    }

    // Hide the cookie notification
    $(this).closest('.jm-cookie-message-layer').fadeOut();
  });

  /*
  * Check for accept tracking on button click
   */
   $('.jm-cookie-button').click(function(e) {

     // If button accept is activated
     if ($(this).attr('data-accept')) {
       // Set the cookie
       $.ajax({
         url: jm_cookies_ajax_object.ajax_url,
         // dataType: 'json',
         method: 'POST',
         cache: false,
         data: {
           'action': 'jm_cookies_set_cookie_ajax',
           'id': $(this).closest('.jm-cookie-message-layer').attr('data-id'),
           'accept': true
         }
       })
       .done(function( data ) {
         console.log(data);
       });
     }

     // If a button url is set
     if ($(this).attr('href')) location.href=$(this).attr('href');
     else {
       // Close cookie
       $(this).closest('.jm-cookie-message-layer').fadeOut();

       e.preventDefault();
     }

   });

  /*
  * For each cookie notice that have tracking enabled
   */
  $( '.cookie_tracking' ).each(function( index ) {
    $.ajax({
      url: jm_cookies_ajax_object.ajax_url,
      // dataType: 'json',
      method: 'POST',
      cache: false,
      data: {
          'action': 'jm_cookies_frontend_tracking_ajax',
          'id': $(this).attr('data-id')
      }
    })
    .done(function( data ) {
      // console.log(data);
    });
  });

});
