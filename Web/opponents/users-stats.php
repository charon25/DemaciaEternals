<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('champion', $_GET)) {
        echo '{"status":{"message":"Bad Request - No champion specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `opponent`=?');
    $request->execute(array($_GET['champion']));

    $output = array();
    while ($row = $request->fetch()) {
        $queue = $row['queue'];
        $user_id = $row['user_id'];
        if (!array_key_exists($queue, $output)) $output[$queue] = array();
        if (!array_key_exists($user_id, $output[$queue])) $output[$queue][$user_id] = array();
        $match = array();
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) $match[$key] = $value;
        }
        $output[$queue][$user_id][] = $match;
    }

    echo json_encode($output);

?>