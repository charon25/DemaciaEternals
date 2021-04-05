<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);

	$req_users = $bdd->query('SELECT `name`, `discord_id`, `avatar` FROM `et_users`');
	while ($user = $req_users->fetch()) {
		try {
			$size = getimagesize($bot->getAvatarURL($user['discord_id'], $user['avatar']));
			if (!$size) {
				$req_update = $bdd->prepare('UPDATE `et_users` SET `avatar`=? WHERE `discord_id`=?');
				$req_update->execute(array($bot->getAvatar($user['discord_id']), $user['discord_id']));
			}
		} catch (Exception $e) {
			$req_update = $bdd->prepare('UPDATE `et_users` SET `avatar`=? WHERE `discord_id`=?');
			$req_update->execute(array($bot->getAvatar($user['discord_id']), $user['discord_id']));
		}
		/*if (!@getimagesize($bot->getAvatarURL($user['discord_id'], $user['avatar']))) {
			$req_update = $bdd->prepare('UPDATE `et_users` SET `avatar`=? WHERE `discord_id`=?');
			$req_update->execute(array($bot->getAvatar($user['discord_id']), $user['discord_id']));
		}*/
	}

 ?>