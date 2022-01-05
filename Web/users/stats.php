<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));

    $output = array();
    while ($row = $request->fetch()) {
        $queue = $row['queue'];
        if (!array_key_exists($queue, $output)) $output[$queue] = array();
        $match = array();
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) $match[$key] = $value;
        }
        $output[$queue][] = $match;
    }

    echo json_encode($output);

?>