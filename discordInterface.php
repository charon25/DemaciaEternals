<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);

	echo date('Y-m-d');

	$messages = $bot->getMessages($CHANNEL_ID, $MESSAGE_ID);
	foreach ($messages as $key => $message) {
		try {
			if (substr($message['content'], 0, 1) == '!') {
				if (preg_match("/^!([^\/]+)\/[^\/]{3,}(\/non|)$/", $message['content'])) {
					$args = explode('/', substr($message['content'], 1));
					$name = $args[0];
					echo $name . "<br>";
					$summoner_name = str_replace(' ', '', $args[1]);
					$show_ranking = 1;
					if (count($args) == 3 && $args[2] == 'non') {
						$show_ranking = 0;
					}
					$discord_id = $message['author']['id'];
					$dm = $bot->createDM($discord_id);
					$dm_channel_id = $dm['id'];
					$content = @file_get_contents(str_replace("%region%", 'euw1', $req_start) . "summoner/v4/summoners/by-name/" . $summoner_name . "?api_key=" . $riot_token);
					$content_tft = @file_get_contents(str_replace("%region%", 'euw1', $req_start) . "summoner/v4/summoners/by-name/" . $summoner_name . "?api_key=" . $riot_token_tft);
					if ($content === FALSE || $content_tft === FALSE) {
						$bot->sendMessage($dm_channel_id, "Un problème est survenu ! Vérifie que ton pseudo LoL est correct (**" . $summoner_name . "**). Si il est incorrect, renvoie le message, sinon contacte Charon.");
					} else {
						$summoner = json_decode($content, TRUE);
						$summoner_tft = json_decode($content_tft, TRUE);
						$req_test = $bdd->prepare('SELECT `id` FROM `et_users` WHERE `discord_id`=?');
						$req_test->execute(array($discord_id));
						if ($req_test->rowCount() == 0) {
							$req_insert = $bdd->prepare('INSERT INTO `et_users`(`last_game`, `name`, `account_id`, `summoner_id`, `summoner_name`, `discord_id`, `avatar`, `start_date`, `show_ranking`, `stats`, `roles_stats`, `region`, `smurfs`, `puuid`, `tft_stats`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
							$req_insert->execute(array(1000 * (time() - 345600), $name, $summoner['accountId'], $summoner['id'], $summoner_name, $discord_id, $bot->getAvatar($discord_id), date('Y-m-d', time() - 4 * 24 * 3600), $show_ranking, '{}', $default_roles_stats, 'euw1', "", $summoner_tft['puuid'], "X"));
							$bot->sendMessage($dm_channel_id, 'Ton compte (**' . $summoner_name . '**) a bien été enregistré ! Tu peux désormais voir les statistiques de tes futures games à cette addresse : ' . $URL . ' !');
						} else {
							$req_update = $bdd->prepare('UPDATE `et_users` SET `name`=?, `account_id`=?, `summoner_id`=?, `summoner_name`=?, `avatar`=?, `show_ranking`=?, `puuid`=? WHERE `discord_id`=?');
							echo '<br>' . $bot->getAvatar($discord_id);
							$req_update->execute(array($name, $summoner['accountId'], $summoner['id'], $summoner_name, $bot->getAvatar($discord_id), $show_ranking, $discord_id, $summoner_tft['puuid']));
							$bot->sendMessage($dm_channel_id, 'Modification effectuée avec succès, ton compte LoL associé est désormais **' . $summoner_name . '** ! Tes stats précédentes n\'ont pas été supprimées.');
						}
					}
					sleep(1);
					$bot->deleteMessage($CHANNEL_ID, $message['id']);
					sleep(1);
				} else if (preg_match("/^!s:[^:]{3,}$/", $message['content'])) {
					/*$summoner_name = str_replace(' ', '', explode(':', $message['content'])[1]);
					$discord_id = $message['author']['id'];
					$dm = $bot->createDM($discord_id);
					$dm_channel_id = $dm['id'];
					$content = @file_get_contents(str_replace("%region%", 'euw1', $req_start) . "summoner/v4/summoners/by-name/" . $summoner_name . "?api_key=" . $riot_token);
					if ($content === FALSE) {
						$bot->sendMessage($dm_channel_id, "Un problème est survenu ! Vérifie que ton pseudo LoL est correct (**" . $summoner_name . "**). Si il est incorrect, renvoie le message, sinon contacte Charon.");
					} else {
						$req_user = $bdd->prepare('SELECT `id` FROM `et_users` WHERE `discord_id`=?');
						$req_user->execute(array($discord_id));
						if ($req_user->rowCount() == 0) {
							$bot->sendMessage($dm_channel_id, "Ton main n'est pas enregistré sur le site ! Envoie un message de la forme \"!Pseudo/PseudoIg\" dans le channel #bot-a-réaction, attend de recevoir la confirmation du bot, et renvoie ensuite ton smurf.");
						} else {
							$summoner = json_decode($content, TRUE);
							$main_id = $req_user->fetch()['id'];
							$req_test = $bdd->prepare('SELECT `id` FROM `et_smurfs` WHERE `summoner_name`=?');
							$req_test->execute(array($summoner_name));
							if ($req_test->rowCount() > 0) {
								$bot->sendMessage($dm_channel_id, "Ton smurf est déjà enregistré !");
							} else {
								$req_insert = $bdd->prepare('INSERT INTO `et_smurfs`(`main_id`, `last_game`, `account_id`, `summoner_id`, `summoner_name`, `region`) VALUES (?, ?, ?, ?, ?, ?)');
								$req_insert->execute(array($main_id, 1000 * (time() - 345600), $summoner['accountId'], $summoner['id'], $summoner['name'], 'euw1'));
								$bot->sendMessage($dm_channel_id, "Ton smurf **" . $summoner_name . "** a bien été enregistré !");
							}
						}
					}
					sleep(1);
					$bot->deleteMessage($CHANNEL_ID, $message['id']);
					sleep(1);*/
				}
			}
		} catch (Exception $e) {
			file_put_contents("logs.txt", date('[c] ') . $e->getMessage() . "\n\n");
		}
	}

 ?>