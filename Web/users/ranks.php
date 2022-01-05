<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT * FROM `et2_ranks` WHERE `user_id`=? ORDER BY `time` ASC');
    $request->execute(array($_GET['user_id']));

    $output = array("soloq" => array(), "flex" => array());
    while ($row = $request->fetch()) {
        $rank_type = (intval($row['type']) == 0 ? "soloq" : "flex");
        $output[$rank_type][] = array(
            'time' => $row['time'],
            'lp' => $row['lp'],
            'wins' => $row['wins'],
            'losses' => $row['losses']
        );
    }

    echo json_encode($output);

?>