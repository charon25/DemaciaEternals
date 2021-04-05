<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);

	$demaciens_stats = array();
	$total_demaciens = 0;
	$req_demaciens = $bdd->query('SELECT `id`, `name`, `discord_id`, `avatar`, `aram_stats` FROM `et_users`');
	while ($demacien = $req_demaciens->fetch()) {
		if ($demacien['aram_stats'] != "{}") {
			$name = $demacien['name'];
			$temp = json_decode($demacien['aram_stats'], TRUE);
			$demaciens_stats[$name] = array( "id" => $demacien['id'], "discord_id" => $demacien['discord_id'], "avatar" => $demacien['avatar'], "n" => 0, "w" => 0);
			foreach ($temp as $champion_id => $champion_stats) {
				$demaciens_stats[$name]['n'] += $champion_stats['n'];
				$demaciens_stats[$name]['w'] += $champion_stats['w'];
				$total_demaciens += $champion_stats['n'];
			}
			$demaciens_stats[$name]['wr'] = 100 * $demaciens_stats[$name]['w'] / $demaciens_stats[$name]['n'];
		}
	}

	$req_champs = $bdd->query('SELECT `champion_id`, `aram_stats` FROM `et_champs`');
	$champ_stats = array();
	$total_champs = 0;
	while ($champ = $req_champs->fetch()) {
		$temp = json_decode($champ['aram_stats'], TRUE);
		if (array_key_exists('n', $temp)) {
			$total_champs += $temp['n'];
			if ($temp['n'] > 20) {
				$champ_stats[$champ['champion_id']] = $temp;
				$champ_stats[$champ['champion_id']]['wr'] = 100 * $temp['w'] / $temp['n']; 
			}
		}
	}

	$req_champions = $bdd->query('SELECT `champion_id`, `champion_name` FROM `lol_champions` ORDER BY `champion_name` ASC');
	$champions_names = array();
	while ($champion = $req_champions->fetch()) {
		if (array_key_exists($champion['champion_id'], $champ_stats)) {
			$champions_names[$champion['champion_id']] = $champion['champion_name'];
		}
	}

?>


<!DOCTYPE html>
<html lang="fr">

<head>
	<title>Éternels de Demacia - ARAMs</title>
	<link rel="icon" href="icon.png">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		table {
			margin-left: 25%;
			border-spacing: 0;
			width: 50%;
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
	<h1>Eternels de Demacia - ARAMs</h1>
	<a class="button" href="index.php">← Revenir à la page principale</a>
	<p>Début : <strong>2020-03-17</strong></p>
	<h3>Demaciens (au moins <?php echo number_format(floor($arams_min_freq_demaciens * $total_demaciens), 0, ',', ' ') ?> ARAMs)</h3>
	<table id="demaciens_table">
		<tr>
			<th onclick="sortTable('demaciens_table', 0, 'asc')">Demaciens ↕</th>
			<th onclick="sortTable('demaciens_table', 1, 'desc')" class="text-center">Games ↕</th>
			<th onclick="sortTable('demaciens_table', 2, 'desc')" class="text-center">Wins ↕</th>
			<th onclick="sortTable('demaciens_table', 3, 'desc')" class="text-center">Winrate ↕</th>
		</tr>
		<?php 
			foreach ($demaciens_stats as $demacien_name => $data) {
				if ($data['n'] >= $arams_min_freq_demaciens * $total_demaciens) {
					echo '<tr style="vertical-align: middle;">';
					echo '<td><a href="demacien.php?id=' . $data['id'] . '&mode=all"><img width="30" height="30" style="vertical-align:middle" src="' . $bot->createAvatarURL($data['discord_id'], 	$data['avatar']) . '" alt="' . $demacien_name . '_pp">  ' . $demacien_name . '</td>';
					echo '<td class="text-center">' . $data['n'] . '</td>';
					echo '<td class="text-center">' . $data['w'] . '</td>';
					echo '<td class="text-center">' . number_format($data['wr'], 1, ',', ' ') . ' %' . '</td>';
					echo '</tr>';
				}
			}
		 ?>
	</table>
	<h3>Champions (au moins <?php echo number_format(floor($arams_min_freq_champions * $total_champs), 0, ',', ' ') ?> ARAMs)</h3>
	<table id="champions_table">
		<tr>
			<th onclick="sortTable('champions_table', 0, 'asc')">Champions ↕</th>
			<th onclick="sortTable('champions_table', 1, 'desc')" class="text-center">Games ↕</th>
			<th onclick="sortTable('champions_table', 2, 'desc')" class="text-center">Wins ↕</th>
			<th onclick="sortTable('champions_table', 3, 'desc')" class="text-center">Winrate ↕</th>
		</tr>
		<?php 

			foreach ($champ_stats as $champion_id => $stats) {
				if ($stats['n'] >= $arams_min_freq_champions * $total_champs) {
					echo '<tr style="vertical-align: middle;">';
					$champion_name = $champions_names[$champion_id];
					echo '<td><a champion_name="' . $champion_name . '" href="champion.php?id=' . $champion_id . '"><img src="../champions/' . $champion_name . '" alt="' . $champion_name .'_miniature" style="vertical-align:middle">	' . $champion_name . '</a></td>';
					echo '<td class="text-center">' . $stats['n'] . '</td>';
					echo '<td class="text-center">' . $stats['w'] . '</td>';
					echo '<td class="text-center">' . number_format($stats['wr'], 1, ',', ' ') . ' %' . '</td>';
					echo '</tr>';
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

  		sortTable("demaciens_table", 1, 'desc');
  		sortTable("champions_table", 1, 'desc');
  	</script>

</html>