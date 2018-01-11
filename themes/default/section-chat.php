<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');
?>
<div class="chat rounded-edges" id="<?php echo $this->guid; ?>">
	<div class="messages floated-left">
	</div>

	<div class="active-users floated-left">
		<p class="nick">Delusion</p>
		<p class="nick">jrwren</p>
		<p class="nick">plstate</p>
		<p class="nick">someRandoWithALongNick</p>
		<p class="nick">thegleek</p>
		<p class="nick">Tiki</p>
		<p class="nick">z0ylent</p>
	</div>
	<div class="clearfix">
	</div>

	<div class="text-input">
		<input class="chat-msg floated-left" name="chat-msg" placeholder="Write something..."></textarea>
		<button class="button send floated-left">
			Send
		</button>
		<div class="clearfix">
		</div>
	</div>
</div>
<div class="clearfix">
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/section-chat.js" type="text/javascript"></script>
