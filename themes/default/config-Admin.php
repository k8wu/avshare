<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

global $config;
?>
<div class="module-settings admin hidden">
	<h1>Administrative Settings</h1>
	<div class="settings-area">
		<div class="config-item">
			<div class="label">
				<label for="admin-site-title"><?php echo $config->get_description('title'); ?>:</label>
			</div>
			<div class="input">
				<input type="text" name="admin-site-title" id="admin-site-title" placeholder="<?php echo $config->get_value('title'); ?>" />
			</div>
		</div>
		<div class="config-item">
			<div class="label">
				<label for="admin-site-subtitle"><?php echo $config->get_description('subtitle'); ?>:</title>
			</div>
			<div class="input">
				<input type="text" name="admin-site-subtitle" id="admin-site-subtitle" placeholder="<?php echo $config->get_value('subtitle'); ?>"/>
			</div>
		</div>
		<div class="config-item">
			<div class="label">
				<label for="admin-active-theme"><?php echo $config->get_description('active_theme'); ?>:</title>
			</div>
			<div class="input">
				<select name="admin-active-theme" id="admin-active-theme">
					<?php
					foreach($config->get_all_themes() as $theme) { ?>
						<option id="<?php echo $theme; ?>"<?php echo $config->get_value('active_theme') == $theme ? ' selected' : ''; ?>><?php echo $theme; ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="submit-area">
			<button class="button submit">Submit</button>
		</div>
	</div>
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/config-Admin.js"></script>
