$(document).ready(function() {
	$('.button').on('mouseover', function() {
		$(this).css('color', 'black');
		$(this).css('background-color', 'white');
	});

	$('.button').on('mouseout', function() {
		$(this).css('color', 'white');
		$(this).css('background-color', 'black');
	});

	$('.admin-panel').on('click', function() {
		window.location = '/admin';
	});

	$('.logout').on('click', function() {
		$.post('/user/logout', function(data) {
			var response = JSON.parse(data);

			// if we had an issue, now is a good time to know
			if(response.response == 'error') {
				alert('Error: ' + response.message);
			}

			// redirect
			else {
				window.location = response.location;
			}
		});
	});
});
