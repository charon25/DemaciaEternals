<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');

	$req_users = $bdd->query('SELECT `id`,`last_game`,`account_id`,`region`,`summoner_id_tft` FROM `et_users`');
	while ($user = $req_users->fetch()) {
		$last_game = $user['last_game'];

		$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start) . "match/v4/matchlists/by-account/" . $user['account_id'] . "?api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$matchlist = json_decode($content, TRUE);
			if (count($matchlist['matches']) > 0) {
				$last_game = $matchlist['matches'][0]['timestamp'];
				foreach ($matchlist['matches'] as $key => $match) {
					if ($match['timestamp'] > $user['last_game']) {
						if (in_array($match['queue'], $queues)) {
							$req_existance = $bdd->prepare('SELECT `id` FROM `et_matches` WHERE `match_id`=?');
							$req_existance->execute(array($match['gameId']));
							if ($req_existance->rowCount() == 0) {
								$req_insert = $bdd->prepare('INSERT INTO `et_matches`(`match_id`, `users_id`, `region`) VALUES (?, ?, ?)');
								$req_insert->execute(array($match['gameId'], $user['id'] . '/' . $user['account_id'], $user['region']));
							} else {
								$req_update = $bdd->prepare('UPDATE `et_matches` SET `users_id`=CONCAT(`users_id`, ?) WHERE `match_id`=?');
								$req_update->execute(array('%' . $user['id'] . '/' . $user['account_id'], $match['gameId']));
							}
						} else if (in_array($match['queue'], $aram_queues)) {
							echo "aram<br>";
							$req_existance = $bdd->prepare('SELECT `id` FROM `et_arams_matches` WHERE `match_id`=?');
							$req_existance->execute(array($match['gameId']));
							if ($req_existance->rowCount() == 0) {
								$req_insert = $bdd->prepare('INSERT INTO `et_arams_matches`(`match_id`, `users_id`, `region`) VALUES (?, ?, ?)');
								$req_insert->execute(array($match['gameId'], $user['id'] . '/' . $user['account_id'], $user['region']));
							} else {
								$req_update = $bdd->prepare('UPDATE `et_arams_matches` SET `users_id`=CONCAT(`users_id`, ?) WHERE `match_id`=?');
								$req_update->execute(array('%' . $user['id'] . '/' . $user['account_id'], $match['gameId']));
							}
						}	
					}
				}
				$req_update = $bdd->prepare('UPDATE `et_users` SET `last_game`=? WHERE `id`=?');
				$req_update->execute(array($last_game + 100, $user['id']));
			}
			usleep(22 * 1000);
		} else {
			echo "<p>Erreur : " . $user['id'] . "</p>";
		}

		if (intval(date("H")) == 20) {
			$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start_tft) . "league/v1/entries/by-summoner/" . $user['summoner_id_tft'] . "?api_key=" . $riot_token_tft);
			if (!($content === FALSE)) {
				$tft_league = json_decode($content, TRUE);
				if (count($tft_league) > 0) {
					$tft_league = $tft_league[0];
					$tft_stats_array = array("n" => ($tft_league['wins'] + $tft_league['losses']), "w" => $tft_league['wins'], "r" => ($tft_league['tier'] . '-' . $tft_league['rank'] . '-' . $tft_league['leaguePoints']));
					$tft_stats_string = json_encode($tft_stats_array);
					$req_update = $bdd->prepare('UPDATE `et_users` SET `tft_stats`=? WHERE `id`=?');
					$req_update->execute(array($tft_stats_string, $user['id']));
				}
			} else {
				echo "<p>Erreur (TFT) : " . $user['id'] . "</p>";
			}
			usleep(1.2*1000000+100);
		}
	}

	$req_update = $bdd->query('UPDATE `et_users` SET `tft_stats`="X" WHERE `tft_stats`=""');

	/*$req_users = $bdd->query('SELECT `id`,`main_id`,`last_game`,`account_id`,`region` FROM `et_smurfs`');
	while ($user = $req_users->fetch()) {
		$last_game = $user['last_game'];

		$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start) . "match/v4/matchlists/by-account/" . $user['account_id'] . "?beginTime=" . $user['last_game'] . "&api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$matchlist = json_decode($content, TRUE);
			if (count($matchlist['matches']) > 0) {
				$last_game = $matchlist['matches'][0]['timestamp'];
				foreach ($matchlist['matches'] as $key => $match) {
					if (in_array($match['queue'], $queues)) {
						$req_existance = $bdd->prepare('SELECT `id` FROM `et_matches` WHERE `match_id`=?');
						$req_existance->execute(array($match['gameId']));
						if ($req_existance->rowCount() == 0) {
							$req_insert = $bdd->prepare('INSERT INTO `et_matches`(`match_id`, `users_id`, `region`) VALUES (?, ? , ?)');
							$req_insert->execute(array($match['gameId'], $user['main_id'] . '/' . $user['account_id'], $user['region']));
						} else {
							$req_update = $bdd->prepare('UPDATE `et_matches` SET `users_id`=CONCAT(`users_id`, ?) WHERE `match_id`=?');
							$req_update->execute(array('%' . $user['main_id'] . '/' . $user['account_id'], $match['gameId']));
						}
					}
				}
				$req_update = $bdd->prepare('UPDATE `et_smurfs` SET `last_game`=? WHERE `id`=?');
				$req_update->execute(array($last_game + 1, $user['id']));
			}
			usleep(20 * 1000);
		} else {
			echo "<p>Erreur : " . $user['id'] . "</p>";
		}
	}*/

 ?>