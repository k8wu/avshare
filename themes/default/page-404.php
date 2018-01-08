<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// pull in the header
global $config;
include $config->get_theme_location() . '/header.php';
?>

<div class="body rounded-edges">
	<div class="main rounded-edges">
		<h1>Resource Not Found</h1>
	</div>
</div>

<?php include $config->get_theme_location() . '/footer.php';
