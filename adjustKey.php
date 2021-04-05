<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

	require('bdd.php');
	require('constants.php');

	if (isset($_POST['bmbh'])) {
		if (hash('sha256', $_POST['bmbh']) == "c156f9863cacf145e33304805a1cb51705a853858a010f6ae2263d9b9e240a63") {
			if (isset($_POST['updateName'])) {
				$req_users = $bdd->query('SELECT `id`,`account_id`,`region` FROM `et_users`');
				while ($user = $req_users->fetch()) {
					$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start) . "summoner/v4/summoners/by-account/" . $user['account_id'] . "?api_key=" . $riot_token);
					if (!($content === FALSE)) {
						$summoner = json_decode($content, TRUE);
						$name = $summoner['name'];
						$req_update = $bdd->prepare('UPDATE `et_users` SET `summoner_name`=? WHERE `id`=?');
						$req_update->execute(array($name, $user['id']));
					} else {
						echo '<p>Erreur : id=' . $user['id'] . '</p>';
					}
				}
			} else if (isset($_POST['updateIds'])) {
				$req_users = $bdd->query('SELECT `id`,`summoner_name`,`region` FROM `et_users`');
				while ($user = $req_users->fetch()) {
					$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start) . "summoner/v4/summoners/by-name/" . str_replace(' ', '', $user['summoner_name']) . "?api_key=" . $riot_token);
					if (!($content === FALSE)) {
						$summoner = json_decode($content, TRUE);
						$account_id = $summoner['accountId'];
						$summoner_id = $summoner['id'];
						$req_update = $bdd->prepare('UPDATE `et_users` SET `account_id`=?, `summoner_id`=? WHERE `id`=?');
						$req_update->execute(array($account_id, $summoner_id, $user['id']));
					} else {
						echo '<p>Erreur : id=' . $user['id'] . '</p>';
					}
				}
			} else if (isset($_POST['updatePuuids'])) {
				$req_users = $bdd->query('SELECT `id`,`summoner_name`,`region` FROM `et_users`');
				while ($user = $req_users->fetch()) {
					$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start_tft) . "summoner/v1/summoners/by-name/" . str_replace(' ', '', $user['summoner_name']) . "?api_key=" . $riot_token_tft);
					if (!($content === FALSE)) {
						$summoner = json_decode($content, TRUE);
						$puuid = $summoner['puuid'];
						$summoner_id_tft = $summoner['id'];
						$req_update = $bdd->prepare('UPDATE `et_users` SET `puuid`=?, `summoner_id_tft`=? WHERE `id`=?');
						$req_update->execute(array($puuid, $summoner_id_tft, $user['id']));
					} else {
						echo '<p>Erreur : id=' . $user['id'] . '</p>';
					}
					usleep(1.2 * 1000000);
				}
			}
		}
	} else {

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
		<p>Mot de passe : <input type="password" name="bmbh"></p>
		<p><input type="submit" name="updateName" value="Mettre à jour les summoner names">   <input type="submit" name="updateIds" value="Mettre à jour les ids">   <input type="submit" name="updatePuuids" value="Mettre à jour les puuids et ids de TFT (lent!)"></p>
	</form>
</body>

</html>