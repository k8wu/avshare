// state variable for whether a video is playing
var mediaIsPlaying = false;

// polling function (front end side)
function pollForNextMedia() {
	var parameters = {
		'room_guid': $('.viewport').prop('id'),
		'media_playing': mediaIsPlaying
	}
	$.post('/media/poll', parameters, function(data) {
		if(data) {
			var response = JSON.parse(data);
			if(response.response == 'error') {
				console.log(response.message);
			}
			else if(response.response == 'ok'){
				// media is ready to play - embed the video ASAP
				var media_player = $('.viewport .video .now-playing');
				media_player.removeClass('hidden').html('<iframe width="' + media_player.width() + '" height="' + media_player.height() + '" src = "' + response.media_url + '" />');
			}
		}
	});
}

$(document).ready(function() {
	$('.viewport .controls .submit').on('click', function() {
		var parameters = {
			'media_url': $('.viewport .controls .media-url').val(),
			'room_guid': $('.viewport').prop('id')
		}
		$.post('/media/queue-add', parameters, function(data) {
			if(data) {
				var response = JSON.parse(data);
				if(response.response != 'ok') {
					$('.viewport .controls .status').text('Error: ' + response.message).removeClass('hidden');
					$('.viewport .controls .media-url').css('border', '1px solid #F33');
				}
				else {
					var queue_object = '<div class="in-queue" id="' + response.media_url + '">\n';
					queue_object += '<img src="' + response.image_url + '" />\n';
					queue_object += '</div>\n';
					$('.viewport .whats-next').append(queue_object);
					$('.viewport .controls .media-url').css('border', '1px solid black').val('');
					$('.viewport .controls .status').text('').addClass('hidden');
				}
			}
		});
		if(!mediaIsPlaying) {
			// TODO - anything that needs to be done when the video is not playing
		}
		else {
			// TODO - anything that needs to be done when the video is playing
		}
	});
});
