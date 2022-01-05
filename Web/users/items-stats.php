<?php

    function cmp($item1, $item2) {
        $item1_games = count($item1['games']);
        $item2_games = count($item2['games']);
        if ($item1_games === $item2_games) {
            return strcmp($item1['name'], $item2['name']);
        }

        return -($item1_games > $item2_games ? +1 : -1);
    }

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT `queue`, `items`, `win` FROM `et2_matchs` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));

    $unsorted_output = array();
    while ($row = $request->fetch()) {
        $queue = $row['queue'];
        $items = json_decode($row['items'], TRUE);
        if (!array_key_exists($queue, $unsorted_output)) $unsorted_output[$queue] = array();
        foreach ($items as $key => $item_id) {
            if (!array_key_exists($item_id, $unsorted_output[$queue])) $unsorted_output[$queue][$item_id] = array('id' => $item_id, 'games' => array());
            $unsorted_output[$queue][$item_id]['games'][] = array('win' => $row['win']);
        }
    }

    $sorted_output = array();
    foreach ($unsorted_output as $queue_id => $queue) {
        usort($queue, 'cmp');
        $sorted_output[$queue_id] = $queue;
    }

    echo json_encode($sorted_output);

?>