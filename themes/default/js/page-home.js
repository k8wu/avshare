$(document).ready(function() {
   // get an image of what's playing for each room
   $('.room-box').each(function(index) {
      var parameters = {
         'room_guid': $(this).prop('id')
      }
      $.post('/media/first-play', parameters, function(data) {
         if(data) {
            response = JSON.parse(data);
            console.log(response);
            if(response.response == 'ok') {
               // change the embed URL to an image one
               $('.now-playing img', '#' + response.room_guid).prop('src', response.image_url);
            }
         }
      });
   });
});
