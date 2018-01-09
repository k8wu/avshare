<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

global $config;
?>
<div class="module-settings room hidden">
	<h1>Rooms</h1>
	<h2>Global Settings</h2>
	<div class="settings-area rooms-general">
		<div class="config-item">
			<label for="room-global-max-users">
				<?php echo $config->get_description('room_global_max_users'); ?>:
			</label>
			<input type="number" name="room-global-max-users" id="room-global-max-users" placeholder="<?php echo $config->get_value('room_global_max_users'); ?>" />
		</div>

		<div class="config-item">
			<input type="checkbox" name="room-users-can-create" id="room-users-can-create" <?php echo $config->get_value('room_users_can_create') ? 'checked ' : ''; ?>/>
			<label for="room-users-can-create">
				<?php echo $config->get_description('room_users_can_create'); ?>
			</label>
		</div>

		<div class="config-item">
			<input type="checkbox" name="room-users-can-own" id="room-users-can-own" <?php echo $config->get_value('room_users_can_own') ? 'checked ' : ''; ?>/>
			<label for="room-users-can-own">
				<?php echo $config->get_description('room_users_can_own'); ?>
			</label>
		</div>

		<div class="submit-area">
			<button class="button submit">Submit</button>
		</div>
	</div>
	<h2>Room Settings</h2>
	<div class="settings-area rooms-specific">
		<div class="config-item">
			<select class="rooms-list" name="rooms-list">
				<option value="">Select a room...</option>
				<?php
				$rooms = Room::get_room_list();
				foreach($rooms as $room) { ?>
					<option value="<?php echo $room['guid']; ?>"><?php echo $room['room_name']; ?></option>
				<?php } ?>
				<option value="create-room">Create new room...</option>
			</select>
		</div>

		<div class="room-container">
			<div class="config-item">
				<label for="room-name">Room Name:</label>
				<input type="text" name="room-name" id="room-name" />
			</div>

			<div class="config-item">
				<label for="room-uri">URI: /room/</label>
				<input type="text" name="room-uri" id="room-uri" />
			</div>

			<div class="config-item">
				<label for="room-max-users">Maximum Users:</label>
				<input type="number" name="room-max-users" id="room-max-users" />
			</div>

			<?php
			// get list of users and iterate over them
			$users = AAC::list_users();
			$users_filtered = array();
			foreach($users as $user) {
				// we need some information on the user
				$user_info = AAC::get_user_data($user['guid']);

				// filter out inactive/banned users
				if($user_info['access_level'] != 0 && !$user_info['banned']) {
					// if users can own rooms, just put them in the array
					if($config->get_value('room_users_can_own')) {
						array_push($users_filtered, $user);
					}

					// otherwise, check to make sure that they're at least admins
					else if(!$config->get_value('room_users_can_own') && $user_info['access_level'] >= AAC::ACCESS_LEVEL_ADMIN) {
						array_push($users_filtered, $user);
					}
				}
			} ?>

			<div class="config-item">
				<label for="room-owner">Room Owner</label>
				<select class="room-owner" id="room-owner">
					<option value="">Select a user...</option>
					<?php foreach($users_filtered as $user_filtered) { ?>
						<option value="<?php echo $user_filtered['guid']; ?>"><?php echo $user_filtered['name']; ?></option>
					<?php } ?>
				</select>
			</div>
		</div>

		<div class="submit-area floated-right">
			<button class="button submit">Submit</button>
			<button class="button delete">Delete</button>
		</div>
	</div>
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/config-Room.js"></script>
