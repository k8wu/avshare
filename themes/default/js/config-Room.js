function resetRoomForm() {
	// restore the form to sensible defaults
	$('.room .rooms-specific .room-container').prop('id', '');
	$('#room-title').val('').prop('placeholder', 'New title');
	$('#room-url').val('').prop('placeholder', 'new_url');
   $('#room-max-users').val('').prop('placeholder', $('#room-global-max-users').val());
	for(i = 0; i < $('#room-owner').length; i++) {
		$('#room-owner option:eq(' + i + ')').prop('selected', false);
	}
}

$(document).ready(function() {
	// set up a listener for the room selector
	$("select[name='rooms-list']").on('change', function() {
      // is there a GUID?
      var guid = $("select[name='rooms-list']").val();
      if(!guid || guid.length == 0) {
         resetAACForm();
         $('.aac .user-container').addClass('hidden', 500);
      }
      else if(guid != 'new') {
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
               $('#room-title').val(response.name);
               $('#room-url').val(response.uri);
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
            }
         });
      }
   });
});
