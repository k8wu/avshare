<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// pull in the header
global $config;
include $config->get_theme_location() . '/header.php';
?>

<div class="body rounded-edges">
	<div class="main home-page rounded-edges">
		<?php
		// get information on all rooms
		require_once $config->app_base_dir . '/inc/Room.class.php';
		$rooms = Room::get_room_list();
		if(!isset($rooms) || !is_array($rooms)) { ?><h2>No rooms defined</h2><?php }
		else {
			foreach($rooms as $room) {
				?><a href="/room/<?php echo $room['room_uri']; ?>">
					<div class="room-box">
						<div class="name"><?php echo $room['room_name']; ?></div>
						<div class="users">
							<i class="fa fa-user" aria-hidden="true"></i>
							<span class="current-users"><?php echo Room::get_users($room['guid'], true); ?></span> / <span class="max-users"><?php echo Room::get_max_users($room['guid']); ?></span>
						</div>
						<div class="now-playing">
						</div>
					</div>
				</a><?php
			}
		} ?>
	</div>
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/page-home.js" type="text/javascript"></script>

<?php include $config->get_theme_location() . '/footer.php';
