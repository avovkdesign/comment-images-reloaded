jQuery(function($){

	$('#convert_images').click(function(e){
		e.preventDefault();
		var answer = my_alert();
		if(answer){
			$.post(
                cmr_reloaded_ajax_object.ajax_url, 

                {
                    action: "convert_img",
                },

                function(response) {
                		$('.updated.settings-error.notice.is-dismissible').show();
                		//var count = num2word(response);
                		$('.responce_convert').text(response);
                    });
        }
	});

    // delete images
    $('.delete-cid').click( function(e){
        e.preventDefault();
        var td = $(this).parents('td');

        if ( confirm(cmr_reloaded_ajax_object.before_delete_text) ) {

            $.post(
                cmr_reloaded_ajax_object.ajax_url, 

                {
                    action: "cir_delete_image",
                    cid: $(this).attr('data-cid'),
                    aid: $(this).attr('data-aid'),
                },

                function(response) {
                    console.log(response);
                    if ( 'true' == response ) {
                        $(td).html( cmr_reloaded_ajax_object.after_delete_text );
                    }
                }
            );
            
        } // end confirm check

    });

	
});