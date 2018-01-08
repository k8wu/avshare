<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');
?>
<div class="viewport rounded-edges">
	<div class="video">
		<div class="info-text">
			<p>Not playing anything. Add something!</p>
			<p>We support <a href="https://youtube.com" target="_blank">YouTube</a> and <a href="https://spotify.com" target="_blank">Spotify</a> links.</p>
		</div>
		<div class="now-playing">
			<img src="<?php echo $config->get_theme_uri(); ?>/img/sample_video.png" />
		</div>
	</div>
	
	<div class="controls rounded-edges">
		<input class="instructions floated-left" type="text" placeholder="Enter URL here" />
		<button class="button floated-left">
			Submit
		</button>
		
		<div class="clearfix">
		</div>
	</div>
	<div class="whats-next">
		<div class="in-queue">
			<img src="<?php echo $config->get_theme_uri(); ?>/img/sample_video.png" />
		</div>
		<div class="in-queue">
			<img src="<?php echo $config->get_theme_uri(); ?>/img/sample_video.png" />
		</div>
		<div class="in-queue">
			<img src="<?php echo $config->get_theme_uri(); ?>/img/sample_video.png" />
		</div>
	</div>
	<div class="clearfix">
	</div>
</div>
