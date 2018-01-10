// get new messages from the server
function getNewMessages(since = null) {
   var parameters = {}
   parameters.room_guid = $('.chat').prop('id');

   // a timestamp should be passed to make this work as expected
   if(since) {
      parameters.since = since;
   }

   // make the request and get the data
   $.post('/chat/get-messages', parameters, function(data) {
      if(data) {
         var response = JSON.decode(data);
         if(response.response == 'error') {
            alert('Error: ' + response.message);
         }
         else {
            for(var i = 0; i < response.length; i++) {
               var msg = null;
               msg += '<p>\n';
               msg += '<span class="time">' + response[i].date_time + '</span>\n';
               msg += '<span class="nick">' + response[i].user_name + '</span>\n';
               msg += '<span class="msg">' + response[i].message + '</span>\n';
               $('.chat .messages').val($('.chat .messages').val() + msg);
            }
         }
      }
   });
}

$(document).ready(function() {
   // fire the function every few seconds to check for new messages
   var msgCheck = setInterval(getNewMessages, 2000);
});
