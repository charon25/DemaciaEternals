<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');

	if (isset($_POST['addChampion']) && hash('sha256', $_POST['bmbh']) == "c156f9863cacf145e33304805a1cb51705a853858a010f6ae2263d9b9e240a63") {
		$req_insert1 = $bdd->prepare('INSERT INTO `et_champs`(`champion_id`, `stats`, `masteries`) VALUES(?, ?, ?)');
		$req_insert1->execute(array($_POST['championID'], "{}", "{}"));
		$req_insert2 = $bdd->prepare('INSERT INTO `lol_champions`(`champion_id`, `champion_name`, `games`, `bans`, `wins`) VALUES(?, ?, ?, ?, ?)');
		$req_insert2->execute(array($_POST['championID'], $_POST['championName'], 0, 0, 0));
		echo '<p style="color: #0f0>Ajout réussi de ' . $_POST['championName'] . ' (' . $_POST['championID'] . ') !</p>';
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
</head>
<body>
	<form method="post">
		<p>Nom du champion : <input type="text" name="championName"></p>
		<p>ID du champion : <input type="number" min="1" max="1000000" name="championID"></p>
		<p>Mot de passe : <input type="password" name="bmbh"></p>
		<p><input type="submit" name="addChampion" value="Ajouter le champion"></p>
	</form>
</body>

</html>