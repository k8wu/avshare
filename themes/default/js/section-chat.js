// global variables
window.last_id = 0;
window.messagesDivHeight = $('.chat .messages').height();

// this is needed later
Array.prototype.removeDuplicates = function() {
    return this.filter(function(item, index, self) {
        return self.indexOf(item) == index;
    });
};

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
               switch(response[i].action) {
                  case 'message':
                     messages += '<p>\n';
                     messages += '<span class="time">' + response[i].date_time + '</span>\n';
                     messages += '<span class="nick"><i class="fa fa-user" aria-hidden="true"></i> ' + response[i].user_name + '</span>\n';
                     messages += '<span class="msg">' + response[i].message + '</span>\n';
                     messages += '</p>\n';
                     break;

                  case 'userjoin':
                     // refill the active users
                     fillActiveUsers();

                     // do this next part regardless
                     messages += '<p>\n';
                     messages += '<span class="time">' + response[i].date_time + '</span>\n';
                     messages += '<span class="msg"><i class="fa fa-user" aria-hidden="true"></i> ' + response[i].user_name + ' has joined the room</span>\n';
                     messages += '</p>\n';

                     break;

                  case 'userpart':
                     $('#user-' + response[i].user_name).remove();
                     messages += '<p>\n';
                     messages += '<span class="time">' + response[i].date_time + '</span>\n';
                     messages += '<span class="msg"><i class="fa fa-user" aria-hidden="true"></i> ' + response[i].user_name + ' has left the room</span>\n';
                     messages += '</p>';
                     break;

                  case 'action':
                     messages += '<p>\n';
                     messages += '<span class="time">' + response[i].date_time + '</span>\n';
                     messages += '<span class="msg">* <i class="fa fa-user" aria-hidden="true"></i> ' + response[i].user_name + ' ' + response[i].message + '</span>\n';
                     messages += '</p>';
                     break;

                  default:
                     break;
               }
               window.last_id = response[i].id;
            }

            // append the messages to the chat window while scrolling down
            $('.chat .messages').append(messages).animate({
               scrollTop: window.messagesDivHeight
            }, 250);
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
            else if(response.response == 'ok') {
               $('.chat .text-input .chat-msg').val('');
               getMessages();
               clearInterval(window.msgCheck);
               window.msgCheck = setInterval(function() {
                  getMessages();
               }, 2000);
            }
         }
      });
   }
}

function fillActiveUsers() {
   var parameters = {
      'room_guid': $('.chat').prop('id')
   }
   $.post('/chat/get-users', parameters, function(userdata) {
      var userlist = JSON.parse(userdata);
      var users = '';
      for(var i = 0; i < userlist.length; i++) {
         users += '<p class="nick" id="user-' + userlist[i].user_name + '"><i class="fa fa-user" aria-hidden="true"></i> ' + userlist[i].user_name + '</p>\n';
      }
      $('.chat .active-users').html(users);
   });
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

   // get the initial user list
   fillActiveUsers();

   // get the messages loaded
   getMessages();

   // check messages every 2 seconds
   window.msgCheck = setInterval(function() {
      getMessages();
   }, 2000);
});
