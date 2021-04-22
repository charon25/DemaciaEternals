<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');

	$req_stats = $bdd->query('SELECT * FROM `et_categories` ORDER BY `ordering` ASC');
	$stats = array();
	while ($stat = $req_stats->fetch()) {
		$stats[$stat['name']] = $stat['stats_name'];
	}

	$req_items = $bdd->query('SELECT `item_id` FROM `et_items`');
	$items_stats = array();
	while ($item = $req_items->fetch()) {
		$items_stats[$item['item_id']] = array('n' => 0, 'w' => 0);
	}

	$champion_aram_stats = array();
	$req_champions = $bdd->query('SELECT `champion_id`, `aram_stats` FROM `et_champs`');
	while ($champion = $req_champions->fetch()) {
		$champion_aram_stats[$champion['champion_id']] = json_decode($champion['aram_stats'], TRUE);
	}

	$all_stats_array = array();
	$roles_array = array();
	$pentakills_array = array();
	$delete_string = "";

	echo 'Début<br>';

	$req_matches = $bdd->query('SELECT * FROM `et_matches` LIMIT ' . $max_matches_by_request);
	while ($match = $req_matches->fetch()) {
		$content = @file_get_contents(str_replace("%region%", $match['region'], $req_start) . "match/v4/matches/" . $match['match_id'] . "?api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$match_json = json_decode($content, TRUE);
			if ($match_json['gameDuration'] > 250) {
				foreach (explode('%', $match['users_id']) as $key => $user_infos) {
					$user_id = explode('/', $user_infos)[0];
					$accountId = explode('/', $user_infos)[1];
					$req_user = $bdd->prepare('SELECT `account_id`,`stats`,`roles_stats`,`pentakills` FROM `et_users` WHERE `id`=?');
					$req_user->execute(array($user_id));
					$user = $req_user->fetch();
					//$accountId = $user['account_id'];
					$stats_json = json_decode($user['stats'], TRUE);
					if (!array_key_exists($user_id, $all_stats_array)) {
						$all_stats_array[$user_id] = $stats_json;
					}
					$roles_stats_json = json_decode($user['roles_stats'], TRUE);
					if (!array_key_exists($user_id, $roles_array)) {
						$roles_array[$user_id] = $roles_stats_json;
					}
					$pentakills_json = json_decode($user['pentakills'], TRUE);
					if (!array_key_exists($user_id, $pentakills_array)) {
						$pentakills_array[$user_id] = $pentakills_json;
					}
		
					foreach ($match_json['participantIdentities'] as $key => $participant) {
						if ($participant['player']['accountId'] == $accountId) {
							$participantId = $participant['participantId'] - 1;
						}
					}
		
					$participantStats = $match_json['participants'][$participantId]['stats'];
					$championId = $match_json['participants'][$participantId]['championId'];
					if (!array_key_exists($championId, $all_stats_array[$user_id])) {
						$void_stats_array = array();
						foreach ($stats as $name => $stat_name) {
							$void_stats_array[$name] = 0;
						}
						$all_stats_array[$user_id][$championId] = $void_stats_array;
					}
					foreach ($stats as $name => $stat_name) {
						if ($stat_name != '_') {
							$all_stats_array[$user_id][$championId][$name] += $participantStats[$stat_name];
						} else {
							if ($name == 'n') {
								$all_stats_array[$user_id][$championId][$name]++;
							} else if ($name == 'gt') {
								$all_stats_array[$user_id][$championId][$name] += $match_json['gameDuration'];
							} else if ($name == 'kda') {
								$all_stats_array[$user_id][$championId][$name] = 0;
							} else if ($name == 'w') {
								$all_stats_array[$user_id][$championId][$name] += ($participantStats['win'] == 1 ? 1 : 0);
							}
						}
					}

					if ($participantStats['pentaKills'] > 0) {
						for ($i = 0 ; $i < $participantStats['pentaKills'] ; $i++) {
							$arr = array("champion_id" => $championId, "time" => $match_json['gameCreation']);
							switch ($match_json['queueId']) {
								case 400:
									$arr['queue'] = "normal";
									break;
								case 420:
									$arr['queue'] = "soloq";
									break;
								case 430:
									$arr['queue'] = "normal";
									break;
								case 420:
									$arr['queue'] = "flex";
									break;
								case 420:
									$arr['queue'] = "clash";
									break;
								default:
									$arr['queue'] = "unknown";
							}
							$pentakills_array[$user_id][] = $arr;
						}
					}

					$participantTimeline = $match_json['participants'][$participantId]['timeline'];
					switch ($participantTimeline['lane']) {
						case "TOP":
							$role = "Top";
							break;
						case "JUNGLE":
							$role = "Jungle";
							break;
						case "MIDDLE":
							$role = "Mid";
							break;
						case "BOTTOM":
							if ($participantTimeline['role'] == "DUO_CARRY") {
								$role = "ADC";
							} else {
								$role = "Support";
							}
							break;
						default:
							$role = "err";
					}
					if ($role != "err") {
						$roles_array[$user_id][$role]['n']++;
						$roles_array[$user_id][$role]['w'] += ($participantStats['win'] == 1 ? 1 : 0);
					}

					for ($k = 0 ; $k <= 6 ; $k++) {
						$item_id = $participantStats['item' . $k];
						if (array_key_exists($item_id, $items_stats)) {
							$items_stats[$item_id]['n']++;
							$items_stats[$item_id]['w'] += ($participantStats['win'] == 1 ? 1 : 0);
						}
					}
				}

				$winning_team = array();
				$losing_team = array();
				//Données de la game
				foreach ($match_json['participants'] as $key => $participant) {
					if ($participant['stats']['win']) {
						$winning_team[] = $participant['championId'];
					} else {
						$losing_team[] = $participant['championId'];
					}
				}

				$team1 = $match_json['teams'][0];
				$blueside_win = 0;
				if ($team1['teamId'] == 100) {
					if ($team1['win'] == "Win")
						$blueside_win = 1;
					else
						$blueside_win = 0;
				} else {
					if ($team1['win'] == "Win")
						$blueside_win = 0;
					else
						$blueside_win = 1;
				}

				$req_insert_data = $bdd->prepare('INSERT INTO `et_matchs_data`(`winning_team`, `losing_team`, `blueside_win`) VALUES (?, ?, ?)');
				$req_insert_data->execute(array(implode("_", $winning_team), implode("_", $losing_team), $blueside_win));


			}
			
			$delete_string = $delete_string . '`id`=' . $match['id'] . ' OR ';
		}
		usleep(25 * 1000);
	}

	usleep(50 * 1000);

	echo 'Début aram<br>';

	$delete_string_aram = "";
	$all_stats_aram_array = array();

	$req_arams = $bdd->query('SELECT * FROM `et_arams_matches` LIMIT ' . $max_arams_by_request);
	while ($match = $req_arams->fetch()) {
		$content = @file_get_contents(str_replace("%region%", $match['region'], $req_start) . "match/v4/matches/" . $match['match_id'] . "?api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$match_json = json_decode($content, TRUE);
			if ($match_json['gameDuration'] > 250) {
				foreach (explode('%', $match['users_id']) as $key => $user_infos) {
					$user_id = explode('/', $user_infos)[0];
					$accountId = explode('/', $user_infos)[1];
					$req_user = $bdd->prepare('SELECT `account_id`,`stats`,`roles_stats`,`aram_stats`,`pentakills` FROM `et_users` WHERE `id`=?');
					$req_user->execute(array($user_id));
					$user = $req_user->fetch();
					$user_aram_stats = json_decode($user['aram_stats'], TRUE);
					if (!array_key_exists($user_id, $all_stats_aram_array)) {
						$all_stats_aram_array[$user_id] = $user_aram_stats;
					}
					if (is_null($all_stats_aram_array[$user_id])) {
						$all_stats_aram_array[$user_id] = array();
					}
					$pentakills_json = json_decode($user['pentakills'], TRUE);
					if (!array_key_exists($user_id, $pentakills_array)) {
						$pentakills_array[$user_id] = $pentakills_json;
					}

					foreach ($match_json['participantIdentities'] as $key => $participant) {
						if ($participant['player']['accountId'] == $accountId) {
							$participantId = $participant['participantId'] - 1;
						}
					}
		
					$participantStats = $match_json['participants'][$participantId]['stats'];
					$championId = $match_json['participants'][$participantId]['championId'];
					if (!array_key_exists($championId, $all_stats_aram_array[$user_id])) {
						$all_stats_aram_array[$user_id][$championId] = array("n" => 0, "w" => 0);
					}
					$all_stats_aram_array[$user_id][$championId]['n']++;
					$all_stats_aram_array[$user_id][$championId]['w'] += ($participantStats['win'] == 1 ? 1 : 0);

					if ($participantStats['pentaKills'] > 0) {
						for ($i = 0 ; $i < $participantStats['pentaKills'] ; $i++) {
							$pentakills_array[$user_id][] = array("champion_id" => $championId, "time" => $match_json['gameCreation'], "queue" => "aram");
						}
					}
				}
				foreach ($match_json['participants'] as $key => $participant) {
					$champion_aram_stats[$participant['championId']]['n']++;
					$champion_aram_stats[$participant['championId']]['w'] += ($participant['stats']['win'] == 1 ? 1 : 0);
				}
			}
			$delete_string_aram = $delete_string_aram . '`id`=' . $match['id'] . ' OR ';
		}
		usleep(20 * 1000);
	}

	echo 'Fin<br>';

	foreach ($all_stats_array as $user_id => $stats_json) {
		$req_update = $bdd->prepare('UPDATE `et_users` SET `stats`=?, `roles_stats`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($stats_json), json_encode($roles_array[$user_id]), $user_id));
	}
	foreach ($all_stats_aram_array as $user_id => $aram_stats) {
		$req_update = $bdd->prepare('UPDATE `et_users` SET `aram_stats`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($aram_stats), $user_id));
	}
	foreach ($pentakills_array as $user_id => $pentakills_stats) {
		$req_update = $bdd->prepare('UPDATE `et_users` SET `pentakills`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($pentakills_stats), $user_id));
	}

	foreach ($items_stats as $item_id => $item_stats) {
		if ($item_stats['n'] > 0) {
			$req_update = $bdd->prepare('UPDATE `et_items` SET `games_played`=`games_played`+?, `wins`=`wins`+? WHERE `item_id`=?');
			$req_update->execute(array($item_stats['n'], $item_stats['w'], $item_id));
		}
	}

	foreach ($champion_aram_stats as $champ_id => $champ_stats) {
		$req_update = $bdd->prepare('UPDATE `et_champs` SET `aram_stats`=? WHERE `champion_id`=?');
		$req_update->execute(array(json_encode($champ_stats), $champ_id));
	}

	if ($delete_string != "") {
		$req_delete = $bdd->query('DELETE FROM `et_matches` WHERE ' . substr($delete_string, 0, strlen($delete_string) - 4));
	}

	if ($delete_string_aram != "") {
		$req_delete = $bdd->query('DELETE FROM `et_arams_matches` WHERE ' . substr($delete_string_aram, 0, strlen($delete_string_aram) - 4));
	}

 ?>