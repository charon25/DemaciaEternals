<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require('bdd.php');
	require('constants.php');

	$req_users = $bdd->query('SELECT * FROM `et_users` WHERE `stats` != ""');
	$all_stats = array();
	while ($user = $req_users->fetch()) {
		$all_stats[$user['id']] = array();
		foreach (json_decode($user['stats'], TRUE) as $champion_id => $champion_stats) {
			$all_stats[$user['id']][$champion_id] = $champion_stats;
			$all_stats[$user['id']][$champion_id]['w'] = $champion_stats['n'] - $champion_stats['w'];
		}
	}

	foreach ($all_stats as $user_id => $user_stats) {
		$req_update = $bdd->prepare('UPDATE `et_users` SET `stats`=? WHERE `id`=?');
		$req_update->execute(array(json_encode($user_stats), $user_id));
	}

 ?>