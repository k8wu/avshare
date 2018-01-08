<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// pull in the header
global $config;
include $config->app_base_dir . $config->get_theme_uri() . '/header.php';
?>

<div class="login-area rounded-edges">
	<div class="login-form">
		<h3 class="login-banner">Login</h3>
		<label for="username" class="floated-left">User Name:</label>
		<input id="username" name="username" type="text" class="floated-right" />
		<div class="clearfix">
		</div>
		<label for="password" class="floated-left">Password:</label>
		<input id="password" name="password" type="password" class="floated-right" />
		<div class="clearfix">
		</div>
		<button class="button floated-left" type="submit">
			Submit
		</button>
		<div class="clearfix">
		</div>
		<div class="status">
		</div>
	</div>				
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/page-login.js"></script>

<?php
include $config->app_base_dir . $config->get_theme_uri() . '/footer.php';
