$(document).ready(function() {
	$('.button').on('mouseover', function() {
		$(this).css('color', 'rgb(0, 0, 0)');
		$(this).css('background-color', 'rgb(255, 255, 255)');
	});

	$('.button').on('mouseout', function() {
		$(this).css('color', 'rgb(255, 255, 255)');
		$(this).css('background-color', 'rgb(0, 0, 0)');
	});

	// state variable for whether a video is playing
	var videoIsPlaying = false;

	$('.viewport .button').on('click', function() {
		if(!videoIsPlaying) {
			$('.video .info-text').css('display', 'none');
			$('.video .now-playing').css('display', 'block');
			videoIsPlaying = true;
		}
		else {
			$('.video .info-text').css('display', 'block');
			$('.video .now-playing').css('display', 'none');
			videoIsPlaying = false;
		}
	});
});