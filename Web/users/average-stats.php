<?php

    require_once('../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');
    require_once('../utils/functions.php');

    $CHAMPION = (array_key_exists('champion', $_GET) ? $_GET['champion'] : '_all_');

    $stats = get_all_stats($bdd);

    $QUEUES = array(400, 420, 440, 450);

    $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));

    $games = array();
    $sums = array();
    $output = array();
    while ($row = $request->fetch()) {
        if ($CHAMPION !== '_all_' && $row['champion'] !== $CHAMPION) continue;
        $queue = $row['queue'];
        if (!in_array($queue, $QUEUES)) continue;
        if (!array_key_exists($queue, $games)) $games[$queue] = array();
        if (!array_key_exists($queue, $sums)) {
            $sums[$queue] = array();
            foreach ($stats as $stat_symbol => $_) {
                $sums[$queue][$stat_symbol] = array();
            }
        }
        if (!array_key_exists($queue, $output)) $output[$queue] = array();
        $match = array();
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) $match[$key] = $value;
        }
        $games[$queue][] = $match;
    }

    $supplementary_stats = array('Kills', 'Deaths', 'Assists');

    foreach ($games as $queue_id => $queue_games) {
        foreach ($queue_games as $_ => $game) {
            foreach ($stats as $stat_symbol => $stat) {
                $sums[$queue_id][$stat_symbol][] = compute_stat($game, $stat);
            }
            foreach ($supplementary_stats as $_ => $stat_symbol) {
                $sums[$queue_id][$stat_symbol][] = $game[strtolower($stat_symbol)];
            }
        }

        
        foreach ($stats as $stat_symbol => $stat) {
            $output[$queue_id][$stat_symbol] = array_sum($sums[$queue_id][$stat_symbol]) / count($sums[$queue_id][$stat_symbol]);
        }
        foreach ($supplementary_stats as $_ => $stat_symbol) {
            $output[$queue_id][$stat_symbol] = array_sum($sums[$queue_id][$stat_symbol]) / count($sums[$queue_id][$stat_symbol]);
        }

        $output[$queue_id]['Games'] = count($games[$queue_id]);
    }


    echo json_encode($output);

?>