<?php

    require_once('../../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('match', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain match","status_code":400}}';
        exit;
    }

    require_once('../../utils/bdd.php');

    $request = $bdd->prepare('INSERT INTO `et2_matchs`(`user_id`, `match_id`, `time`, `champion`, `opponent`, `win`, `teamId`, `role`, `duration`, `queue`, `total_kills`, `total_deaths`, `kills`, `assists`, `deaths`, `golds`, `damages`, `minions`, `monsters`, `vision`, `items`) VALUES (:user_id, :id, :time, :championName, :opponent, :win, :teamId, :individualPosition, :duration, :queue, :totalKills, :totalDeaths, :kills, :assists, :deaths, :goldEarned, :totalDamageDealtToChampions, :totalMinionsKilled, :neutralMinionsKilled, :visionScore, :items)');
    $request->execute($DATA['match']);
    
    echo '{"status":{"message":"Match added","status_code":200}}';


?>