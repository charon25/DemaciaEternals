<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    $min_points = (array_key_exists('min_points', $_GET) && is_numeric($_GET['min_points']) ? intval($_GET['min_points']) : 0);
    $limit = (array_key_exists('limit', $_GET) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 1000);

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT `masteries` FROM `et2_masteries` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));
    $masteries = $request->fetch()['masteries'];

    if ($min_points == 0 && $limit == 1000) {
        echo $masteries;
    } else {
        $masteries = json_decode($masteries, TRUE);
        $output = array();
        $count = 0;
        foreach ($masteries as $champion => $points) {
            if (intval($points) >= $min_points && $count < $limit) $output[$champion] = $points;
            $count++;
        }

        echo json_encode($output);
    }

?>