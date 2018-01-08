<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// pull in the header
global $config;
include $config->get_theme_location() . '/header.php';
?>
		
<div class="body rounded-edges">
	<div class="main rounded-edges">
<?php include $config->get_theme_location() . '/section-media.php'; ?>	
<?php include $config->get_theme_location() . '/section-chat.php'; ?>
	</div>
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/page-room.js" type="text/javascript"></script>

<?php include $config->get_theme_location() . '/footer.php';
