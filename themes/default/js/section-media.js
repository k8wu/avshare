// start the YouTube player
function startYouTubePlayer(player_id) {
	var player;
	// the YouTube API is ready
	player = new YT.Player(player_id, {
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
	});

	function onPlayerReady(event) {
		console.log('the player is ready, playing the media')
		event.target.playVideo();
	}

	function onPlayerStateChange(event) {
		console.log('player state = ' + event.target.getPlayerState());
	}
}

// polling function (front end side)
function pollForNextMedia() {
	var parameters = {
		'room_guid': $('.viewport').prop('id')
	}
	$.post('/media/poll', parameters, function(data) {
		if(data) {
			var response = JSON.parse(data);
			if(response.response == 'ok') {
				// media is ready to play - embed the video ASAP
				$('.viewport .video .info-text').addClass('hidden');
				var media_player = $('.viewport .video .now-playing');
				media_player.removeClass('hidden').html('<iframe id="youtube-player" width="' + media_player.width() + '" height="' + media_player.height() + '" src = "' + response.media_url + '" />');
				startYouTubePlayer('youtube-player');

				// take the item off the queue if it exists
				if($('#' + response.media_url)) {
					$('#' + response.media_url).remove();
				}
				clearInterval(window.poller);
				window.poller = setInterval(function() {
					pollForNextMedia();
				}, 5000);
			}
		}
	});
}

function parseExistingQueue() {
	var parameters = {
		'room_guid': $('.viewport').prop('id')
	}
	$.post('/media/queue-get', parameters, function(data) {
		if(data) {
			var response = JSON.parse(data);
			if(!response.response) {
				for(var i = 0; i < response.length; i++) {
					var queue_object = '<div class="in-queue" id="' + response[i].media_url + '">\n';
					queue_object += '<img src="' + response[i].image_url + '" />\n';
					queue_object += '</div>\n';
					$('.viewport .whats-next').append(queue_object);
				}
			}
		}
	});
}

$(document).ready(function() {
	// get the existing queue
	parseExistingQueue();

	// see if there's anything playing, and play it if there is
	var parameters = {
		'room_guid': $('.viewport').prop('id')
	}
	$.post('/media/first-play', parameters, function(data) {
		if(data) {
			var response = JSON.parse(data);
			if(response.response == 'ok') {
				// media is ready to play - embed the video ASAP
				$('.viewport .video .info-text').addClass('hidden');
				var media_player = $('.viewport .video .now-playing');
				media_player.removeClass('hidden').empty().append('<iframe type="text/html" id="youtube-player" width="' + media_player.width() + '" height="' + media_player.height() + '" src="' + response.media_url + '" />');

				startYouTubePlayer('youtube-player');
			}
		}
	});

	// click handler for the submit button
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
	});

	window.poller = setInterval(function() {
		pollForNextMedia()
	}, 5000);
});
