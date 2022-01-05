<?php

    function cmp($champ1, $champ2) {
        $champ1_games = count($champ1['games']);
        $champ2_games = count($champ2['games']);
        if ($champ1_games === $champ2_games) {
            return strcmp($champ1['name'], $champ2['name']);
        }

        return -($champ1_games > $champ2_games ? +1 : -1);
    }

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));

    $unsorted_output = array();
    while ($row = $request->fetch()) {
        $queue = $row['queue'];
        $champion = $row['opponent'];
        if (!array_key_exists($queue, $unsorted_output)) $unsorted_output[$queue] = array();
        if (!array_key_exists($champion, $unsorted_output[$queue])) $unsorted_output[$queue][$champion] = array('name' => $champion, 'games' => array());
        $match = array();
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) $match[$key] = $value;
        }
        $unsorted_output[$queue][$champion]['games'][] = $match;
    }

    $sorted_output = array();
    foreach ($unsorted_output as $queue_id => $queue) {
        usort($queue, 'cmp');
        $sorted_output[$queue_id] = $queue;
    }

    echo json_encode($sorted_output);

?>