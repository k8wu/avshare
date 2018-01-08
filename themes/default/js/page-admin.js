// common functionality for the module buttons
function selectOptions(module) {
	// hide the initial description and all module pages
	$('.right-side p').addClass('hidden');
	$('.module-settings').addClass('hidden');
	
	// show the requested module
	$('.' + module).removeClass('hidden');
}

$(document).ready(function() {
	$('.admin-area .left-side .button').on('click', function() {
		selectOptions($(this).prop('id').toLowerCase());
	});
});