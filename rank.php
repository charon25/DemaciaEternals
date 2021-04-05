<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require('bdd.php');
	require('constants.php');
	require('bot.php');
	echo '6<br>';

	$req = $bdd->query('SELECT `id`, `name`, `region`, `summoner_id`, `rank` FROM `et_users`');
	while ($user = $req->fetch()) {
		$content = @file_get_contents(str_replace("%region%", $user['region'], $req_start) . "league/v4/entries/by-summoner/" . $user['summoner_id'] . "?api_key=" . $riot_token);
		if (!($content === FALSE)) {
			$rank = json_decode($content, TRUE);
			$prev_ranks = json_decode($user['rank'], TRUE);
			foreach ($rank as $key => $queue) {
				$rank_string = $queue['tier'] . ',' . $queue['rank'] . ',' . $queue['leaguePoints'];
				$flag = 0;
				if (count($prev_ranks) >= 2) {
					$prev_queue = $prev_ranks[count($prev_ranks) - 2];
					if ($prev_queue['queue'] == $queue['queueType'] && $rank_string == $prev_queue['rank']) {
						$flag = 1;
					}
				}
				if (count($prev_ranks) >= 1) {
					$prev_queue = $prev_ranks[count($prev_ranks) - 1];
					if ($prev_queue['queue'] == $queue['queueType'] && $rank_string == $prev_queue['rank']) {
						$flag = 1;
					}
				}
				if ($flag == 0) $prev_ranks[] = array("time" => time(), "queue" => $queue['queueType'], "rank" => $rank_string, "wins" => $queue['wins'], "losses" => $queue['losses']);
			}
			if (json_encode($prev_ranks) != "null") {
				$req_insert = $bdd->prepare('UPDATE `et_users` SET `rank`=? WHERE `id`=?');
				$req_insert->execute(array(json_encode($prev_ranks), $user['id']));
			}
			usleep(10000);
		} else {
			echo "Erreur pour " . $user['name'] . " - Summoner ID : " . $user['summoner_id'];
		}
	}

?>