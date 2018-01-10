function resetRoomForm() {
	// restore the form to sensible defaults
	$('.room .rooms-specific').prop('id', '');
	$('#room-name').val('').prop('placeholder', 'New Name');
	$('#room-uri').val('').prop('placeholder', 'new-uri');
   $('#room-max-users').val('').prop('placeholder', $('#room-global-max-users').val());
	for(i = 0; i < $('#room-owner').length; i++) {
		$('#room-owner option:eq(' + i + ')').prop('selected', false);
	}
}

$(document).ready(function() {
	// disable the delete/submit buttons once we load up
	$('.room .rooms-specific .submit-area .delete').addClass('disabled');
	$('.room .rooms-specific .submit-area .submit').addClass('disabled');

	// set up a listener for the room selector
	$("select[name='rooms-list']").on('change', function() {
      // is there a GUID?
      var guid = $("select[name='rooms-list']").val();
      if(!guid || guid.length == 0) {
         resetRoomForm();
         $('.room .rooms-specific').addClass('hidden', 500);
			$('.room .rooms-specific .submit-area .delete').addClass('disabled');
			$('.room .rooms-specific .submit-area .submit').addClass('disabled');
      }
      else if(guid != 'create-room') {
			// unhide the form and enable the delete button
			$('.room .rooms-specific').removeClass('hidden', 500);
			$('.room .rooms-specific .submit-area .delete').removeClass('disabled');
			$('.room .rooms-specific .submit-area .submit').removeClass('disabled');

         // make an API request for the data
         var parameters = {
            'guid': guid
         }

         // post the object to the server
         $.post('/room-admin/get-info', parameters, function(data) {
            // convert the JSON from the server to an object
            var response = JSON.parse(data);

            // if we had an issue, now is a good time to know
            if(response.response == 'error') {
               alert('Error: ' + response.message);
            }

            // otherwise, we now have what we need to display the room info
            else {
               $('#room-name').val(response.room_name);
               $('#room-uri').val(response.room_uri);
               $('#room-max-users').val(function() {
                  if(response.max_users > 15) {
                     return 15;
                  }
                  else if(response.max_users < 2) {
                     return 2;
                  }
                  else {
                     return response.max_users;
                  }
               });
               $("#room-owner option[value='" + response.owner_guid + "']").prop('selected', true);
					$('.room .rooms-specific').prop('id', guid);
            }
         });
      }

      // what if we want to create a room?
      else {
			resetRoomForm();
			$('.room .rooms-specific').prop('id', 'create-room').removeClass('hidden');
			$('.room .rooms-specific .submit-area .submit').removeClass('disabled');
			$('.room .rooms-specific .submit-area .delete').addClass('disabled');
		}
   });

	// set up a listener for the Submit button (general/global)
	$('.room .rooms-general .submit-area .submit').on('click', function() {
		// validate the input on each field (though it's optional - we just won't send the ones that don't have values)
		var parameters = {};

		// take the value of the number field
		if($('#room-global-max-users').val()) {
			parameters.room_global_max_users = $('#room-global-max-users').val();
		}

		// these next two are checkboxes, but we need to convert to literal 0 (false) or 1 (true) for the database's sake
		parameters.room_users_can_create = function() {
			if($('#room-users-can-create').prop('checked')) {
				return '1';
			}
			else {
				return '0';
			}
		}
		parameters.room_users_can_own = function() {
			if($('#room-users-can-own').prop('checked')) {
				return '1';
			}
			else {
				return '0';
			}
		}

		$.post('/config-set-values', parameters, function(data) {
			response = JSON.parse(data);
			// if we had an issue, now is a good time to know
			if(response.response == 'error') {
				alert('Error: ' + response.message);
			}

			// operation was sucessful
			else {
				alert("Settings were successfully modified! Reloading the page.");
				window.location = '/admin';
			}
		});
	});

	// set up a listener for the Submit button (room specific)
	$('.room .rooms-specific .submit-area .submit').on('click', function() {
		// check the ID of the room container
		var guid = $('.room .rooms-specific').prop('id');

		// if it doesn't contain any string, reset the room form and re-hide it
		if(!guid || guid.length == 0) {
			resetRoomForm();
			$('.room .rooms-specific').addClass('hidden', 500);
		}

		// all fields must validate or else
		var validated = true;
		var fields = [ '#room-name', '#room-uri', '#room-max-users', '#room-owner' ];
		fields.forEach(function(element) {
			if(!$(element).val()) {
				$(element).css('border', '1px solid #F33');
				validated = false;
			}
		});

		// if everything is still validated, proceed with the API call
		if(validated) {
			var parameters = {
				'room_name': $('#room-name').val(),
				'room_uri': $('#room-uri').val(),
				'max_users': $('#room-max-users').val(),
				'owner_guid': $('#room-owner').val()
			}

			// if it contains a certain string, we're going to create a room
			if(guid == 'create-room') {
				var uri = '/room-admin/create';
			}

			// otherwise, it's most likely a modify request, so that gets a different URI and an additional parameter (the GUID)
			else {
				var uri = '/room-admin/modify';
				parameters.guid = guid;
			}

			// do the POST request
			$.post(uri, parameters, function(data) {
				response = JSON.parse(data);
				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}

				// operation was sucessful
				else {
					alert("Room was successfully added or modified! Reloading the page.");
					window.location = '/admin';
				}
			});
		}
	});

	// there's a delete button, too - might as well use it!
	$('.room .rooms-specific .submit-area .delete').on('click', function() {
		// check the ID of the room container
		var guid = $('.room .rooms-specific').prop('id');

		// if it equals something other than '' or 'create-room', proceed
		if(guid.length > 0 && guid != 'create-room') {
			var parameters = {
				'guid': guid
			}
			$.post('/room-admin/delete', parameters, function(data) {
				response = JSON.parse(data);

				// if we had an issue, now is a good time to know
				if(response.response == 'error') {
					alert('Error: ' + response.message);
				}
				else {
					alert("Room was successfully deleted! Reloading the page.");
					window.location = '/admin';
				}
			});
		}
	});

	// put an event listener on the room title field so that it will latch to whatever is in the title field
	$('#room-name').on('change', function() {
		$('#room-uri').val($(this).val().toLowerCase().replace(/\s/g, '-'));
	});
});
