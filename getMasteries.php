<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');

	$req_users = $bdd->query('SELECT `id`, `summoner_id`, `masteries`, `region`, `smurfs` FROM `et_users`');
	$users_array = array();
	while ($user = $req_users->fetch()) {
		$users_array[$user['id']] = array("summoner_id" => $user['summoner_id'], "masteries" => json_decode($user['masteries'], TRUE), "region" => $user['region'], "smurfs" => (strlen($user['smurfs']) == 0 ? array() : explode('_', $user['smurfs'])));
	}

	$req_smurfs = $bdd->query('SELECT `id`, `summoner_id`, `region` FROM `et_smurfs`');
	$smurfs_infos = array();
	while ($smurf = $req_smurfs->fetch()) {
		$smurfs_infos[$smurf['id']] = array("summoner_id" => $smurf['summoner_id'], "region" => $smurf['region']);
	}

	$k = 0;
	foreach ($users_array as $user_id => $user_infos) {
		$masteries = array();
		$summoners_infos = array();
		$summoners_infos[] = array("summoner_id" => $user_infos['summoner_id'], "region" => $user_infos['region']);
		foreach ($user_infos['smurfs'] as $key => $smurf_id) {
			$summoners_infos[] = $smurfs_infos[$smurf_id];
		}

		foreach ($summoners_infos as $key => $summoner_infos) {
			//echo str_replace("%region%", $summoner_infos['region'], $req_start) . "champion-mastery/v4/champion-masteries/by-summoner/" . $summoner_infos['summoner_id'] . "?api_key=" . $riot_token;
			$content = @file_get_contents(str_replace("%region%", $summoner_infos['region'], $req_start) . "champion-mastery/v4/champion-masteries/by-summoner/" . $summoner_infos['summoner_id'] . "?api_key=" . $riot_token);
			if (!($content === FALSE)) {
				$masteries_json = json_decode($content, TRUE);
				foreach ($masteries_json as $key => $champion) {
					$champion_id = $champion['championId'];
					if (!array_key_exists($champion_id, $masteries)) {
						$masteries[$champion_id] = $champion['championPoints'];
					} else {
						$masteries[$champion_id] += $champion['championPoints'];
					}
				}
			} else {
				echo '<p>Erreur : ' . $summoner_infos['summoner_id'] . '<br>' . str_replace("%region%", $summoner_infos['region'], $req_start) . "champion-mastery/v4/champion-masteries/by-summoner/" . $summoner_infos['summoner_id'] . "?api_key=__key__" . '</p>';
				file_put_contents("logs.txt", file_get_contents("logs.txt") . "\n\n" . '<p>Erreur : ' . $summoner_infos['summoner_id'] . '<br>' . str_replace("%region%", $summoner_infos['region'], $req_start) . "champion-mastery/v4/champion-masteries/by-summoner/" . $summoner_infos['summoner_id'] . "?api_key=__key__" . '</p>');
			}
		}

		$req_update = $bdd->prepare('UPDATE `et_users` SET `masteries`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($masteries), $user_id));

		/*foreach ($masteries as $champion_id => $champion_points) {
			if ($user_infos['masteries'] == null || !array_key_exists($champion_id, $user_infos['masteries'])) {
				$user_infos['masteries'][$champion_id] = array("startDate" => time(), "points" => array($champion_points));
			} else {
				$user_infos['masteries'][$champion_id]['points'][] = $champion_points;
			}
		}*/

		/*$content = @file_get_contents(str_replace("%region%", $user_infos['region'], $req_start) . "champion-mastery/v4/champion-masteries/by-summoner/" . $user_infos['summoner_id'] . "?api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$masteries_json = json_decode($content, TRUE);
			foreach ($masteries_json as $key => $champion) {
				$champion_id = $champion['championId'];
				if ($user_infos['masteries'] == null || !array_key_exists($champion_id, $user_infos['masteries'])) {
					$user_infos['masteries'][$champion_id] = array("startDate" => time(), "points" => array($champion['championPoints']));
				} else {
					$user_infos['masteries'][$champion_id]['points'][] = $champion['championPoints'];
				}
			}
		} else {
			echo '<p> Erreur id : ' . $user_id . '</p>';
		}*/

		/*$req_update = $bdd->prepare('UPDATE `et_users` SET `masteries`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($user_infos['masteries']), $user_id));*/
	}

 ?>