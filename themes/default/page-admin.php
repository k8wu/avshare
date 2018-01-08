<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// pull in the header
global $config;
include $config->app_base_dir . $config->get_theme_uri() . '/header.php';
?>

<div class="page-title rounded-edges">
	<h3><?php echo $config->get_module_description(get_called_class()); ?></h3>
</div>

<div class="admin-area rounded-edges floated-left">
	<div class="left-side floated-left rounded-edges">
		<div class="module-list">
			<h1>Modules</h1>
			<?php
			$modules = $config->get_module_list();
			foreach($modules as $module) { ?>
				<button class="button" id="<?php echo $module['name']; ?>">
					<?php echo $module['descr']; ?>
				</button>
			<?php } ?>
		</div>
	</div>
	<div class="right-side floated-right rounded-edges">
		<?php
		foreach($modules as $module) {
			if(!class_exists($module['name'])) {
				include $config->app_base_dir . '/inc/' . $module['name'] . '.class.php';
			}

			// not processing any actions - just need the panel data
			$inst = new $module['name'](null, null, null);
			echo $inst->get_admin_panel_data();
		}
		?>
		<p>Select a module from the menu on the left to see its options.</p>
	</div>
</div>
<div class="clearfix">
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/page-admin.js"></script>

<?php
include $config->app_base_dir . $config->get_theme_uri() . '/footer.php';
