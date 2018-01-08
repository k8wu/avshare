$(document).ready(function() {
	// the submit button needs to do something
	$('.admin-module .submit-area .submit').on('click', function() {
		// only submit the options that changed
		var parameters = {}
		if($('#admin-site-title').val()) {
			parameters.title = $('#admin-site-title').val();
		}
		if($('#admin-site-subtitle').val()) {
			parameters.subtitle = $('#admin-site-subtitle').val();
		}
		if($('#admin-default-theme').val()) {
			parameters.default_theme = $('#admin-default-theme').val();
		}
		
		// if the parameters object is populated, execute an API request
		if(Object.keys(parameters).length > 0) {			
			$.post('/config-set-values', parameters, function(data) {
				response = JSON.parse(data);
				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}

				// operation was sucessful
				else {
					alert("Configuration options successfully modified! Reloading the page.");
					window.location = '/admin';
				}
			});
		}
	});
});