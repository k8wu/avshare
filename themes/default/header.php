<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// the config instance is required
global $config;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo $config->get_title(); ?></title>
		<link rel="stylesheet" href="<?php echo $config->get_theme_uri(); ?>/css/main.css">
		<?php if((isset($this->action)) && $this->action == 'admin') { ?>
			<link rel="stylesheet" href="<?php echo $config->get_theme_uri(); ?>/css/admin.css">
		<?php } ?>
		<script src="<?php echo $config->get_theme_uri(); ?>/js/jquery-3.2.1.min.js"></script>
		<script src="<?php echo $config->get_theme_uri(); ?>/js/fontawesome-all.js"></script>
		<script src="<?php echo $config->get_theme_uri(); ?>/js/common.js"></script>
	</head>
	<body>
		<div class="header rounded-edges">
			<div class="title-and-subtitle">
				<h1 class="title"><?php echo $config->get_title(); ?></h1>
				<h2 class="subtitle"><?php echo $config->get_subtitle(); ?></h2>
			</div>
			<?php if(isset($_SESSION['user_object'])) { ?>
				<div class="user-info" id="<?php echo $_SESSION['user_object']->get_guid(); ?>">
					<p><i class="fa fa-user" aria-hidden="true"></i> <span class="user-name"><?php echo $_SESSION['user_object']->get_username(); ?></span></p>
					<?php if($_SESSION['user_object']->is_admin()) { ?>
						<button class="button admin-panel">Admin</button>
					<?php } ?>
					<button class="button home">Home</button>
					<button class="button logout">Logout</button>
				</div>
			<?php } ?>
		</div>
