<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);

	$showDemacians = TRUE;
	if (isset($_GET['mode']) && $_GET['mode'] == 'champions') {
		$showDemacians = FALSE;
	}

	if (!$showDemacians) {
		$sort_alpha = TRUE;
		if (isset($_GET['sort']) && $_GET['sort'] == 'games') {
			$sort_alpha = FALSE;
		}
	}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
	<title>Éternels de Demacia</title>
	<link rel="icon" href="icon.png">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		table {
			margin-left: 5%;
			border-spacing: 0;
			width: 90%;
			border: 1px solid #ddd;
		}

		.tft_table {
			margin-left: 25%;
			width: 50%;
		}

		.pentakills_table {
			margin-left: 30%;
			width: 40%;
		}

		.tft_table td {
			width: 33%;
		}

		.tft_table th {
			width: 33%;
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
        	-moz-appearance: button;
        	-webkit-appearance: button;
        	text-decoration: none; 
        	font: menu; 
        	color: ButtonText;
        	display: inline-block; 
        	padding: 2px 8px;
        	cursor: pointer;
        	width: 250px;
        	margin-bottom: 5px;
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
	<h1>Éternels de Demacia
		<?php 
			if ($showDemacians) {
				echo ' - Stats individuelles';
			} else {
				echo ' - Classement par champion';
			}
		 ?>
	</h1>
	<form method="get" id="change_mode" action="#" style="display: inline-block;">
		<?php 
			if ($showDemacians) {
				echo '<input type="hidden" name="mode" value="champions">';
				echo '<a class="button" onclick="document.getElementById(\'change_mode\').submit()">Classement par champion</a>';
			} else {
				echo '<a class="button" onclick="document.getElementById(\'change_mode\').submit()">Stats individuelles</a>';
			}
		 ?>
	</form>
	<?php if (!$showDemacians) { ?>
		<form method="get" id="change_sort" action="#" style="display: inline-block;">
			<?php 
				if ($sort_alpha) {
					echo '<input type="hidden" name="mode" value="champions">';
					echo '<input type="hidden" name="sort" value="games">';
					echo '<a class="button" onclick="document.getElementById(\'change_sort\').submit()">Trier par nombre de games</a>';
				} else {
					echo '<input type="hidden" name="mode" value="champions">';
					echo '<input type="hidden" name="sort" value="alpha">';
					echo '<a class="button" onclick="document.getElementById(\'change_sort\').submit()">Trier par ordre alphabétique</a>';
				}
			 ?>
		</form>
	<?php } ?>
	<a class="button" href="champion.php?id=total" width="100px" style="display: inline-block;">Classement global des Demaciens</a>
	<a class="button" href="items.php" style="display: inline-block;">Statistiques des objets</a>
	<a class="button" href="arams.php" width="100px" style="display: inline-block;">ARAMs</a><br>
	<a class="button" href="https://www.demacia.fr/"><img src="demacia_icon.png" alt="demacia_icon" height="30px" style="vertical-align: middle;">  Site de Demacia  <img src="demacia_icon.png" alt="demacia_icon" height="30px" style="vertical-align: middle;"></a>
	<p>Début : <strong><?php echo $debut; ?></strong></p>
	<p>Games en attente : 
		<?php 
			$req_games = $bdd->query('SELECT `id` FROM `et_matches`');
			echo $req_games->rowCount();
		 ?>
	</p>

	<table id="lol_table">
		<?php
			if ($showDemacians) {
				$req_users = $bdd->query('SELECT `id`,`name`,`discord_id`,`avatar`,`stats`,`smurfs`,`tft_stats`,`pentakills` FROM `et_users` ORDER BY `name` ASC');
				$k = 0;
				$output = "";
				$sum_stats = array();
				$tft_total_games = 0;
				$tft_total_wins = 0;
				$pentakills_array = array();
				while ($user = $req_users->fetch()) {
					$n_games = 0;
					foreach (json_decode($user['stats'], TRUE) as $champion_id => $champion_stats) {
						$n_games += $champion_stats['n'];
					}
					if ($user['tft_stats'] == 'X') {
						$tft_games = 0;
					} else {
						$tft_games = json_decode($user['tft_stats'], TRUE)['n'];
						$tft_total_games += $tft_games;
						$tft_wins = json_decode($user['tft_stats'], TRUE)['w'];
						$tft_total_wins += $tft_wins;
					}
					$td = '<td id="' . $user['id'] . '"><a href="demacien.php?id=' . $user['id'] . '&mode=all"><img width="30" height="30" style="vertical-align:middle" src="' . $bot->createAvatarURL($user['discord_id'], $user['avatar']) . '" alt="' . $user['name'] . '_pp">  ' . $user['name'] . ' (' . $n_games . ' game' . ($n_games <= 1 ? '' : 's') . ($tft_games == 0 ? "" : ", $tft_games TFT") . ')</a></td>';
					if ($k == 3) {
						echo $output . $td . '</tr>';
						$output = "";
						$k = 0;
					} else {
						if ($k == 0) {
							$output = '<tr style="vertical-align: middle; text-align: left;">';
						}
						$k++;
						$output = $output . $td;
					}
					foreach (json_decode($user['stats'], TRUE) as $champion_id => $champion_stats) {
						foreach ($champion_stats as $stat_name => $stat_value) {
							if (!array_key_exists($stat_name, $sum_stats)) {
								$sum_stats[$stat_name] = $stat_value;
							} else {
								$sum_stats[$stat_name] += $stat_value;
							}
						}
					}

					$user_pentas = json_decode($user['pentakills'], TRUE);
					if (json_last_error() == JSON_ERROR_NONE && $user['pentakills'] != "null") {
						foreach ($user_pentas as $key => $pentakill) {
							if ($pentakill['queue'] != "aram") {
								$penta_key = $pentakill['time'] / 10000 + rand(0, 100);
								$pentakills_array[$penta_key] = $pentakill;
								$pentakills_array[$penta_key]["username"] = $user['name'];
								$pentakills_array[$penta_key]["user_id"] = $user['id'];
								$pentakills_array[$penta_key]["discord_id"] = $user['discord_id'];
								$pentakills_array[$penta_key]["avatar"] = $user['avatar'];
							}
						}
					}
				}
				if ($k > 0) {
					for ($i = $k - 1 ; $i < 3 ; $i++) {
						$output = $output . '<td></td>';
					}
					echo $output . '</tr>';
				}
			} else {
				$req_stats_champions = $bdd->query('SELECT `champion_id`, `stats` FROM `et_champs`');
				$games_played_ids = array();
				while ($champion = $req_stats_champions->fetch()) {
					$games_played_ids[$champion['champion_id']] = 0;
					foreach (json_decode($champion['stats'], TRUE) as $user_id => $user_stats) {
						$games_played_ids[$champion['champion_id']] += $user_stats['n'];
					}
				}

				$req_champions = $bdd->query('SELECT `champion_id`, `champion_name` FROM `lol_champions` ORDER BY `champion_name` ASC');
				$champions_names = array();
				$champions_ids = array();
				$games_played_names = array();
				while ($champion = $req_champions->fetch()) {
					$champions_names[$champion['champion_id']] = $champion['champion_name'];
					$games_played_names[$champion['champion_name']] = $games_played_ids[$champion['champion_id']];
					$champions_ids[$champion['champion_name']] = $champion['champion_id'];
				}
				array_multisort(array_values($games_played_names), SORT_DESC, array_keys($games_played_names), SORT_ASC, $games_played_names);
				$k = 0;
				$output = "";
				$nn = 0;
				if ($sort_alpha) {
					foreach ($champions_names as $champion_id => $champion_name) {
						$nn++;
						$td = '<td class="text-center" id="' . $champion_id . '"><a href="champion.php?id=' . $champion_id . '"><img width="120" height="120" style="vertical-align:middle" src="../champions/big/' . $champion_name . '.png" alt="' . $champion_name . '"><br>' . $champion_name . ' (' . $games_played_ids[$champion_id] . ' game' . ($games_played_ids[$champion_id] <= 1 ? '' : 's') . ')</a></td>';
						if ($k == 3) {
							echo $output . $td . '</tr>';
							$output = "";
							$k = 0;
						} else {
							if ($k == 0) {
								$output = '<tr style="vertical-align: middle; text-align: center;">';
							}
							$k++;
							$output = $output . $td;	
						}
					}
				} else {
					foreach ($games_played_names as $champion_name => $champion_n) {
						$td = '<td class="text-center" id="' . $champions_ids[$champion_name] . '"><a href="champion.php?id=' . $champions_ids[$champion_name] . '"><img width="120" height="120" style="vertical-align:middle" src="../champions/big/' . $champion_name . '.png" alt="' . $champion_name . '"><br>' . $champion_name . ' (' . $champion_n . ' game' . ($champion_n <= 1 ? '' : 's') . ')</a></td>';
						if ($k == 3) {
							echo $output . $td . '</tr>';
							$output = "";
							$k = 0;
						} else {
							if ($k == 0) {
								$output = '<tr style="vertical-align: middle; text-align: center;">';
							}
							$k++;
							$output = $output . $td;	
						}
					}
				}
				if ($k > 0) {
					for ($i = $k - 1 ; $i < 3 ; $i++) {
						$output = $output . '<td></td>';
					}
					echo $output . '</tr>';
				}
			}
			
		?>
	</table>

	<?php if ($showDemacians) { ?>
		<h2>Statistiques globales</h2>
		<table>
			<tr>
				 <?php 
				 	$req_stats = $bdd->query('SELECT `name`,`generic_name` FROM `et_categories` ORDER BY `ordering` ASC');
				 	while ($stat = $req_stats->fetch()) {
				 		echo '<th class="text-center">' . $stat['generic_name'] . '</th>';
				 	}
				  ?>
			</tr>
			<tr>
				<?php 
					foreach ($sum_stats as $stat_name => $stat_value) {
						if ($stat_name == 'gt') {
							$output_value = number_format($stat_value / 3600, 1, ',', ' '); 
						} else if ($stat_name == 'kda') {
							$output_value = number_format(($sum_stats['d'] == 0 ? $sum_stats['k'] + $sum_stats['a'] : ($sum_stats['k'] + $sum_stats['a']) / $sum_stats['d']), 1, ',', ' ');
						} else if ($stat_name == 'w') {
							$output_value = number_format($stat_value, 0, ',', ' ') . ' (' . number_format(100 * $stat_value / $sum_stats['n'], 1, ',', ' ') . ' %)';
						} else {
							$output_value = number_format($stat_value, 0, ',', ' ');
						}
						echo '<td class="text-center">' . $output_value . '</td>';
					}
			 	?>
			</tr>
		</table>
		<?php 
			if (count($sum_stats) == 0) {
				echo '<h4>Aucune game n\'a encore été enregistrée !</h4>';
			}
		 ?>
		<h2>Statistiques globales de TFT</h2>
		<table class="tft_table">
			<tr>
				<th class="text-center">Nombre de games</th>
				<th class="text-center">Wins</th>
				<th class="text-center">Winrate</th>
			</tr>
			<tr>
				<td class="text-center"><?php echo $tft_total_games; ?></td>
				<td class="text-center"><?php echo $tft_total_wins; ?></td>
				<td class="text-center"><?php echo ($tft_total_games > 0 ? number_format(100 * $tft_total_wins / $tft_total_games, 1, ',', ' ') : "---") . ' %'; ?></td>
			</tr>
		</table>
		<?php if (count($pentakills_array) > 0) {
				krsort($pentakills_array);  ?>
			<h3>Pentakills (<?php echo count($pentakills_array); ?>)</h3>
			<p>Pour les pentakills en ARAM, voir les pages individuelles.</p>
			<table class="pentakills_table">
				<tr>
					<th>Demacien</th>
					<th>Champion</th>
					<th>Date</th>
					<th>Mode de jeu</th>
				</tr>
				<?php
					$req_champions = $bdd->query('SELECT `champion_id`, `champion_name` FROM `lol_champions`');
					$champs = array();
					while ($champ = $req_champions->fetch()) {
						$champs[$champ['champion_id']] = $champ['champion_name'];
					}
					foreach ($pentakills_array as $key => $pentakill) {
					$champion_name = $champs[$pentakill['champion_id']];
						echo '<tr>';
						echo '<td id="' . $pentakill['user_id'] . '"><a href="demacien.php?id=' . $pentakill['user_id'] . '&mode=all"><img width="30" height="30" style="vertical-align:middle" src="' . $bot->createAvatarURL($pentakill['discord_id'], $pentakill['avatar']) . '" alt="' . $pentakill['username'] . '_pp">  ' . $pentakill['username'] . '</a></td>';
						//echo '<td>' . $pentakill['username'] . '</td>';
						echo '<td><a champion_name="' . $champion_name . '" href="champion.php?id=' . $pentakill['champion_id'] . '"><img src="../champions/' . $champion_name . '" alt="' . $champion_name .'_miniature" style="vertical-align:middle"> ' . $champion_name . '</a></td>';
						echo '<td>' . date("d/m/Y H", $pentakill['time'] / 1000) . "h</td>";
						echo '<td>';
						switch ($pentakill['queue']) {
							case "normal":
								echo 'Normal 5v5';
								break;
							case "soloq":
								echo 'Solo Queue';
								break;
							case "flex":
								echo 'Flex Queue';
								break;
							case "clash":
								echo 'Clash';
								break;
							case "aram":
								echo 'ARAM';
								break;
							default:
								echo '?';
						}
						echo '</td></tr>';
					}
				 ?>
			</table>
		<?php } ?>
	<?php } ?>


</body>

</html>

<script type="text/javascript" src="https://www.charon25.fr/script.js"></script>