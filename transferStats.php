<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require('bdd.php');
	require('constants.php');

	$req_users = $bdd->query('SELECT `id`, `stats`, `masteries` FROM `et_users` WHERE `show_ranking`=1');
	$champs_stats_array = array();
	$masteries_array = array();
	$total_stats = array();
	while ($user = $req_users->fetch()) {
		$statsJson = json_decode($user['stats'], TRUE);
		$masteriesJson = json_decode($user['masteries'], TRUE);
		$total_stats[$user['id']] = array();
		foreach ($statsJson as $champion_id => $championStats) {
			if (!array_key_exists($champion_id, $champs_stats_array)) {
				$champs_stats_array[$champion_id] = array();
			}

			$champs_stats_array[$champion_id][$user['id']] = $championStats;

			foreach ($championStats as $stat_name => $stat_value) {
				if (!array_key_exists($stat_name, $total_stats[$user['id']])) {
					$total_stats[$user['id']][$stat_name] = $stat_value;
				} else {
					$total_stats[$user['id']][$stat_name] += $stat_value;
				}
			}
		}

		$mastery_sum = 0;
		foreach ($masteriesJson as $champion_id => $champion_mastery_point) {
			if (!array_key_exists($champion_id, $masteries_array)) {
				$masteries_array[$champion_id] = array();
			}
			$masteries_array[$champion_id][$user['id']] = $champion_mastery_point;
			$mastery_sum += $champion_mastery_point;
		}
		$total_stats[$user['id']]['mastery'] = $mastery_sum;
	}

	$smurfs = array();
	$req_smurfs = $bdd->query('SELECT `id`, `main_id` FROM `et_smurfs`');
	while ($smurf = $req_smurfs->fetch()) {
		$smurfs[$smurf['main_id']] = array();
		$smurfs[$smurf['main_id']][] = $smurf['id'];
	}

	if (count($champs_stats_array) == 0) {
		$req_update = $bdd->query('UPDATE `et_champs` SET `stats`="{}"');
	} else {
		foreach ($masteries_array as $champion_id => $champion_mastery) {
			if (array_key_exists($champion_id, $champs_stats_array)) {
				$req_update = $bdd->prepare('UPDATE `et_champs` SET `stats`=?, `masteries`=? WHERE `champion_id`=?');
				$req_update->execute(array(json_encode($champs_stats_array[$champion_id]), json_encode($champion_mastery), $champion_id));
			} else {
				$req_update = $bdd->prepare('UPDATE `et_champs` SET `masteries`=? WHERE `champion_id`=?');
				$req_update->execute(array(json_encode($champion_mastery), $champion_id));
			}
		}
		/*foreach ($champs_stats_array as $champion_id => $championStats) {
			$req_update = $bdd->prepare('UPDATE `et_champs` SET `stats`=?, `masteries`=? WHERE `champion_id`=?');
			$req_update->execute(array(json_encode($championStats), json_encode($masteries_array[$champion_id]), $champion_id));
		}*/
	}

	foreach ($total_stats as $user_id => $user_stats) {
		$req_update = $bdd->prepare('UPDATE `et_users` SET `total_stats`=?, `smurfs`=? WHERE `id`=?');
		if (!array_key_exists($user_id, $smurfs) || count($smurfs[$user_id]) == 0) {
			$smurf = "";
		} else {
			$smurf = implode('_', $smurfs[$user_id]);
		}
		$req_update->execute(array(json_encode($user_stats), $smurf, $user_id));
	}

	//MAISONS
	$req_users = $bdd->query('SELECT `id`, `maison` FROM `et_users`');
	$maisons_stats = array();
	while ($user = $req_users->fetch()) {
		$maison = $user['maison'];
		echo $maison . '<br>';
		if ($maison != "") {
			if (!array_key_exists($maison, $maisons_stats)) {
				$maisons_stats[$maison] = array();
			}
			$id = $user['id'];
			foreach ($total_stats[$id] as $stat_name => $stat_value) {
				if (!array_key_exists($stat_name, $maisons_stats[$maison])) {
					$maisons_stats[$maison][$stat_name] = 0;
				}
				$maisons_stats[$maison][$stat_name] += $stat_value;
			}
		}
	}
	print_r($maisons_stats);
	foreach ($maisons_stats as $maison => $stats) {
		$req_update = $bdd->prepare('UPDATE `et_maisons` SET `stats`=? WHERE `name`=?');
		$req_update->execute(array(json_encode($stats), $maison));
	}

 ?>