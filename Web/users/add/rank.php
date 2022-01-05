<?php

    require_once('../../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('rank', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain rank","status_code":400}}';
        exit;
    }

    $rank = $DATA['rank'];

    require_once('../../utils/bdd.php');

    $request = $bdd->prepare('SELECT `lp`, `wins`, `losses` FROM `et2_ranks` WHERE `user_id`=? AND `type`=? ORDER BY `time` DESC LIMIT 1');
    $request->execute(array($_GET['user_id'], $rank['type']));
    $row = $request->fetch();

    if (!is_array($row)) {
        $request = $bdd->prepare('INSERT INTO `et2_ranks`(`user_id`, `type`, `time`, `lp`, `wins`, `losses`) VALUES (?, ?, ?, ?, ?, ?)');
        $request->execute(array($_GET['user_id'], $rank['type'], $rank['time'], $rank['lp'], $rank['wins'], $rank['losses']));
        echo '{"status":{"message":"Rank added","status_code":200}}';
    } else {
        if (intval($row['lp']) === intval($rank['lp']) && intval($row['wins']) === intval($rank['wins']) && intval($row['losses']) === intval($rank['losses'])) {
            echo '{"status":{"message":"Rank already saved","status_code":200}}';
        } else {
            $request = $bdd->prepare('INSERT INTO `et2_ranks`(`user_id`, `type`, `time`, `lp`, `wins`, `losses`) VALUES (?, ?, ?, ?, ?, ?)');
            $request->execute(array($_GET['user_id'], $rank['type'], $rank['time'], $rank['lp'], $rank['wins'], $rank['losses']));
            echo '{"status":{"message":"Rank added","status_code":200}}';
        }
    }


?>