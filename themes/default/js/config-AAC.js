function resetForm() {
	// restore the form to sensible defaults
	$('.aac .user-container').prop('id', '');
	$('#aac-username').val('').prop('placeholder', 'New user name');
	$('#aac-password').val('').prop('placeholder', 'Enter password');
	$('#aac-email-address').val('').prop('placeholder', 'Email address');
	for(i = 0; i < 4; i++) {
		$('#aac-access-level option:eq(' + i + ')').prop('selected', false);
	}
	$("#aac-access-level option:eq('3')").remove();
	$('#aac-access-level option:eq(1)').prop('selected', true);
	$('#aac-created, #aac-modified, #aac-last-login').text('0000-00-00 00:00:00');
}

$(document).ready(function() {
	// set up a listener for the user selector
	$("select[name='user']").on('change', function() {
		// is there a GUID?
		var guid = $("select[name='user']").val();
		if(!guid || guid.length == 0) {
			resetForm();
			$('.aac .user-container').addClass('hidden', 500);
		}
		else if(guid != 'new') {
			// make an API request for the data
			var parameters = {
				'guid': guid
			}
			
			// post the object to the server
			$.post('/user/get', parameters, function(data) {
				// convert the JSON from the server to an object
				var response = JSON.parse(data);
				
				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}

				// fill in the (currently hidden) user form
				else {
					$('#aac-username').val(response.name);
					$('#aac-email-address').val(response.email_address);
					if(response.access_level < 3) {
						$("#aac-access-level option:eq('" + response.access_level + "')").prop('selected', true);
					}
					else {
						$('#aac-access-level').append($('<option>', {value: 3, text: 'SysOp'}));
						$("#aac-access-level option:eq('3')").prop('selected', true);
					}
					$('#aac-created').text(response.created);
					$('#aac-modified').text(response.modified);
					$('#aac-last-login').text(function() {
						if(!response.last_login || response.last_login.length == 0) {
							return 'Never';
						} else {
							return response.last_login;
						}
					});
				
					// unhide the form
					$('.aac .user-container').prop('id', guid).removeClass('hidden', 500);
				}
			});
			
			// enable the delete button
			$('.aac .submit-area .delete-user').removeClass('disabled');
		}
		else if(guid == 'new') {
			// magic keyword indicates that we want to create a new user
			resetForm();
			$('.aac .user-container').prop('id', guid);
			$('.aac .user-container').removeClass('hidden', 500);
			
			// disable the delete button
			$('.aac .submit-area .delete-user').addClass('disabled');
		}
	});
	
	// what happens when a user hits the submit button?
	$('.aac .submit-area .submit').on('click', function() {
		// validate the input on each text field
		var validated = true;
		var fields = [ '#aac-username', '#aac-email-address' ];
		fields.forEach(function(element) {
			if(!$(element).val()) {
				$(element).css('border', '1px solid #F33');
				validated = false;
			}
		});
		
		// if everything is still validated, proceed with the API call
		if(validated) {
			if($('.aac .user-container').prop('id') == 'new') {
				var uri = '/user/create';
				var parameters = {
					'username': $('#aac-username').val(),
					'password': $('#aac-password').val(),
					'email_address': $('#aac-email-address').val(),
					'access_level': $('#aac-access-level').val()
				}
			}
			else if($('.aac .user-container').prop('id').length > 0) {
				var uri = '/user/modify';
				var parameters = {
					'guid': $('.aac .user-container').prop('id'),
					'username': $('#aac-username').val(),
					'password': $('#aac-password').val(),
					'email_address': $('#aac-email-address').val(),
					'access_level': $('#aac-access-level').val()
				}
			}
	
			$.post(uri, parameters, function(data) {
				response = JSON.parse(data);
				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}

				// operation was sucessful
				else {
					alert("User was successfully added or modified! Reloading the page.");
					window.location = '/admin';
				}
			});
		}
	});
	
	// how about the delete button?
	$('.aac .submit-area .delete-user').on('click', function() {
		// get the guid of the user that we want to delete
		var guid = $('.aac .user-container').prop('id');
		if(guid == 'new') {
			$('.aac .submit-area .delete-user').addClass('disabled');
		}
		else {
			var uri = '/user/delete';
			var parameters = {
				'guid': $('.aac .user-container').prop('id')
			}
			
			$.post(uri, parameters, function(data) {
				response = JSON.parse(data);
				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}

				// operation was sucessful
				else {
					alert("User was successfully deleted! Reloading the page.");
					window.location = '/admin';
				}
			});
		}
	});
});