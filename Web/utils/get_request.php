<?php

    require_once('functions.php');

    function get_users_games_count($bdd) {
        $request = $bdd->query('SELECT `user_id`, `name` FROM `et2_users`');

        $games_count = array();
        while ($row = $request->fetch()) {
            $req = $bdd->prepare('SELECT COUNT(*) FROM `et2_matchs` WHERE `user_id`=?');
            $req->execute(array($row['user_id']));
            $games_count[$row['user_id']] = array(
                'name' => $row['name'],
                'games_count' => $req->fetch()['COUNT(*)']
            );
        }
    
        return $games_count;
    }

    function get_champions_games_count($bdd) {
        $request = $bdd->query('SELECT `name` FROM `et2_champions`');
    
        $games_count = array();
        while ($row = $request->fetch()) {
            $req = $bdd->prepare('SELECT COUNT(*) FROM `et2_matchs` WHERE `champion`=?');
            $req->execute(array($row['name']));
            $games_count[$row['name']] = array(
                'games_count' => $req->fetch()['COUNT(*)']
            );
        }
    
        return $games_count;
    }


    function get_user_average_champion_stats($bdd, $user_id, $champion) {
        $stats = get_all_stats($bdd);

        $QUEUES = array(400, 420, 440, 450);

        $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `user_id`=?');
        $request->execute(array($user_id));

        $games = array();
        $sums = array();
        $output = array();
        while ($row = $request->fetch()) {
            if ($champion !== '_all_' && $row['champion'] !== $champion) continue;
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

        return $output;
    }


    function get_user_masteries($bdd, $user_id, $limit = 1000, $min_points = 0) {
        $request = $bdd->prepare('SELECT `masteries` FROM `et2_masteries` WHERE `user_id`=?');
        $request->execute(array($user_id));
        $result = $request->fetch();
        
        if (!is_array($result)) return array();
    
        $masteries = json_decode($result['masteries'], TRUE);

        if ($min_points == 0 && $limit == 1000) {
            return $masteries;
        } else {
            $champions = array();
            $count = 0;
            foreach ($masteries as $champion => $points) {
                $champions[$champion] = intval($points);
                $count++;
                if ($count >= $limit || intval($points) < $min_points) break;
            }
        
            return $champions;
        }
    }

    function _compare_champions__get_request($champ1, $champ2) {
        $champ1_games = count($champ1['games']);
        $champ2_games = count($champ2['games']);
        if ($champ1_games === $champ2_games) {
            return strcmp($champ1['name'], $champ2['name']);
        }

        return -($champ1_games > $champ2_games ? +1 : -1);
    }

    function get_champions_stats($bdd, $user_id, $side = 'champion') {
    
        $request = $bdd->prepare('SELECT * FROM `et2_matchs` WHERE `user_id`=?');
        $request->execute(array($user_id));
    
        $unsorted_data = array();
        while ($row = $request->fetch()) {
            $queue = $row['queue'];
            $champion = $row[$side];
            if (!array_key_exists($queue, $unsorted_data)) $unsorted_data[$queue] = array();
            if (!array_key_exists($champion, $unsorted_data[$queue])) $unsorted_data[$queue][$champion] = array('name' => $champion, 'games' => array());
            $match = array();
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) $match[$key] = $value;
            }
            $unsorted_data[$queue][$champion]['games'][] = $match;
        }
    
        $sorted_data = array();
        foreach ($unsorted_data as $queue_id => $queue) {
            usort($queue, '_compare_champions__get_request');
            $sorted_data[$queue_id] = $queue;
        }
    
        return $sorted_data;
    }

    function get_opponents_stats($bdd, $user_id) {
        return get_champions_stats($bdd, $user_id, 'opponent');
    }


    function get_user_ranks($bdd, $user_id) {
        $request = $bdd->prepare('SELECT * FROM `et2_ranks` WHERE `user_id`=? ORDER BY `time` ASC');
        $request->execute(array($user_id));
    
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
    
        return $output;        
    }

    function _compare_items__get_request($item1, $item2) {
        $item1_games = count($item1['games']);
        $item2_games = count($item2['games']);

        return $item2_games <=> $item1_games;
    }

    function get_user_items_stats($bdd, $user_id) {
        $request = $bdd->prepare('SELECT `queue`, `items`, `win` FROM `et2_matchs` WHERE `user_id`=?');
        $request->execute(array($user_id));
    
        $unsorted_data = array();
        while ($row = $request->fetch()) {
            $queue = $row['queue'];
            $items = json_decode($row['items'], TRUE);
            if (!array_key_exists($queue, $unsorted_data)) $unsorted_data[$queue] = array();
            foreach ($items as $_ => $item_id) {
                if (!array_key_exists($item_id, $unsorted_data[$queue])) $unsorted_data[$queue][$item_id] = array('id' => $item_id, 'games' => array());
                $unsorted_data[$queue][$item_id]['games'][] = array('win' => $row['win']);
            }
        }
    
        $sorted_data = array();
        foreach ($unsorted_data as $queue_id => $queue) {
            usort($queue, '_compare_items__get_request');
            $sorted_data[$queue_id] = $queue;
        }
    
        return $sorted_data;
    }




