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

	
});