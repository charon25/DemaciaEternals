<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	require('bdd.php');
	require('constants.php');
	require('bot.php');

	$req_items = $bdd->query('SELECT * FROM `et_items` WHERE `games_played` > 0');
	$items_stats = array("Item" => array(), "Mythic" => array(), "Bottes" => array(), "Trinket" => array());
	$total_games = array("Item" => 0, "Mythic" => 0, "Bottes" => 0, "Trinket" => 0);
	while ($item = $req_items->fetch()) {
		$items_stats[$item['item_type']][$item['item_name']] = array('item_id' => $item['item_id'], 'n' => $item['games_played'], 'w' => $item['wins']);
		$total_games[$item['item_type']] += $item['games_played'];
	}
	ksort($items_stats);

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
			margin-left: 10%;
			border-spacing: 0;
			width: 80%;
			border: 1px solid #ddd;
		}

		th {
			text-align: left;
			padding: 16px;
			cursor: pointer;
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
	<h1>Statistiques des objets</h1>
	<a class="button" href="index.php?mode=demaciens">← Revenir à la liste des Demaciens</a>
	<a class="button" href="index.php?mode=champions&sort=alpha">← Revenir à la liste des champions</a>
	<p>Début : <strong><?php echo $debut_items; ?></strong></p>
	<a class="button" href="#legendaires">Légendaires ↓</a>  <a class="button" href="#bottes">Bottes ↓</a>  <a class="button" href="#trinkets">Trinkets ↓</a>

	<h3>Objets Mythiques</h3>
	<table id="mythic_table">
		<tr>
			<th onclick="sortTable('mythic_table', 0, 'asc')">Objet</th>
			<th onclick="sortTable('mythic_table', 1, 'desc')" class="text-center">Nombre de games</th>
			<th onclick="sortTable('mythic_table', 2, 'desc')" class="text-center">Pickrate</th>
			<th onclick="sortTable('mythic_table', 3, 'desc')" class="text-center">Nombre de wins</th>
			<th onclick="sortTable('mythic_table', 4, 'desc')" class="text-center">Winrate</th>
		</tr>
		<?php 
			foreach ($items_stats['Mythic'] as $item_name => $item_infos) {
				echo '<tr style="vertical-align: middle;">';
				echo '<td width="20%"><img alt="' . $item_name . '_miniature" style="vertical-align: middle;" width="32" height="32" src="../items/' . $item_infos['item_id'] . '.png">  ' . $item_name . '</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['n'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['n'] / $total_games['Item'], 1, ',', ' ') . ' %</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['w'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['w'] / $item_infos['n'], 1, ',', ' ') . ' %</td>';
				echo '</tr>';
			}
		 ?>
	</table>

	<h3 id="legendaires">Objets légendaires</h3>
	<table id="items_table">
		<tr>
			<th onclick="sortTable('items_table', 0, 'asc')">Objet</th>
			<th onclick="sortTable('items_table', 1, 'desc')" class="text-center">Nombre de games</th>
			<th onclick="sortTable('items_table', 2, 'desc')" class="text-center">Pickrate</th>
			<th onclick="sortTable('items_table', 3, 'desc')" class="text-center">Nombre de wins</th>
			<th onclick="sortTable('items_table', 4, 'desc')" class="text-center">Winrate</th>
		</tr>
		<?php 
			foreach ($items_stats['Item'] as $item_name => $item_infos) {
				echo '<tr style="vertical-align: middle;">';
				echo '<td width="20%"><img alt="' . $item_name . '_miniature" style="vertical-align: middle;" width="32" height="32" src="../items/' . $item_infos['item_id'] . '.png">  ' . $item_name . '</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['n'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['n'] / $total_games['Item'], 1, ',', ' ') . ' %</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['w'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['w'] / $item_infos['n'], 1, ',', ' ') . ' %</td>';
				echo '</tr>';
			}
		 ?>
	</table>

	<h3 id="bottes">Bottes</h3>
	<table id="boots_table">
		<tr>
			<th onclick="sortTable('boots_table', 0, 'asc')">Objet</th>
			<th onclick="sortTable('boots_table', 1, 'desc')" class="text-center">Nombre de games</th>
			<th onclick="sortTable('boots_table', 2, 'desc')" class="text-center">Pickrate</th>
			<th onclick="sortTable('boots_table', 3, 'desc')" class="text-center">Nombre de wins</th>
			<th onclick="sortTable('boots_table', 4, 'desc')" class="text-center">Winrate</th>
		</tr>
		<?php 
			foreach ($items_stats['Bottes'] as $item_name => $item_infos) {
				echo '<tr style="vertical-align: middle;">';
				echo '<td width="20%"><img alt="' . $item_name . '_miniature" style="vertical-align: middle;" width="32" height="32" src="../items/' . $item_infos['item_id'] . '.png">  ' . $item_name . '</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['n'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['n'] / $total_games['Bottes'], 1, ',', ' ') . ' %</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['w'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['w'] / $item_infos['n'], 1, ',', ' ') . ' %</td>';
				echo '</tr>';
			}
		 ?>
	</table>

	<h3 id="trinkets">Trinkets</h3>
	<table id="trinkets_table">
		<tr>
			<th onclick="sortTable('trinkets_table', 0, 'asc')">Objet</th>
			<th onclick="sortTable('trinkets_table', 1, 'desc')" class="text-center">Nombre de games</th>
			<th onclick="sortTable('trinkets_table', 2, 'desc')" class="text-center">Pickrate</th>
			<th onclick="sortTable('trinkets_table', 3, 'desc')" class="text-center">Nombre de wins</th>
			<th onclick="sortTable('trinkets_table', 4, 'desc')" class="text-center">Winrate</th>
		</tr>
		<?php 
			foreach ($items_stats['Trinket'] as $item_name => $item_infos) {
				echo '<tr style="vertical-align: middle;">';
				echo '<td width="20%"><img alt="' . $item_name . '_miniature" style="vertical-align: middle;" width="32" height="32" src="../items/' . $item_infos['item_id'] . '.png">  ' . $item_name . '</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['n'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['n'] / $total_games['Trinket'], 1, ',', ' ') . ' %</td>';
				echo '<td width="20%" class="text-center">' . number_format($item_infos['w'], 0, ',', ' ') . '</td>';
				echo '<td width="20%" class="text-center">' . number_format(100 * $item_infos['w'] / $item_infos['n'], 1, ',', ' ') . ' %</td>';
				echo '</tr>';
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
  					console.log(x.innerHTML);
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
  		sortTable('mythic_table', 1, 'desc');
  		sortTable('items_table', 1, 'desc');
  		sortTable('boots_table', 1, 'desc');
  		sortTable('trinkets_table', 1, 'desc');
</script>
</html>

<script type="text/javascript" src="https://www.charon25.fr/script.js"></script>