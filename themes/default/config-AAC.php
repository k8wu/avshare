<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');
?>
<div class="module-settings aac hidden">
	<h1>AAC Settings</h1>
		<div class="user-info">
			<p>Modify or add a user</p>
			<select class="users" name="user">
				<option value="">Select user...</option>
				<?php
				// iterate through the available users
				$user_list = $this->list_users();
				foreach($user_list as $user) { ?>
					<option value="<?php echo $user['guid']; ?>"><?php echo $user['name']; ?></option>
				<?php } ?>
				<option value="new">Add new user...</option>
			</select>
		<div class="clearfix">
		</div>
		<div class="user-container hidden">
			<div class="profile floated-left">
				<div class="config-item">
					<label for="username">Username:</label>
					<input type="text" name="username" id="aac-username" placeholder="(username)"/>
				</div>
		
				<div class="config-item">
					<label for="password">Password:</label>
					<input type="password" name="password" id="aac-password" placeholder="(not shown)" />
				</div>
		
				<div class="config-item">
					<label for="email-address">Email Address:</label>
					<input type="email" name="email-address" id="aac-email-address" placeholder="(email address)" />
				</div>

				<div class="config-item">
					<label for="access-level">Access Level:</label>
					<select name="access-level" id="aac-access-level">
						<?php foreach(array(AAC::ACCESS_LEVEL_INACTIVE => "Inactive", AAC::ACCESS_LEVEL_USER => "User", AAC::ACCESS_LEVEL_ADMIN => "Administrator") as $access_level => $access_level_str) { ?>
							<option value="<?php echo $access_level; ?>">
								<?php echo $access_level_str; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
	
			<div class="info floated-right">
				<div class="info-line">
					Created: <span id="aac-created">2017-00-00 00:00:00</span>
				</div>
				<div class="info-line">
					Last Modified: <span id="aac-modified">2017-12-00 00:00:00</span>
				</div>
				<div class="info-line">
					Last Logged In: <span id="aac-last-login">2017-12-30 00:00:00</span>
				</div>
			</div>
			<div class="submit-area floated-right">
				<button class="button submit">Submit</button>
				<button class="button delete-user">Delete</button>
			</div>
			<div class="clearfix">
			</div>
		</div>
	</div>
</div>
<script src="<?php echo $config->get_theme_uri(); ?>/js/config-AAC.js"></script>

