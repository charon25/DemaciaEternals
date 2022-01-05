<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('item_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No item id specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT `queue`, `win` FROM `et2_matchs` WHERE `items` LIKE ?');
    $request->execute(array('%' . $_GET['item_id'] . '%'));

    $output = array();
    while ($row = $request->fetch()) {
        $queue = $row['queue'];
        if (!array_key_exists($queue, $output)) $output[$queue] = array();
        $output[$queue][] = array('win' => $row['win']);
    }

    echo json_encode($output);

?>