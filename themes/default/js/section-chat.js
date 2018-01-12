// global variables
window.last_id = 0;
window.messagesDivHeight = $('.chat .messages').height();

// get new messages from the server
function getMessages() {
   var parameters = {}
   parameters.room_guid = $('.chat').prop('id');
   parameters.last_id = window.last_id;

   // make the request and get the data
   $.post('/chat/get-messages', parameters, function(data) {
      if(data) {
         var response = JSON.parse(data);
         if(response.response == 'error') {
            alert('Error: ' + response.message);
         }
         else if(response.length > 0) {
            var messages = '';
            for(var i = 0; i < response.length; i++) {
               messages += '<p>\n';
               messages += '<span class="time">' + response[i].date_time + '</span>\n';
               messages += '<span class="nick"><i class="fa fa-user" aria-hidden="true"></i> ' + response[i].user_name + '</span>\n';
               messages += '<span class="msg">' + response[i].message + '</span>\n';
               messages += '</p>\n\n';
               window.last_id = response[i].id;
            }

            // append the messages to the chat window while scrolling down
            $('.chat .messages').append(messages).animate({ scrollTop: window.messagesDivHeight }, 250);
         }
      }
   });
}

// send messages to the server
function sendMessage(message) {
   var parameters = {}
   parameters.room_guid = $('.chat').prop('id');

   // get the data and send the message if there exists anything there
   if(message) {
      parameters.message = message;
      $.post('/chat/send-message', parameters, function(data) {
         if(data) {
            var response = JSON.parse(data);
            if(response.response == 'error') {
               $('.chat .text-input .chat-msg').css('border', '1px solid #F33');
            }
            else if(response.response == 'ok'){
               $('.chat .text-input .chat-msg').val('');
               getMessages();
            }
         }
      });
   }
}

$(document).ready(function() {
   // clear the message area
   $('.chat .messages').empty();

   // set up an event listener for sending a message
   $('.chat .text-input .send').on('click', function() {
      if($('.chat .text-input .chat-msg').val()) {
         sendMessage($('.chat .text-input .chat-msg').val());
      }
   });

   // the Enter button will trigger the message send event as well
   $('.chat .text-input .chat-msg').keypress(function (e) {
      if(e.which == 13) {
         $('.chat .text-input .send').trigger('click');
         return false;
      }
   });

   // get the messages loaded
   var initMsgCheck = setTimeout(function() {
      getMessages();

      // fire the function every few seconds to check for new messages
      var msgCheck = setInterval(getMessages, 2000);
   }, 100);
});
