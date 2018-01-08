$(document).ready(function() {
	// add an event listener to the Submit button
	$('.login-form .button').on('click', function() {
		// check to see if the fields are filled out
		if(!$('#username').val() || !$('#password').val()) {
			$('.login-form .status').text('Both fields need to be filled out!').show();
		}
		
		// get the values from both fields
		else {
			var parameters = {
				'username': $('#username').val(),
				'password': $('#password').val()
			}
		
			// post the object to the server
			$.post('/user/process-login', parameters, function(data) {
				// convert the JSON from the server to an object
				var response = JSON.parse(data);
				if(response.response == "error") {
					$('.login-form .status').text(response.message).show();
				}
				else if(response.response == "ok") {
					$('.login-form .status').text('Login successful! Redirecting.').show();
					window.location = response.location;
				}
			});
		}
	});
	
	
	$('.login-form').keypress(function (e) {
		if (e.which == 13) {
			$('.login-form .button').trigger('click');
			return false;
		}
	});
});