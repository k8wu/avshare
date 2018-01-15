// state variable for whether a video is playing
var videoIsPlaying = false;

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
					$('.viewport .controls .status').val('Error: ' + response.message).removeClass('hidden');
				}
				else {
					var queue_object = '<div class="in-queue" id="' + response.media_url + '">\n';
					queue_object += '<img src="' + response.image_url + '" />\n';
					queue_object += '</div>\n';
					$('.viewport .whats-next').append(queue_object);
				}
			}
		});
		if(!videoIsPlaying) {
			// TODO - anything that needs to be done when the video is not playing
		}
		else {
			// TODO - anything that needs to be done when the video is playing
		}
	});
});
