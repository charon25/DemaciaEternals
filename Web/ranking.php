<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

    require_once('utils/bdd.php');
    require_once('utils/functions.php');
    require_once('utils/get_request.php');

    $GAME_THRESHOLD = 5;

    $PATCH = file_get_contents('patch.txt');
    $CHAMPIONS = get_all_champions($bdd, $PATCH);

    $CHAMPION = (array_key_exists('champion', $_GET) && array_key_exists(strtolower($_GET['champion']), $CHAMPIONS) ? $_GET['champion'] : '_all_');

    $STATS_ORDER = array(
        'Kills', 'Deaths', 'Assists', 'KDA',
        'KP (%)', 'DS (%)',
        'Winrate (%)', 'Durée (min)',
        'CS/min', 'Jungle/min', 'Golds/min', 'Dmg/min',
        'Vision/min', 'Games'
    );

    $users = get_all_users($bdd);
    $stats = get_all_stats($bdd);
    $CHAMPIONS = get_all_champions($bdd, $PATCH);

    $stats['Kills'] = array('symbol' => 'Kills', 'name' => 'Kills par game', 'rounding' => 2, 'sort_order' => 1);
    $stats['Deaths'] = array('symbol' => 'Deaths', 'name' => 'Morts par game', 'rounding' => 2, 'sort_order' => 0);
    $stats['Assists'] = array('symbol' => 'Assists', 'name' => 'Assists par game', 'rounding' => 2, 'sort_order' => 1);
    $stats['Games'] = array('symbol' => 'Games', 'name' => 'Nombre de games', 'rounding' => 0, 'sort_order' => 1);

    foreach ($users as $user_id => $_) {
        $average_stats = get_user_average_champion_stats($bdd, $user_id, $CHAMPION);
        $users[$user_id]['stats'] = $average_stats;
    }

    $QUEUES = array(400, 420, 440, 450);

    $rankings = array(); // Par queue, puis par stats
    foreach ($QUEUES as $_ => $queue_id) {
        $rankings[$queue_id] = array();
        foreach ($stats as $stat_symbol => $_) {
            $rankings[$queue_id][$stat_symbol] = array();
            foreach ($users as $user_id => $user) {
                if (!is_array($user['stats']) || count($user['stats']) == 0) continue;
                if (!array_key_exists($queue_id, $user['stats'])) continue;
                if ($user['stats'][$queue_id]['Games'] < $GAME_THRESHOLD) continue;
                if (!($user['auth'] & 16)) continue;
                $rankings[$queue_id][$stat_symbol][] = array('order' => $stats[$stat_symbol]['sort_order'], 'name' => $user['name'], 'user_id' => $user_id, 'value' => $user['stats'][$queue_id][$stat_symbol]);
            }
        }
    }

    function cmp($user1, $user2) {
        if ($user1['value'] === $user2['value']) {
            if ($user1['order'] == 0) return strcmp($user1['name'], $user2['name']);
            else return strcmp($user2['name'], $user1['name']);
        }
        if ($user1['order'] == 0) return $user1['value'] <=> $user2['value'];
        else return $user2['value'] <=> $user1['value'];
    }

    foreach ($QUEUES as $_ => $queue_id) {
        foreach ($stats as $stat_symbol => $_) {
            usort($rankings[$queue_id][$stat_symbol], 'cmp');
        }
    }

    function cmp_masteries($user1, $user2) {
        if ($user1['points'] === $user2['points']) {
            return strcmp($user1['name'], $user2['name']);
        }
        return $user2['points'] <=> $user1['points'];
    }

    if ($CHAMPION === '_all_') {
        $masteries = array();
        foreach ($users as $user_id => $user) {
            if (!($user['auth'] & 32)) continue;
            $most_played_champion = get_user_masteries($bdd, $user_id, 1);
            if (!is_array($most_played_champion) || count($most_played_champion) === 0) continue;
            $masteries[$user_id] = array('user_id' => $user_id, 'name' => $user['name'], 'champion' => array_keys($most_played_champion)[0], 'points' => array_values($most_played_champion)[0]);
        }

        usort($masteries, 'cmp_masteries');
    } else {
        $masteries = array();
        foreach ($users as $user_id => $user) {
            if (!($user['auth'] & 32)) continue;
            $mastery = get_user_mastery_one_champion($bdd, $user_id, $CHAMPION);
            if ($mastery < 0) continue; // = bug ou 0 points
            if ($mastery < 21600) continue; // 21600 est le nombre de points nécessaire pour la maîtrise 5
            $masteries[$user_id] = array('user_id' => $user_id, 'name' => $user['name'], 'champion' => $CHAMPION, 'points' => $mastery);
        }

        usort($masteries, 'cmp_masteries');
    }

?>

<!DOCTYPE html>
<html lang='fr'>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Éternels 2 de Demacia - Classement des Demaciens</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="resources/icon.png">
    </head>

    <body class="cfont container-fluid">
        <div class="row mt-2 mb-3">
            <div class="col-12 col-sm-12 d-flex justify-content-center text-center"><h1>Classement<?php echo ($CHAMPION === '_all_' ? ' global' : '') ?> des Demaciens<?php echo ($CHAMPION === '_all_' ? '' : ' sur ' . $CHAMPIONS[strtolower($CHAMPION)]['showname'])?></h1></div>
        </div>

        <div class="row mt-3 mb-3">
            <div class="offset-1 offset-lg-4 col-10 col-lg-4">
                <a href=".">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-block" type="button">← Retour</button>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mt-3 mb-3">
            <div class="col-12 col-sm-12 d-flex justify-content-center"><h3>Points de maîtrises max</h3></div>
        </div>

        <div class="row mb-3">
            <div class="offset-1 offset-lg-3 col-10 col-lg-6">
                <div class="table-responsive vertical-scroll">
                    <table class="table table-hover table-dark table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" class="text-nowrap clickable text-center">Rang ↕</th>
                                <th scope="col" class="text-nowrap clickable">Demacien ↕</th>
                                <th scope="col" class="text-nowrap clickable">Champion ↕</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $rank = 1;
                                foreach ($masteries as $_ => $user) {
                                    echo '<tr>';
                                    echo '<td scope="row" class="text-nowrap text-center bold" data-type="int">' . $rank . ($rank == 1 ? 'er' : 'e') . '</td>';
                                    echo '<td scope="row" class="text-nowrap text-start" data-type="demacien"><a href="user?user_id=' . $user['user_id'] . '"><img src="' . $users[$user['user_id']]['img'] . '" width="32"> ' . $user['name'] . '</a></td>';
                                    echo '<td scope="row" class="text-nowrap text-start" data-type="masteries"><a href="ranking?champion=' . $user['champion'] . '"><img src="' . $CHAMPIONS[strtolower($user['champion'])]['img'] . '" width="32"></a> ' . number_format($user['points'] / 1000, 0, ',', ' ') . ' k</td>';
                                    echo '</tr>';
                                    $rank++;
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="row mt-3">
            <div class="col-12 d-flex justify-content-center"><h3>Mode de jeu</h3></div>
            <div class="offset-1 offset-lg-3 col-10 col-lg-6 d-flex justify-content-center"><p>5 games sont requises pour être affiché dans le classement.</p></div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-4"></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-420" onclick="change_queue(420);" type="button">SoloQ</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-440" onclick="change_queue(440);" type="button">FlexQ</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-400" onclick="change_queue(400);" type="button">Norm</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-450" onclick="change_queue(450);" type="button">ARAM</button></div>
            <div class="col-lg-4"></div>
        </div>

        <?php
            foreach ($rankings as $queue_id => $ranking) {
                $side = 0;
                foreach ($STATS_ORDER as $_ => $stat_symbol) {
                    if ($side % 2 === 0) {
                        echo '<div class="row hide mb-3 ranking" id="ranking-q-' . $queue_id . '-s-' . $stat_symbol . '"><div class="mb-3 mb-lg-0 offset-1 offset-lg-1 col-10 col-lg-4 text-center">';
                    } else {
                        echo '<div class="offset-1 offset-lg-2 col-10 col-lg-4 text-center">';
                    }
                    echo '<h4 class="mb-3">' . $stats[$stat_symbol]['name'] . '</h4>';
                    echo '<div class="table-responsive vertical-scroll"><table id="table-rankings-q-' . $queue_id . '-s-' . $stat_symbol . '" class="table table-hover table-dark table-striped text-center">';
                    echo '<thead class="table-dark"><tr><th scope="col" class="text-nowrap clickable" width="100">Rang ↕</th><th scope="col" class="text-nowrap clickable text-left" width="100">Demacien ↕</th><th scope="col" class="text-nowrap clickable">' . $stat_symbol . ' ↕</th></tr></thead>';

                    echo '<tbody>';
                    $rank = 1;
                    foreach ($ranking[$stat_symbol] as $_ => $user) {
                        echo '<tr>';
                        echo '<td scope="row" class="text-nowrap text-center bold" data-type="int">' . $rank . ($rank == 1 ? 'er' : 'e') . '</td>';
                        echo '<td scope="row" class="text-nowrap text-start" data-type="demacien"><a href="user?user_id=' . $user['user_id'] . '"><img src="' . $users[$user['user_id']]['img'] . '" width="32"> ' . $user['name'] . '</a></td>';
                        echo '<td scope="row" class="text-nowrap text-center" data-type="float">' . number_format($user['value'], $stats[$stat_symbol]['rounding'], ',', ' ') . '</td>';
                        echo '</tr>';
                        $rank++;
                    }

                    echo '</tbody></table></div></div>';

                    if ($side % 2 === 1) echo '</div>';

                    $side++;
                }
                if ($side % 2 === 1) echo '</div>';
            }
        ?>

    </body>

    <script type="text/javascript">
        function change_queue(queue) {
            // Boutons
            var buttons = document.getElementsByClassName('queue-button');
            for (let button of buttons) {
                if (button.id == `queue-button-q-${queue}`) {
                    button.classList.remove('btn-dark');
                    button.classList.add('btn-primary');
                } else {
                    button.classList.add('btn-dark');
                    button.classList.remove('btn-primary');
                }
            }

            var tables = document.getElementsByClassName('ranking');
            for (let table of tables) {
                if (table.id.includes(`q-${queue}`)) {
                    table.classList.remove('hide');
                } else {
                    table.classList.add('hide');
                }
            }

        }

        change_queue(420);
    </script>

    <script src='js/sort_table.js'></script>

</html>