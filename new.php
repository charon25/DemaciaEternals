<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require('bdd.php');
	require('constants.php');
	require('bot.php');
	$bot = new Bot($discord_token, $GUILD_ID);
	$START_TIME = "1605070800000";

	if (isset($_POST['new_add'])) {
		if (hash("sha256", $_POST['new_bmbh']) == "c156f9863cacf145e33304805a1cb51705a853858a010f6ae2263d9b9e240a63") {
			$name = $_POST['new_pseudo'];
			$lol_name = $_POST['new_lol'];
			$disc_id = $_POST['new_discord'];
			$content = @file_get_contents(str_replace("%region%", "euw1", $req_start) . "summoner/v4/summoners/by-name/" . $lol_name . "?api_key=" . $riot_token);
			if (!($content === FALSE)) {
				$summoner = json_decode($content, TRUE);
				$summoner_id = $summoner['id'];
				$account_id = $summoner['accountId'];
				$content = @file_get_contents(str_replace("%region%", "euw1", $req_start_tft) . "summoner/v1/summoners/by-name/" . $lol_name . "?api_key=" . $riot_token_tft);
				if (!($content === FALSE)) {
					$summoner_tft = json_decode($content, TRUE);
					$summoner_id_tft = $summoner_tft['id'];
					$puuid_tft = $summoner_tft['puuid'];
					$default_role = '"{"ADC":{"n":0,"w":0},"Mid":{"n":0,"w":0},"Support":{"n":0,"w":0},"Top":{"n":0,"w":0},"Jungle":{"n":0,"w":0}}"';
					$req_insert = $bdd->prepare('INSERT INTO `et_users` (`id`, `last_game`, `name`, `account_id`, `summoner_id`, `summoner_name`, `discord_id`, `avatar`, `start_date`, `show_ranking`, `stats`, `total_stats`, `roles_stats`, `masteries`, `region`, `smurfs`, `puuid`, `summoner_id_tft`, `tft_stats`, `aram_stats`, `isNew`, `maison`, `pentakills`, `rank`) VALUES (NULL, "1610078400000", ?, ?, ?, ?, ?, "", "2021-01-08", "1", "{}", "{}", ?, "{}", "euw1", NULL, ?, ?, "X", "{}", "1", "", "[]", "[]")');
					$req_insert->execute(array($name, $account_id, $summoner_id, $lol_name, $disc_id, $default_role, $puuid_tft, $summoner_id_tft));
					echo '<p>' . $name . ' ajouté(e) correctement !</p>';
				} else {
					echo "<p>Erreur avec l'API de TFT</p>";
				}
			} else {
				echo "<p>Erreur avec l'API de LoL</p>";
			}
		} else {
			echo "<p>Mot de passe incorrect</p>";
		}
	}

	if (isset($_POST['games_get'])) {
		$id = $_POST['games_id'];
		$req_user = $bdd->prepare('SELECT `account_id`, `region`, `last_game` FROM `et_users` WHERE `id`=?');
		$req_user->execute(array($id));
		$user = $req_user->fetch();
		$acc_id = $user['account_id'];
		$region = $user['region'];
		$beginIndex = $user['last_game'];
		$endLoop = FALSE;
		$list_of_games = array();
		//echo str_replace("%region%", $region, $req_start) . "match/v4/matchlists/by-account/" . $acc_id . "?beginTime=" . $beginIndex . "&api_key=" . $riot_token;
		
		do {
			$content = @file_get_contents(str_replace("%region%", $region, $req_start) . "match/v4/matchlists/by-account/" . $acc_id . "?beginTime=" . $beginIndex . "&api_key=" . $riot_token);
			if (!($content === FALSE)) {
				$matchlist = json_decode($content, TRUE)['matches'];
				if (count($matchlist) == 0) {
					$endLoop = TRUE;
				} else {
					foreach ($matchlist as $i => $match) {
						if ($match['timestamp'] >= $START_TIME) {
							if (in_array($match['queue'], $queues)) {
								$list_of_games[] = $match['gameId'];
							}
						} else {
							$endLoop = TRUE;
							break;
						}
					}
				}
			} else {
				$endLoop = TRUE;
			}
			$beginIndex += 100;
		} while (!$endLoop);
		$request = 'INSERT INTO `et_matches`(`match_id`, `users_id`, `region`) VALUES ';
		$k = 0;
		foreach ($list_of_games as $key => $match_id) {
			if ($k % 100 == 0) {
				if ($k != 0) {
					$request = trim($request, ',');
					$req_insert_games = $bdd->query($request);
					file_put_contents("request.txt", file_get_contents("request.txt") . "\n\n" . $request);
				}
				$request = 'INSERT INTO `et_matches`(`match_id`, `users_id`, `region`) VALUES ';
			}
			$request = $request . '("' . $match_id . '", "' . $id . '/' . $acc_id . '", "euw1"),';
			$k++;
		}
		if (strlen($request) > 70) {
			$request = trim($request, ',');
			$req_insert_games = $bdd->query($request);
			file_put_contents("request.txt", file_get_contents("request.txt") . "\n\n" . $request);
		}

		$req_not_new = $bdd->prepare('UPDATE `et_users` SET `last_game`=?, `isNew`=0 WHERE `id`=?');
		$req_not_new->execute(array(time()*1000, $id));

		echo "<p>" . count($list_of_games) . " games ont été ajoutées !";
	}

 ?>

 <!DOCTYPE html>
 <html>
 <head>
 	<title>Éternels de Demacia - Nouveau Demacien - V3</title>
	<link rel="icon" href="icon.png">
 </head>
 <body>
 
 	<form method="post">
 		<p>Pseudo : <input type="text" name="new_pseudo"></p>
 		<p>Pseudo LoL : <input type="text" name="new_lol"></p>
 		<p>ID Discord: <input type="text" name="new_discord"></p>
 		<p>Mot de passe : <input type="password" name="new_bmbh"></p>
 		<input type="submit" name="new_add" value="Ajouter">
 	</form>
 	<hr>
 	<form method="post">
 		<p>Nouveau Demacien : <select name="games_id">
 			<?php 
 				$req_news = $bdd->query('SELECT `id`, `name` FROM `et_users` WHERE `isNew`=1');
 				while ($new = $req_news->fetch()) {
 				echo '<option value="' . $new['id'] . '">' . $new['name'] . '</option>';
 				}
 			 ?>
 		</select></p>
 		<input type="submit" name="games_get" value="Récupérer les games de la S11">
 	</form>
 	<hr>
 	<p>V3.3</p>

 </body>
 </html>