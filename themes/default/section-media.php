<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');
?>
<div class="viewport rounded-edges" id="<?php echo $this->guid; ?>">
	<div class="video">
		<div class="info-text">
			<p>Not playing anything. Add something!</p>
			<p>We support <a href="https://youtube.com" target="_blank">YouTube</a> links.</p>
		</div>

		<div class="now-playing hidden">
		</div>
	</div>

	<div class="controls rounded-edges">
		<input class="media-url" type="text" placeholder="Enter URL here" />
		<button class="button submit">
			Submit
		</button>

		<div class="clearfix">
		</div>

		<div class="status hidden">
		</div>
	</div>

	<div class="whats-next">
	</div>

	<div class="clearfix">
	</div>
</div>
<script src="https://www.youtube.com/iframe_api"></script>
<script src="<?php echo $config->get_theme_uri(); ?>/js/section-media.js" type="text/javascript"></script>
