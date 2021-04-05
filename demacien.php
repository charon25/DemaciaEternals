<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	require('bdd.php');
	require('constants.php');

	if (isset($_GET['id']) && strlen($_GET['id']) <= 4) {
		$req_user = $bdd->prepare('SELECT `name`,`summoner_name`,`start_date`,`stats`,`total_stats`,`roles_stats`,`masteries`,`smurfs`,`tft_stats`,`pentakills` FROM `et_users` WHERE `id`=?');
		$req_user->execute(array($_GET['id']));
		$user = $req_user->fetch();

		$req_champions = $bdd->query('SELECT `champion_id`, `champion_name` FROM `lol_champions`');
		$champs = array();
		while ($champ = $req_champions->fetch()) {
			$champs[$champ['champion_id']] = $champ['champion_name'];
		}
		$champs['Total'] = 'Total';

		$req_stats = $bdd->query('SELECT `name`,`generic_name`,`reducing`,`reduced_name` FROM `et_categories` ORDER BY `ordering` ASC');
		$stats_array = array();
		$reducing_array = array();
		if (!isset($_GET['mode'])) {
			$_GET['mode'] = 'all';
		}
		while ($stat = $req_stats->fetch()) {
			if ($_GET['mode'] == 'all') {
				$stats_array[$stat['name']] = $stat['generic_name'];
			} else if ($_GET['mode'] == 'reduced') {
				$stats_array[$stat['name']] = $stat['reduced_name'];
				$reducing_array[$stat['name']] = $stat['reducing'];
			}
		}

		$roles_array = json_decode($user['roles_stats'], TRUE);

		if ($user['tft_stats'] == 'X') {
			$has_played_tft = FALSE;
		} else {
			$has_played_tft = TRUE;
			$tft_array = json_decode($user['tft_stats'], TRUE);
		}

		$pentakills_stats = json_decode($user['pentakills'], TRUE);
		$has_pentakills = (json_last_error() == JSON_ERROR_NONE && $user['pentakills'] != "null");


		/*$masteries_json = json_decode($user['masteries'], TRUE);
		$masteries_array = array();
		foreach ($masteries_json as $champion_id => $champion_infos) {
			$masteries_array[$champion_id] = end($champion_infos['points']);
		}
		arsort($masteries_array);*/
		$masteries_array = json_decode($user['masteries'], TRUE);
		arsort($masteries_array);

		$req_masteries = $bdd->query('SELECT `champion_id`, `masteries` FROM `et_champs`');
		$rank_array = array();
		$masteries_number_array = array();
		while ($champ = $req_masteries->fetch()) {
			$temp_array = array(); 
			$masteries_json = json_decode($champ['masteries'], TRUE);
			foreach ($masteries_json as $user_id => $user_points) {
				$temp_array[$user_id] = $user_points;
			}
			arsort($temp_array);
			$rank_array[$champ['champion_id']] = array_search($_GET['id'], array_keys($temp_array)) + 1;
			$masteries_number_array[$champ['champion_id']] = count($masteries_json);
		}

		$smurfs = array();
		if (strlen($user['smurfs']) > 0) {
			$req_smurfs = $bdd->prepare('SELECT `summoner_name` FROM `et_smurfs` WHERE `main_id`=?');
			$req_smurfs->execute(array($_GET['id']));
			while ($smurf = $req_smurfs->fetch()) {
				$smurfs[] = $smurf['summoner_name'];
			}
		}
	}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
	<title>Éternels de Demacia - 
		<?php 
			if (isset($user)) {
				echo $user['name'];
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
			margin-left: 5%;
			border-spacing: 0;
			width: 90%;
			border: 1px solid #ddd;
		}

		th {
			cursor: pointer;
			text-align: left;
			padding: 16px;
		}

		.tft_table {
			margin-left: 25%;
			width: 50%;
		}

		.tft_table td {
			width: 33%;
		}

		.tft_table th {
			width: 33%;
		}

		.unclickable {
			cursor: default;
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

    	.spoiler {
    		display: none;
    	}
	</style>
</head>

<body style="text-align: center;">
	<h1>Eternels de Demacia - 
		<?php 
			if (isset($user)) {
				echo $user['name'];
			} else {
				echo 'Erreur';
			}
		?>
	</h1>
	<form method="get" id="change_mode">
	<a class="button" href="index.php<?php if (isset($user)) {echo '#' . $_GET['id'];} ?>">← Revenir à la liste</a>
		<input type="hidden" name="id" value="<?php if (isset($user)) {echo $_GET['id'];} ?>">
		<?php
			if (isset($_GET['mode'])) {
				if ($_GET['mode'] == 'all') {
					echo '<input type="hidden" name="mode" value="reduced">';
				} else if ($_GET['mode'] == 'reduced') {
					echo '<input type="hidden" name="mode" value="all">';
				}
			}
			if (isset($_GET['mode'])) {
				if ($_GET['mode'] == 'all') {
					echo '<a class="button" onclick="document.getElementById(\'change_mode\').submit()">Afficher les valeurs relatives</a>';
				} else if ($_GET['mode'] == 'reduced') {
					echo '<a class="button" onclick="document.getElementById(\'change_mode\').submit()">Afficher les valeurs totales</a>';
				}
			}
		 ?>
	</form>
	<p>Début : <strong>
	<?php 
		if (isset($user)) {
			echo $user['start_date'];
		} else {
			echo '';
		}
	 ?></strong>
	</p>
	<p>Compte<?php echo (count($smurfs) == 0 ? '' : 's') ?> : 
		<?php 
			echo $user['summoner_name'] . (count($smurfs) == 0 ? '' : ', ' . implode(', ', $smurfs));
		 ?>
	</p>

	<h3>Champions</h3>
	<table id="lol_table">
		<tr>
			<th onclick="sortTable('lol_table', 0, 'asc')">Champions ↕</th>
			<?php 
				$k = 1;
				foreach ($stats_array as $key => $stat_name) {
					echo '<th onclick="sortTable(\'lol_table\', ' . $k . ', \'desc\')" class="text-center">' . $stat_name . ' ↕</th>';
					$k++;
				}
			 ?>
		</tr>
		<?php
			if (isset($user)) {
				if (strlen($user['stats']) > 2) {
					$stats_json = json_decode($user['stats'], TRUE);
					$stats_json['Total'] = json_decode($user['total_stats'], TRUE);
					foreach ($stats_json as $champion_id => $stats) {
						$champion_name = $champs[$champion_id];
						echo '<tr style="vertical-align: middle;">';
						echo '<td><a champion_name="' . $champion_name . '" href="champion.php?id=' . $champion_id . '"><img src="../champions/' . $champion_name . '" alt="' . $champion_name .'_miniature" style="vertical-align:middle">	' . $champion_name . '</a></td>';
						foreach ($stats as $name => $value) {
							if ($name != "mastery") {
								if ($_GET['mode'] == 'all') {
									if ($name == 'gt') {
										$output_value = round($value / 3600, 0);
										$round_digit = 1;
									} else if ($name == 'kda') {
										$output_value = ($stats['d'] == 0 ? $stats['k'] + $stats['a'] : ($stats['k'] + $stats['a']) / $stats['d']);
										$round_digit = 1;
									} else {
										$output_value = $value;
										$round_digit = 0;
									}
									echo '<td class="text-center">' . number_format($output_value, $round_digit, ',', ' ') . '</td>';
								} else if ($_GET['mode'] == 'reduced') {
									if ($reducing_array[$name] == 'g') {
										if ($name == 'gt') {
											$output_value = number_format($value / (60 * $stats['n']), 0, ',', ' ');
										} else if ($name == 'w') {
											$output_value = number_format(100 * $value / $stats['n'], 1, ',', ' ') . ' %';
										} else {
											$output_value = number_format($value / $stats['n'], 1, ',', ' ');
										}
									} else if ($reducing_array[$name] == 'm') {
										$output_value = number_format(60 * $value / $stats['gt'], 1, ',', ' ');
									} else {
										if ($name == 'kda') {
											$output_value = number_format($stats['d'] == 0 ? $stats['k'] + $stats['a'] : ($stats['k'] + $stats['a']) / $stats['d'], 1, ',', ' ');
										} else {
											$output_value = number_format($value, 0, ',', ' ');
										}
									}
									echo '<td class="text-center">' . $output_value . '</td>';
								}	
							}
							
						}
						echo '</tr>';
					}
				}
			}
		?>
	</table>

	<h3>Rôles</h3>
	<table id="roles_table">
		<tr>
			<th onclick="sortTable('roles_table', 0, 'asc')">Rôles ↕</th>
			<th onclick="sortTable('roles_table', 1, 'asc')" class="text-center">Nombre de games ↕</th>
			<th onclick="sortTable('roles_table', 2, 'asc')" class="text-center">Playrate ↕</th>
			<th onclick="sortTable('roles_table', 3, 'asc')" class="text-center">Nombre de wins ↕</th>
			<th onclick="sortTable('roles_table', 4, 'asc')" class="text-center">Winrate ↕</th>
		</tr>
		<?php
			$k = 0;
			$total_games_roles = 0;
			foreach ($roles_array as $role_name => $role_stats) {
				$total_games_roles += $role_stats['n'];
			}
			foreach ($roles_array as $role_name => $role_stats) {
				if ($role_stats['n'] > 0) {
					echo '<tr>';
					echo '<td><img order="' . $k . '" src="' . '../roles/' . $role_name . '.png" alt="' . $role_name . '_miniature" style="vertical-align: middle;">' . $role_name . '</td>';
					echo '<td class="text-center">' . number_format($role_stats['n'], 0, ',', ' ') . '</td>';
					echo '<td class="text-center">' . number_format(100 * $role_stats['n'] / $total_games_roles, 1, ',', ' ') . ' %</td>';
					echo '<td class="text-center">' . number_format($role_stats['w'], 0, ',', ' ') . '</td>';
					echo '<td class="text-center">' . number_format(100 * $role_stats['w'] / $role_stats['n'], 1, ',', ' ') . ' %</td>';
					echo '</tr>';
					$k++;
				}
			}
		 ?>
	</table>

	<?php if ($has_pentakills) { ?>
		<h3>Pentakills (<?php echo count($pentakills_stats); ?>)</h3>
		<table id="pentakills_table" style="width: 30%; margin-left: 35%;">
			<tr>
				<th class="unclickable">Champion</th>
				<th class="unclickable text-center">Date</th>
				<th class="unclickable text-center">Mode de jeu</th>
			</tr>
			<?php
				$pentakills_stats = array_reverse($pentakills_stats);
				foreach ($pentakills_stats as $key => $pentakill) {
				$champion_name = $champs[$pentakill['champion_id']];
					echo '<tr>';
					echo '<td><a champion_name="' . $champion_name . '" href="champion.php?id=' . $pentakill['champion_id'] . '"><img src="../champions/' . $champion_name . '" alt="' . $champion_name .'_miniature" style="vertical-align:middle"> ' . $champion_name . '</a></td>';
					echo '<td class="text-center">' . date("d/m/Y H", $pentakill['time'] / 1000) . "h</td>";
					echo '<td class="text-center">';
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

	<?php if ($has_played_tft) { ?>
	<h3>TFT</h3>
	<table id="tft_table" class="tft_table">
		<tr>
			<th class="text-center">Nombre de games</th>
			<th class="text-center">Wins</th>
			<th class="text-center">Winrate</th>
		</tr>
		<tr>
			<td class="text-center"><?php echo $tft_array['n']; ?></td>
			<td class="text-center"><?php echo $tft_array['w']; ?></td>
			<td class="text-center"><?php echo number_format(100 * $tft_array['w'] / $tft_array['n'], 1, ',', ' ') . ' %'; ?></td>
		</tr>
	</table>
	<?php } ?>

	<h3>Points de maîtrise</h3>
	<table id="mastery_table" style="width: 40%; margin-left: 30%">
		<tr>
			<th class="unclickable">Champion</th>
			<th class="text-center unclickable">Point de maîtrises</th>
			<th class="text-center unclickable">Proportion</th>
			<th onclick="sortTable('mastery_table', 3, 'asc')" class="text-center">Rang</th>
		</tr>
		<?php
			$k = 0;
			$masteries_sum = 0;
			foreach ($masteries_array as $champion_id => $points) {
				$masteries_sum += $points;
			}
			foreach ($masteries_array as $champion_id => $points) {
				$champion_name = $champs[$champion_id];
				echo '<tr>';
				echo '<td><a champion_name="' . $champion_name . '" href="champion.php?id=' . $champion_id . '"><img src="../champions/' . $champion_name . '" alt="' . $champion_name .'_miniature" style="vertical-align:middle">	' . $champion_name . '</a></td>';
				echo '<td class="text-center">' . number_format($points, 0, ',', ' ') . '</td>';
				echo '<td class="text-center">' . number_format(100 * $points / $masteries_sum, 1, ',', ' ') . ' %</td>';
				echo '<td class="text-center">' . $rank_array[$champion_id] . '/' . $masteries_number_array[$champion_id] . '</td>';
				echo '</tr>';
				$k++;
				if ($k >= $max_showing_mastery || $points / $masteries_sum < 0.015) {
					break;
				}
			}
		 ?>
	</table>


</body>
<script type="text/javascript">
	function sortTable(table_id, n, dir) {
		var table, rows, switching, i, x, x_str, y, y_str, shouldSwitch, dir, switchcount = 0;
		var dir0 = dir;
		table = document.getElementById(table_id);
		switching = true;
  			//dir = "desc"; 
  			while (switching) {
  				switching = false;
  				rows = table.rows;
  				for (i = 1; i < (rows.length - 1); i++) {
  					shouldSwitch = false;
  					x = rows[i].getElementsByTagName("TD")[n];
  					if (x.innerHTML.split(".")[0].length == 1) {
  						x_str = "0" + x.innerHTML;
  					} else {
  						x_str = x.innerHTML;
  					}
  					x_str = x_str.replace(" ", "").replace(",", ".").replace("%", "");
  					y = rows[i + 1].getElementsByTagName("TD")[n];
  					if (y.innerHTML.split(".")[0].length == 1) {
  						y_str = "0" + y.innerHTML;
  					} else {
  						y_str = y.innerHTML;
  					}
  					y_str = y_str.replace(" ", "").replace(",", ".").replace("%", "");
  					if (table_id == "mastery_table") {
  						x_str = x_str.split("/")[0];
  						y_str = y_str.split("/")[0];
  					}
  					if (dir == "asc") {
  						if (isNaN(x_str)) {
  							if (x_str.toLowerCase() > y_str.toLowerCase()) {
  								shouldSwitch= true;
  								break;
  							}
  						} else {
  							if (parseFloat(x_str) > parseFloat(y_str)) {
  								shouldSwitch= true;
  								break;
  							}
  						}
  					} else if (dir == "desc") {
  						if (isNaN(x_str)) {
  							if (x_str.toLowerCase() < y_str.toLowerCase()) {
  								shouldSwitch= true;
  								break;
  							}
  						} else {
  							if (parseFloat(x_str) < parseFloat(y_str)) {
  								shouldSwitch= true;
  								break;
  							}
  						}
  					}
  				}
  				if (shouldSwitch) {
  					rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
  					switching = true;
  					switchcount ++;      
  				} else {
  					if (dir0 == "desc") {
  						if (switchcount == 0 && dir == "desc") {
  							dir = "asc";
  							switching = true;
  						}
  					} else {
  						if (switchcount == 0 && dir == "asc") {
  							dir = "desc";
  							switching = true;
  						}
  					}
  				}
  			}
  		}

  		sortTable("lol_table", 9, 'desc');
  		sortTable("roles_table", 1, 'desc');
  	</script>
</html>

<script type="text/javascript" src="https://www.charon25.fr/script.js"></script>