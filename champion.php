<?php 
	/*ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);*/
	error_reporting(0);
	
	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);
	if (isset($_GET['id'])) {
		$champion_id = $_GET['id'];

		$req_users = $bdd->query('SELECT `id`, `name`, `discord_id`, `avatar`, `total_stats`, `stats` FROM `et_users` WHERE `show_ranking`=1');
		$users_array = array();
		$users_discord_ids_array = array();
		$users_avatars_array = array();
		$users_total_stats = array();
		$user_has_stats = array();
		while ($user = $req_users->fetch()) {
			$users_array[$user['id']] = $user['name'];
			$users_discord_ids_array[$user['id']] = $user['discord_id'];
			$users_avatars_array[$user['id']] = $user['avatar'];
			$users_total_stats[$user['id']] = json_decode($user['total_stats'], TRUE);
			if ($user['stats'] == "{}") {
				$user_has_stats[$user['id']] = FALSE;
			} else {
				$user_has_stats[$user['id']] = TRUE;
			}
		}

		if ($_GET['id'] != "total") {
			$req_champ = $bdd->prepare('SELECT `stats`, `masteries` FROM `et_champs` WHERE `champion_id`=?');
			$req_champ->execute(array($champion_id));
			$champ = $req_champ->fetch();
			$champion_stats = json_decode($champ['stats'], TRUE);
			$champion_masteries = json_decode($champ['masteries'], TRUE);
	
			$req_champ = $bdd->prepare('SELECT `champion_name` FROM `lol_champions` WHERE `champion_id`=?');
			$req_champ->execute(array($champion_id));
			$champion_name = $req_champ->fetch()['champion_name'];
		} else {
			$champion_stats = array();
			foreach ($users_total_stats as $user_id => $user_stats) {
				$champion_stats[$user_id] = $user_stats;
			}

			$champion_name = 'Total';
		}

		$req_stats = $bdd->query('SELECT `name`, `reducing`, `reduced_name`, `rank_order` FROM `et_categories` ORDER BY `ordering` ASC');
		$all_stats = array();
		while ($stat = $req_stats->fetch()) {
			$all_stats[$stat['name']] = array("reducing" => $stat['reducing'], "reduced_name" => $stat['reduced_name'], "rank_order" => $stat['rank_order'], "existing" => TRUE);
		}
		$all_stats['t'] = array("reducing" => "o", "reduced_name" => "Temps total de jeu", "rank_order" => 0, "existing" => FALSE);

		$stats_array = array();
		foreach ($all_stats as $stat_name => $stat_infos) {
			if ($stat_infos['existing']) {
				$stats_array[$stat_name] = array();
				foreach ($champion_stats as $user_id => $user_stats) {
					if ($user_has_stats[$user_id]) {
						if ($stat_infos['reducing'] == 'g') {
						if ($stat_name == 'gt') {
							$value = $user_stats[$stat_name] / (60 * $user_stats['n']);
						} else if ($stat_name == 'w') {
							$value = 100 * $user_stats[$stat_name] / $user_stats['n'];
						} else {
							try {
								/*if ($user_stats['n'] == 0) {
									echo '<p>' . $user_id . '<br>';
									print_r($user_stats);
									echo '</p>';
								}*/
								$value = $user_stats[$stat_name] / $user_stats['n'];
							} catch (Exception $e) {
								echo "ID:" . $user_id . "/";
							}
						}
						} else if ($stat_infos['reducing'] == 'm') {
							$value = 60 * $user_stats[$stat_name] / $user_stats['gt'];
						} else {
							if ($stat_name == 'kda') {
								$value = ($user_stats['d'] == 0 ? $user_stats['k'] + $user_stats['a'] : ($user_stats['k'] + $user_stats['a']) / $user_stats['d']);
							} else {
								$value = $user_stats[$stat_name];
							}
						}
						if ($user_stats['n'] >= 5) {
							$stats_array[$stat_name][$user_id] = $value;
						}
					}
					
				}
				if ($stat_infos['rank_order'] == 0) {
					arsort($stats_array[$stat_name]);
				} else {
					asort($stats_array[$stat_name]);
				}	
			}
		}
		$stats_array['t'] = array();
		foreach ($champion_stats as $user_id => $user_stats) {
			if ($user_has_stats[$user_id]) {
				$stats_array['t'][$user_id] = $user_stats['gt'] / 3600;
			}
		}
		arsort($stats_array['t']);

		if ($_GET['id'] != "total") {
			$masteries_array = array();
			foreach ($champion_masteries as $user_id => $user_points) {
				$masteries_array[$user_id] = $user_points;
			}
			arsort($masteries_array);

			$rounding = 1;
		} else {
			$masteries_array = array();
			foreach ($users_total_stats as $user_id => $user_stats) {
				$masteries_array[$user_id] = $user_stats['mastery'];
			}

			arsort($masteries_array);
			$rounding = 2;
		}
	}

	$show_all_stats = TRUE;
	if (isset($_GET['stats']) && array_key_exists($_GET['stats'], $stats_array)) {
		$show_all_stats = FALSE;
		$show_mastery = FALSE;
	}
	if (isset($_GET['stats']) && $_GET['stats'] == "mastery") {
		$show_all_stats = FALSE;
		$show_mastery = TRUE;
	}

 ?>

<!DOCTYPE html>
<html lang="fr">

<head>
	<title>Éternels de Demacia - 
		<?php 
			if (isset($champion_name)) {
				echo $champion_name;
			} else {
				echo 'Erreur';
			}
		 ?>
	</title>
	<link rel="icon" href="icon.png">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		table {
			margin-left: 15%;
			border-spacing: 0;
			width: 70%;
			border: 1px solid #ddd;
		}

		th {
			text-align: left;
			padding: 16px;
		}

		td {
			text-align: left;
			padding: 16px;
		}

		tr:nth-child(even) {
			background-color: #f2f2f2;
		}

		.text-center {
			text-align: center;
		}

		a {
			text-decoration: none;
		}

		.button {
        	appearance: button;
        	-moz-appearance: button;
        	-webkit-appearance: button;
        	text-decoration: none; 
        	font: menu; 
        	color: ButtonText;
        	display: inline-block; 
        	padding: 2px 8px;
        	cursor: pointer;
        	border: 1px solid black;
        	background: #eee;
    	}

    	@media(max-width:700px) {
    		table {
				margin-left: 0%;
				border-spacing: 0;
				width: 95%;
				border: 1px solid #ddd;
			}
    	}
	</style>
</head>

<body style="text-align: center;">
	<h1 style="vertical-align: middle;">Classement des Demaciens<?php if (isset($champion_id)) {echo " sur $champion_name";} ?></h1>
	<p style="vertical-align: middle;">Ne sont pris en compte que les Demaciens ayant joué au moins 5 fois le champion.</p>
	<a class="button" href="index.php?mode=champions<?php if (isset($champion_id)) {echo "#$champion_id";} ?>">← Revenir à la liste</a>
	<?php if (!$show_all_stats) { ?><a class="button" href="champion.php?id=<?php if (isset($champion_id)) {echo "$champion_id";} ?>">← Revenir au classement de <?php if (isset($champion_id)) {echo $champion_name;} ?></a><?php } ?>
	<?php 
		if (isset($champion_id)) {
			if ($show_all_stats) {
				echo '<h3>Classement : Points de maîtrise  <span style="font-style: italic; font-size: 13px"><a href="champion.php?id=' . $champion_id . '&stats=mastery">(voir tout le classement)</a></span></h3>';
				echo "<table><tr>";
				for ($i = 0 ; $i < count($order) ; $i++) {
					echo '<th class="text-center">' . $order[$i] . '</th>';
				}
				echo '</tr>';
				$users_ids = array_keys($masteries_array);
				$tr = '<tr style="vertical-align: middle">';
				for ($i = 0 ; $i < min(count($users_ids), count($order)) ; $i++) {
					$tr = $tr . '<td width="20%" class="text-center"><a href="demacien.php?id=' . $users_ids[$i] . '&mode=all"><img style="vertical-align: middle;" width="30" height="30" src="' . $bot->createAvatarURL($users_discord_ids_array[$users_ids[$i]], $users_avatars_array[$users_ids[$i]]) . '" alt="' . $users_array[$users_ids[$i]] . '_pp">  ' . $users_array[$users_ids[$i]] . '</a> (' . format_number($masteries_array[$users_ids[$i]]) . ')</td>';
				}
				for ($i = min(count($users_ids), count($order)) ; $i < count($order) ; $i++) {
					$tr = $tr . '<td width="20%" class="text-center">---</td>';
				}
				echo $tr . '</tr></table>';

				foreach ($all_stats as $stat_name => $stat_infos) {
					echo '<h3>Classement : ' . $stat_infos['reduced_name'] . '  <span style="font-style: italic; font-size: 13px"><a href="champion.php?id=' . $champion_id . '&stats=' . $stat_name . '">(voir tout le classement)</a></span></h3>';
					echo '<table><tr>';
					for ($i = 0 ; $i < count($order) ; $i++) {
						echo '<th class="text-center">' . $order[$i] . '</th>';
					}
					echo '</tr>';
					$users_ids = array_keys($stats_array[$stat_name]);
					$tr = '<tr style="vertical-align: middle">';
					for ($i = 0 ; $i < min(count($users_ids), count($order)) ; $i++) {
						if ($stat_name == 'gt') {
							$output_value = number_format($stats_array[$stat_name][$users_ids[$i]], 0) . 'm ' . floor(60 * ($stats_array[$stat_name][$users_ids[$i]] - floor($stats_array[$stat_name][$users_ids[$i]]))) . 's';
						} else if ($stat_name == 'w') {
							$output_value = number_format($stats_array[$stat_name][$users_ids[$i]], 1, ',', ' ') . ' %';
						} else if ($stat_name == 'n') {
							$output_value = number_format($stats_array[$stat_name][$users_ids[$i]], 0, ',', ' ');
						} else if ($stat_name == 't') {
							$output_value = number_format($stats_array[$stat_name][$users_ids[$i]], 0, ',', ' ') . ' h';
						} else {
							$output_value = number_format($stats_array[$stat_name][$users_ids[$i]], $rounding, ',', ' ');
						}
						$tr = $tr . '<td width="20%" class="text-center"><a href="demacien.php?id=' . $users_ids[$i] . '&mode=all"><img style="vertical-align: middle;" width="30" height="30" src="' . $bot->createAvatarURL($users_discord_ids_array[$users_ids[$i]], $users_avatars_array[$users_ids[$i]]) . '" alt="' . $users_array[$users_ids[$i]] . '_pp">  ' . $users_array[$users_ids[$i]] . '</a> (' . $output_value . ')</td>';
					}
					for ($i = min(count($users_ids), count($order)) ; $i < count($order) ; $i++) {
						$tr = $tr . '<td width="20%" class="text-center">---</td>';
					}
					echo $tr . '</tr></table>';
				}
			} else {
				if (!$show_mastery) {
					echo '<h2>Classement : ' . $all_stats[$_GET['stats']]['reduced_name'] . '</h2>';
					echo '<table><tr><th class="text-center">Rang</th><th style="padding-left: 12%;">Demacien</th><th class="text-center">' . $all_stats[$_GET['stats']]['reduced_name'] . '</th></tr>';
					$users_ids = array_keys($stats_array[$stat_name]);
					$k = 1;
					foreach ($stats_array[$_GET['stats']] as $user_id => $value) {
						if ($_GET['stats'] == 'gt') {
							$output_value = number_format($value, 0) . 'm ' . floor(60 * ($value - floor($value))) . 's';
						} else if ($_GET['stats'] == 'w') {
							$output_value = number_format($value, 1, ',', ' ') . ' %';
						} else if ($_GET['stats'] == 'n') {
							$output_value = number_format($value, 0, ',', ' ');
						} else if ($_GET['stats'] == 't') {
							$output_value = number_format($value, 1, ',', ' ') . ' h';
						} else {
							$output_value = number_format($value, $rounding, ',', ' ');
						}
						echo '<tr style="vertical-align: middle">';
						echo '<td width="33%" class="text-center">' . $k . '</td>';
						echo '<td width="34%" style="padding-left: 12%;"><a href="demacien.php?id=' . $user_id . '&mode=all"><img style="vertical-align: middle;" width="30" height="30" src="' . $bot->createAvatarURL($users_discord_ids_array[$user_id], $users_avatars_array[$user_id]) . '" alt="' . $users_array[$user_id] . '_pp">  ' . $users_array[$user_id] .'</a></td>';
						echo '<td width="33%" class="text-center">' . $output_value . '</td></tr>';
						$k++;
					}
				} else {
					echo '<h2>Classement : Points de maîtrises</h2>';
					echo '<table><tr><th class="text-center">Rang</th><th style="padding-left: 12%;">Demacien</th><th class="text-center">Points de maîtrise</th></tr>';
					$users_ids = array_keys($stats_array[$stat_name]);
					$k = 1;
					foreach ($masteries_array as $user_id => $points) {
						echo '<tr style="vertical-align: middle">';
						echo '<td width="33%" class="text-center">' . $k . '</td>';
						echo '<td width="34%" style="padding-left: 12%;"><a href="demacien.php?id=' . $user_id . '&mode=all"><img style="vertical-align: middle;" width="30" height="30" src="' . $bot->createAvatarURL($users_discord_ids_array[$user_id], $users_avatars_array[$user_id]) . '" alt="' . $users_array[$user_id] . '_pp">  ' . $users_array[$user_id] .'</a></td>';
						echo '<td width="33%" class="text-center">' . number_format($points, 0, ',', ' ') . '</td></tr>';
						$k++;
					}
				}
			}
			
		}
	?>
</body>

</html>

<script type="text/javascript" src="https://www.charon25.fr/script.js"></script>