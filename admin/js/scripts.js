jQuery(document).ready(function($) {

  /*
  * Datepicker
   */
  if ($.fn.datepicker) {
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        firstDay: 1
    });

    $('.reset-datepicker').click(function(e){
      e.preventDefault();

      $('.reset-datepicker').prev('input').val('');
    });
  }

  /*
  * Select2
   */
  if ($.fn.select2) {
    $('.select2').select2({
      // theme: "classic",
      width: 'resolve' // need to override the changed default
    });
  }

  /*
  * Color picker
   */
  if ($.fn.wpColorPicker) {
    $('.color-picker').wpColorPicker(
      'option',
      'change',
      function(event, ui) {
        cookie_preview();
      }
    );
  }

  /*
  * Input Number spinner
   */
  if ($.fn.spinner) {
    // Cookie expire
    $( "#cookie_expire" ).spinner({
      'min': '-1'
    });
    // Cookie duration
    $( "#cookie_display_duration" ).spinner({
      'min': 0,
      stop: function( event, ui ) {
        cookie_preview();
      }
    });
    // Border radius
    $( ".cookie_spinner" ).spinner({
      'min': 0,
      'max': 20,
      stop: function( event, ui ) {
        cookie_preview();
      }
    });
    // Close button size
    $( "#cookie_close_button_size" ).spinner({
      'min': 0,
      'max': 50,
      stop: function( event, ui ) {
        cookie_preview();
      }
    });
  }
  /*
  * Delete cookie confirmation
   */
  $('.delete-list-item').click(function(e){
    if (!confirm('Are you sure you want to delete the cookie?')) e.preventDefault();
  });

  /*
	 * Select/Upload image(s) event
	 */
	$('body').on('click', '.cookie_upload_image_button', function(e){
		e.preventDefault();

    		var button = $(this),
    		    custom_uploader = wp.media({
			title: 'Insert image',
			library : {
				// uncomment the next line if you want to attach image to the current post
				// uploadedTo : wp.media.view.settings.post.id,
				type : 'image'
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false // for multiple image selection set to true
		}).on('select', function() { // it also has "open" and "close" events
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			$(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();
			/* if you sen multiple to true, here is some code for getting the image IDs
			var attachments = frame.state().get('selection'),
			    attachment_ids = new Array(),
			    i = 0;
			attachments.each(function(attachment) {
 				attachment_ids[i] = attachment['id'];
				console.log( attachment );
				i++;
			});
			*/
		})
		.open();
	});

	/*
	 * Remove image event
	 */
	$('body').on('click', '.cookie_remove_image_button', function(){
		$(this).hide().prev().val('').prev().addClass('button').html('Upload image');
		return false;
	});

  /*
  * Cookie preview checkbox
   */
  $('#cookie_preview_checkbox').change(function(e){
    if ($(this).is(':checked'))
    {
      // Show preview div
      $('#cookie_preview').fadeIn();
    }
    else {
      // Hide preview div
      $('#cookie_preview').fadeOut();
    }
  });

  /*
  * Cookie preview actions
   */
  $('.cookie_preview_action').change(function(e){
    cookie_preview();
  });

  // Cookie close button action
  $('.jm-cookie-close').click(function(e) {
    e.preventDefault();

    // Hide the cookie notification
    $(this).closest('.jm-cookie-message-layer').fadeOut();

    // Uncheck preview checkbox
    $('#cookie_preview_checkbox').prop('checked', false);

  });

  /*
  * Cookie preview function
   */
  function cookie_preview() {
    console.log('cookie_preview');

    // Remove all classes
    $('#cookie_preview').removeClass();

    // Add the main class
    $('#cookie_preview').addClass('jm-cookie-message-layer');

    // Change cookie design
    $('#cookie_preview').addClass($('input[name=cookie_design]:checked').val());

    // Placement
    // $('#cookie_preview').addClass($('#cookie_heading').val());
    $('#cookie_preview').addClass($('input[name=cookie_heading]:checked').val());

    // border radius
    $('#cookie_preview').css({'border-radius': $('#cookie_border_radius').val()+'px'});

    // Cookie preview headline
    if ($('#cookie_headline_size').val()!="" && $('input[name=cookie_design]:checked').val()=='big') var headline = '<'+$('#cookie_headline_size').val()+'>'+$('#cookie_headline').val()+'</'+$('#cookie_headline_size').val()+'>';
    else var headline = $('#cookie_headline').val();

    // Headline
    $('#preview_headline').html(headline);

    // Content (if this is a small design then hide the content)
    if ($('input[name=cookie_design]:checked').val()=='big') $('#preview_content').show().html($('#cookie_content').val());
    else $('#preview_content').hide();

    // Set background color
    $('#cookie_preview').css( "background-color", $('#cookie_background_color').val() );

    // Set text color
    $('#cookie_preview').css( "color", $('#cookie_text_color').val() );

    // Set headline text color
    $('#preview_headline h1, #preview_headline h2, #preview_headline h3, #preview_headline h4, #preview_headline strong').css( "color", $('#cookie_text_color').val() );

    // Generate button if button text is defined and if the design is big
    if ($('#cookie_button').val()!="" && $('input[name=cookie_design]:checked').val()=='big')
    {

      // Show button
      $('#preview_button').show();

      // If theres a url defined
      var url = '';
      if ($('#cookie_url').val()) url = $('#cookie_url').val();

      var button = '<a href="'+url+'" class="jm-cookie-button">' + $('#cookie_button').val() + '</a>'

      // Inster Button content
      $('#preview_button').html(button);

      // Set the button background color
      $('.jm-cookie-button').css('background-color', $('#cookie_button_background_color').val());

      // Set the button text color
      $('.jm-cookie-button').css('color', $('#cookie_button_text_color').val());

      // Set the button border radius
      $('.jm-cookie-button').css({'border-radius': $('#cookie_button_border_radius').val()+'px'});

    }
    else $('#preview_button').hide();

    // Close button background color
    $('.jm-cookie-close svg circle').attr('fill', $('#cookie_close_button_background_color').val());

    // Close button text color
    $('.jm-cookie-close svg text').attr('fill', $('#cookie_close_button_text_color').val());

    // Width and height of close button
    $('.jm-cookie-close svg').css('width', $('#cookie_close_button_size').val());
    $('.jm-cookie-close svg').css('height', $('#cookie_close_button_size').val());

    // Hide close button
    if ($('#cookie_hide_close_button').is(':checked')) $('.jm-cookie-close').hide();
    else $('.jm-cookie-close').show();

  }

  /*
   * Close button action
   */
  $(document).on('click', '.jm-cookie-button', function(e){
    e.preventDefault();

    // Hide the cookie notification
    $(this).closest('.jm-cookie-message-layer').fadeOut();

    // Uncheck preview checkbox
    $('#cookie_preview_checkbox').prop('checked', false);

  });

  /*
  * Submit cookie form validation
   */
  $('#cookieform').submit(function(e){
    var error = '';

    // If neither accept on close, button or view is selected
    if (!$('#cookie_close_accept').is(':checked') && !$('#cookie_button_accept').is(':checked') && !$('#cookie_view_accept').is(':checked')) {
      error += 'You should check at least one accept method.\n\n';
    }

    // If the close button is hidden and the button text aint define and view accept is not checked
    if (!$('#cookie_button').val() && $('#cookie_hide_close_button').is(':checked') && !$('#cookie_view_accept').is(':checked')) {
      error += 'The cookie will not be able to be accepted if the close button is hidden, no button text is defined and view accept is not checked.\n\n';
    }

    // If no accept method is selected
    if (!$('#cookie_close_accept').is(':checked') && !$('#cookie_button_accept').is(':checked')) {
      error += 'The cookie will not be able to be accepted if no accept method is selected.\n\n';
    }

    // On error show confirm
    if (error) {
      if (!confirm(error)) e.preventDefault();
    }
  });
});
