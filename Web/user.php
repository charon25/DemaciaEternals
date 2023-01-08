<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

    if (!array_key_exists('user_id', $_GET)) {
        echo 'Demacien inconnu';
        exit;
    }

    require_once('utils/bdd.php');
    require_once('utils/functions.php');
    require_once('utils/get_request.php');

    $request = $bdd->prepare('SELECT `user_id`, `name`, `auth` FROM `et2_users` WHERE `user_id`=? AND `display`=1');
    $request->execute(array($_GET['user_id']));
    $user = $request->fetch();

    if (!is_array($user)) {
        echo 'Demacien inconnu';
        exit;
    }

    $PATCH = file_get_contents('patch.txt');

    $CHAMPIONS = get_all_champions($bdd, $PATCH); // Tous les noms sont en minuscules pour éviter le bug "FiddleSticks"
    $CHAMPIONS['total'] = array('img' => 'resources/Total.png', 'showname' => 'Total');

    $request = $bdd->query('SELECT `id`, `name_fr`, `type` FROM `et2_items` WHERE `type` IN ("mythic", "legendary")');
    $ITEMS = array();

    while ($row = $request->fetch()) {
        $ITEMS[$row['id']] = array(
            'name' => $row['name_fr'],
            'img' => 'https://ddragon.leagueoflegends.com/cdn/' . $PATCH . '/img/item/' . $row['id'] . '.png',
            'type' => $row['type']
        );
    }

    $stats = get_all_stats($bdd);

    $QUEUES = array('400' => 'normal', '420' => 'soloq', '440' => 'flex', '450' => 'aram');
    $ROLES = array('TOP' => 'Top', 'JUNGLE' => 'Jungle', 'MIDDLE' => 'Mid', 'BOTTOM' => 'ADC', 'UTILITY' => 'Supp', 'Invalid' => '?');
    $ARAM_REMOVED = array('Jungle/min', 'Vision/min');


    // CHAMPIONS JOUÉS

    $raw_champions_stats = get_champions_stats($bdd, $user['user_id']);
    $champions_stats = array();
    foreach ($QUEUES as $queue_id => $queue_name) {
        $champions_stats[$queue_id] = array('Total' => array());
    }

    $games_counts = array();
    foreach ($raw_champions_stats as $queue_id => $queue) {
        if (!array_key_exists($queue_id, $QUEUES)) continue;
        if (!array_key_exists($queue_id, $games_counts)) $games_counts[$queue_id] = array('Total' => 0);
        foreach ($queue as $key => $champion) {
            foreach ($champion['games'] as $i => $game) {
                $champion_name = $champion['name'] . ($queue_id == 450 ? '' : ' ' . $ROLES[$game['role']]);
                if (!array_key_exists($champion_name, $champions_stats[$queue_id])) $champions_stats[$queue_id][$champion_name] = array();
                if (!array_key_exists($champion_name, $games_counts[$queue_id])) $games_counts[$queue_id][$champion_name] = 0;
                $games_counts[$queue_id][$champion_name]++;
                $games_counts[$queue_id]['Total']++;
                foreach ($stats as $stat_symbol => $stat) {
                    if (!array_key_exists($stat_symbol, $champions_stats[$queue_id][$champion_name])) {
                        $champions_stats[$queue_id][$champion_name][$stat_symbol] = 0;
                    }
                    if (!array_key_exists($stat_symbol, $champions_stats[$queue_id]['Total'])) {
                        $champions_stats[$queue_id]['Total'][$stat_symbol] = 0;
                    }
                    $stat_value = compute_stat($game, $stat);
                    $champions_stats[$queue_id][$champion_name][$stat_symbol] += $stat_value;
                    $champions_stats[$queue_id]['Total'][$stat_symbol] += $stat_value;
                }
            }
        }

        foreach ($champions_stats[$queue_id] as $champion_name => $_) {
            foreach ($stats as $stat_symbol => $stat) {
                $champions_stats[$queue_id][$champion_name][$stat_symbol] /= $games_counts[$queue_id][$champion_name];
            }
        }
        
    }

    foreach ($QUEUES as $queue_id => $queue_name) {
        if (count($champions_stats[$queue_id]) == 1) unset($champions_stats[$queue_id]['Total']);
    }

    // CHAMPIONS AFFRONTÉS

    $raw_opponents_stats = get_opponents_stats($bdd, $user['user_id']);
    $opponents_stats = array();
    foreach ($QUEUES as $queue_id => $queue_name) {
        $opponents_stats[$queue_id] = array();
    }


    $opponents_games_counts = array();
    foreach ($raw_opponents_stats as $queue_id => $queue) {
        if (!array_key_exists($queue_id, $QUEUES)) continue;
        if (!array_key_exists($queue_id, $opponents_games_counts)) $opponents_games_counts[$queue_id] = array();
        foreach ($queue as $key => $champion) {
            foreach ($champion['games'] as $i => $game) {
                if ($champion['name'] == '') continue;
                $champion_name = $champion['name'] . ($queue_id == 450 ? '' : ' ' . $ROLES[$game['role']]);
                if (!array_key_exists($champion_name, $opponents_stats[$queue_id])) $opponents_stats[$queue_id][$champion_name] = array();
                if (!array_key_exists($champion_name, $opponents_games_counts[$queue_id])) $opponents_games_counts[$queue_id][$champion_name] = 0;
                $opponents_games_counts[$queue_id][$champion_name]++;
                foreach ($stats as $stat_symbol => $stat) {
                    if (!array_key_exists($stat_symbol, $opponents_stats[$queue_id][$champion_name])) {
                        $opponents_stats[$queue_id][$champion_name][$stat_symbol] = 0;
                    }
                    $opponents_stats[$queue_id][$champion_name][$stat_symbol] += compute_stat($game, $stat);
                }
            }
        }

        foreach ($opponents_stats[$queue_id] as $champion_name => $_) {
            foreach ($stats as $stat_symbol => $stat) {
                $opponents_stats[$queue_id][$champion_name][$stat_symbol] /= $opponents_games_counts[$queue_id][$champion_name];
            }
        }
    }

    // ELOS
    $ranks = get_user_ranks($bdd, $user['user_id']);
    $master_limits = json_decode(file_get_contents('master-limits.txt'), TRUE);

    if (count($ranks['soloq']) > 0) {
        $last_entry_soloq = $ranks['soloq'][count($ranks['soloq']) - 1];
        $current_soloq = array('elo' => get_ranks_from_lp($last_entry_soloq['lp']), 'winrate' => get_winrate($last_entry_soloq['wins'], $last_entry_soloq['losses']));
    } else {
        $no_soloq = TRUE;
    }

    if (count($ranks['flex']) > 0) {
        $last_entry_flex = $ranks['flex'][count($ranks['flex']) - 1];
        $current_flex = array('elo' => get_ranks_from_lp($last_entry_flex['lp']), 'winrate' => get_winrate($last_entry_flex['wins'], $last_entry_flex['losses']));        
    } else {
        $no_flex = TRUE;
    }

    // POINTS DE MAITRISES
    $masteries = get_user_masteries($bdd, $user['user_id'], 20);

    // ITEMS
    $raw_items_data = get_user_items_stats($bdd, $user['user_id']);
    $items_data = array();    
    foreach ($QUEUES as $queue_id => $queue_name) {
        $items_data[$queue_id] = array();
    }

    foreach ($raw_items_data as $queue_id => $items) {
        if (!array_key_exists($queue_id, $QUEUES)) continue;
        $items_data[$queue_id] = array();
        foreach ($items as $_ => $item) {
            if (!array_key_exists($item['id'], $ITEMS)) continue;
            $wins = 0;
            foreach ($item['games'] as $_ => $game) {
                $wins += intval($game['win']);
            }
            $items_data[$queue_id][$item['id']] = array('games_count' => count($item['games']), 'wins' => $wins);
        }
    }

    // TODO : rôles

?>

<!DOCTYPE html>
<html lang='fr'>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Éternels 2 de Demacia - <?php echo $user['name']; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="resources/icon.png">
    </head>

    <body class="cfont container-fluid">
        <div class="row mt-2 mb-3">
            <div class="col-12 d-flex justify-content-center text-center"><h1>Éternels de <?php echo $user['name']; ?></h1></div>
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
            <div class="col-12 d-flex justify-content-center"><h3>Mode de jeu</h3></div>
        </div>
        <div class="row mt-3 mb-3">
            <div class="offset-lg-4 col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-420" onclick="change_queue(420);" type="button">SoloQ</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-440" onclick="change_queue(440);" type="button">FlexQ</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-400" onclick="change_queue(400);" type="button">Norm</button></div>
            <div class="col-3 col-sm-3 col-md-3 col-lg-1 d-grid gap-2"><button class="btn btn-dark mt-1 mb-1 queue-button" id="queue-button-q-450" onclick="change_queue(450);" type="button">ARAM</button></div>
        </div>

        <?php if ($user['auth'] & 1) { ?>

        <div class="row mt-3 mb-3">
            <div class="col-12 d-flex justify-content-center">
                <?php print_title_with_expand('Champions joués', 'played-champions'); ?>
            </div>
        </div>

        <div id="block-played-champions">
        <?php

            foreach ($champions_stats as $queue_id => $queue) {
                echo '<div class="row mt-3 mb-3 hide played-champions" id="played-champions-q-' . $queue_id . '"><div class="offset-1 col-10  offset-lg-1 col-lg-10"><div class="table-responsive vertical-scroll"><table id="table-played-champions-q-' . $queue_id . '" class="table table-hover table-dark table-striped text-center">';
                
                echo '<thead class="table-dark"><tr><th scope="col" class="text-nowrap clickable">Champion' . ($queue_id == 450 ? '' : ' (Rôle)') . ' ↕</th><th scope="col" class="text-nowrap clickable" title="Nombre de games">#Games ↕</th>';
                foreach ($stats as $stat_symbol => $stat) {
                    if ($queue_id == 450 && in_array($stat_symbol, $ARAM_REMOVED)) continue;
                    echo '<th scope="col" class="text-nowrap clickable" title="' . $stat['name'] . '">' . $stat_symbol . ' ↕</th>';
                }
                echo '</tr></thead>';
                
                echo '<tbody>';
                foreach ($queue as $champion_name => $queue_champions_stats) {
                    echo '<tr>';
                    $champion_name_alone = strtolower(explode(' ', $champion_name)[0]);
                    $role = ($queue_id == 450 || $champion_name == 'Total' ? '' : explode(' ', $champion_name)[1]);
                    echo '<td scope="row" class="text-start text-nowrap" data-type="champion"><a href="ranking?champion=' . $CHAMPIONS[$champion_name_alone]['name'] . '"><img src="' . $CHAMPIONS[$champion_name_alone]['img'] . '" width="30"> ' . $CHAMPIONS[$champion_name_alone]['showname'] . ($queue_id == 450 || $champion_name == 'Total' ? '' : ' (' . $role . ')') . '</a></td>';
                    echo '<td class="font-weight-normal" data-type="int">' . $games_counts[$queue_id][$champion_name] . '</td>';
                    foreach ($queue_champions_stats as $stat_symbol => $stat_value) {
                        if (!array_key_exists($stat_symbol, $stats)) continue;
                        if ($queue_id == 450 && in_array($stat_symbol, $ARAM_REMOVED)) continue;
                        echo '<td class="text-nowrap" data-type="' . ($stats[$stat_symbol]['rounding'] == 0 ? 'int' : 'float') . '">' . number_format($stat_value, $stats[$stat_symbol]['rounding'], ',', ' ') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody>';

                echo '</table></div></div></div>';
            }
        ?>
        </div>

        <div class="row mt-3 mb-1" id="opponents-title">
            <div class="offset-1 col-10  d-flex justify-content-center text-center">
                <?php print_title_with_expand('Champions adverses (match-ups directs)', 'opponents-champions'); ?>
            </div>
        </div>

        <div id="block-opponents-champions">
        <div class="row mt-1" id="opponents-text" class="text-center">
            <div class="offset-1 col-10 d-flex justify-content-center"><p>Les stats sont les vôtres lorsque vous jouez contre ce champion</p></div>
        </div>
        <?php
            foreach ($opponents_stats as $queue_id => $queue) {
                if ($queue_id == 450) continue;
                echo '<div class="row mb-3 hide opponents-champions" id="opponents-champions-q-' . $queue_id . '"><div class="offset-1 col-10  col-lg-10"><div class="table-responsive vertical-scroll"><table id="table-opponents-champions-q-' . $queue_id . '" class="table table-hover table-dark table-striped text-center">';
                
                echo '<thead class="table-dark"><tr><th scope="col" class="text-nowrap clickable">Champion' . ($queue_id == 450 ? '' : ' (Rôle)') . ' ↕</th><th scope="col" class="text-nowrap clickable" title="Nombre de games">#Games ↕</th>';
                foreach ($stats as $stat_symbol => $stat) {
                    echo '<th scope="col" class="text-nowrap clickable" title="' . $stat['name'] . '">' . $stat_symbol . ' ↕</th>';
                }
                echo '</tr></thead>';
                
                echo '<tbody>';
                foreach ($queue as $champion_name => $queue_opponent_stats) {
                    echo '<tr>';
                    $champion_name_alone = strtolower(explode(' ', $champion_name)[0]);
                    $role = explode(' ', $champion_name)[1];
                    echo '<td scope="row" class="text-start text-nowrap" data-type="champion"><a href="ranking?champion=' . $CHAMPIONS[$champion_name_alone]['name'] . '"><img src="' . $CHAMPIONS[$champion_name_alone]['img'] . '" width="30"> ' . $CHAMPIONS[$champion_name_alone]['showname'] . ' (' . $role . ')</a></td>';
                    echo '<td class="font-weight-normal" data-type="int">' . $opponents_games_counts[$queue_id][$champion_name] . '</td>';
                    foreach ($queue_opponent_stats as $stat_symbol => $stat_value) {
                        if (!array_key_exists($stat_symbol, $stats)) continue;
                        echo '<td class="text-nowrap" data-type="' . ($stats[$stat_symbol]['rounding'] == 0 ? 'int' : 'float') . '">' . number_format($stat_value, $stats[$stat_symbol]['rounding'], ',', ' ') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody>';

                echo '</table></div></div></div>';
            }
        ?>
        </div>

        <?php } ?>

        <?php if ($user['auth'] & 2) { ?>

        <div class="row mt-5 mb-3">
            <div class="col-12 d-flex justify-content-center">
                <?php print_title_with_expand('ELO SoloQ', 'elo-soloq'); ?>
            </div>
        </div>

        <div id="block-elo-soloq">
        <?php if (!isset($no_soloq)) { ?>
        <div class="row mt-3 mb-3" data-masonry='{"percentPosition": true }'>
            <div class="col-12 offset-lg-1 col-lg-3 d-flex justify-content-center">
                <div class="row">
                    <div class="col-12 d-flex justify-content-center">
                        <img src="elo-icons/<?php echo explode(' ', $current_soloq['elo'])[0]; ?>.png" width="250">
                    </div>
                    <div class="col-12 d-flex justify-content-center">
                        <h4><?php echo $current_soloq['elo']; ?></h4>
                    </div>
                    <div class="col-12 d-flex justify-content-center">
                        <h5>Winrate : <?php echo number_format(100 * $current_soloq['winrate'], 1, ',', ' '); ?> %</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div id="soloq-chart"></div>
            </div>
        </div>

        <?php } else { ?>
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-center">
                <p><?php echo $user['name']; ?> n'a pas encore de rang en SoloQ.</p>
            </div>
        </div>
        <?php } ?>
        </div>
        
        <div class="row mt-3 mb-3">
            <div class="col-12 d-flex justify-content-center">
                <?php print_title_with_expand('ELO Flex', 'elo-flex'); ?>
            </div>
        </div>

        <div id="block-elo-flex">
        <?php if (!isset($no_flex)) { ?>
        <div class="row mt-3 mb-3" data-masonry='{"percentPosition": true }'>
            <div class="col-12 offset-lg-1 col-lg-3 d-flex justify-content-center">
                <div class="row align-items-center">
                    <div class="col-12 d-flex justify-content-center">
                        <img src="elo-icons/<?php echo explode(' ', $current_flex['elo'])[0]; ?>.png" width="250">
                    </div>
                    <div class="col-12 d-flex justify-content-center">
                        <h4><?php echo $current_flex['elo']; ?></h4>
                    </div>
                    <div class="col-12 d-flex justify-content-center">
                        <h5>Winrate : <?php echo number_format(100 * $current_flex['winrate'], 1, ',', ' '); ?> %</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div id="flex-chart"></div>
            </div>
        </div>

        <?php } else { ?>
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-center">
                <p><?php echo $user['name']; ?> n'a pas encore de rang en Flex.</p>
            </div>
        </div>
        <?php } ?>
        </div>

        <?php } ?>

        <?php if ($user['auth'] & 4) { ?>
            
        <div class="row mt-5 mb-3">
            <div class="col-12 d-flex justify-content-center">
                <?php print_title_with_expand('Points de maîtrise', 'masteries'); ?>
            </div>
        </div>

        <div id="block-masteries">
        <div class="row mt-3 mb-3">
            <div class="offset-1 col-10  offset-lg-1 col-lg-10">
                <div class="table-responsive">
                    <table class="table table-hover table-dark table-bordered text-center">
                        <?php
                            $k = 0;
                            foreach ($masteries as $champion => $points) {
                                $td = '<td class="bordered-cell"><img src="' . $CHAMPIONS[strtolower($champion)]['img'] . '" width="50"><br><span class="bold">' . number_format(intval($points) / 1000, 0, ",", " ") . ' k</span>';
                                if ($k == 0) {
                                    echo "<tr>$td";
                                } elseif ($k == 3) {
                                    echo "$td</tr>";
                                } else {
                                    echo $td;
                                }
                                $k = ($k + 1) % 4;
                            }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        </div>

        <?php } ?>

        <?php if ($user['auth'] & 8) { ?>

        <div class="row mt-5 mb-3">
            <div class="col-12 d-flex justify-content-center">
                <?php print_title_with_expand('Items', 'items'); ?>
            </div>
        </div>

        <div id="block-items">
        <?php

            foreach ($items_data as $queue_id => $items) {
                echo '<div class="row mt-3 mb-3 hide items" id="items-q-' . $queue_id . '"><div class="offset-1 col-10 offset-lg-1 col-lg-4 text-center"><h4 class="mb-3">Mythiques</h4><div class="table-responsive vertical-scroll"><table id="table-mythics-q-' . $queue_id . '" class="table table-hover table-dark table-striped text-center">';

                echo '<thead class="table-dark"><tr><th scope="col" class="text-nowrap clickable">Item ↕</th><th scope="col" class="text-nowrap clickable" title="Nombre de games">Games ↕</th><th scope="col" class="text-nowrap clickable">Winrate (%) ↕</th></tr></thead>';

                echo '<tbody>';
                foreach ($items as $item_id => $item_stats) {
                    if ($ITEMS[$item_id]['type'] != 'mythic') continue;
                    echo '<tr>';
                    echo '<td scope="row" class="text-nowrap bold" data-type="items"><img src="' . $ITEMS[$item_id]['img'] . '" width="40"></td>';
                    echo '<td scope="row" class="text-nowrap" data-type="int">' . $item_stats['games_count'] . '</td>';
                    echo '<td scope="row" class="text-nowrap" data-type="float">' . number_format(100 * $item_stats['wins'] / $item_stats['games_count'], 1, ',', ' ') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';

                echo '</table></div></div>';
                echo '<div class="offset-1 col-10  offset-lg-2 col-lg-4 text-center"><h4 class="mb-3">Légendaires</h4><div class="table-responsive vertical-scroll"><table id="table-legendary-q-' . $queue_id . '" class="table table-hover table-dark table-striped text-center">';

                echo '<thead class="table-dark"><tr><th scope="col" class="text-nowrap">Item ↕</th><th scope="col" class="text-nowrap clickable" title="Nombre de games">Games ↕</th><th scope="col" class="text-nowrap clickable">Winrate (%) ↕</th></tr></thead>';

                echo '<tbody>';
                foreach ($items as $item_id => $item_stats) {
                    if ($ITEMS[$item_id]['type'] != 'legendary') continue;
                    echo '<tr>';
                    echo '<td scope="row" class="text-nowrap bold" data-type="items"><img src="' . $ITEMS[$item_id]['img'] . '" width="40"></td>';
                    echo '<td scope="row" class="text-nowrap" data-type="int">' . $item_stats['games_count'] . '</td>';
                    echo '<td scope="row" class="text-nowrap" data-type="float">' . number_format(100 * $item_stats['wins'] / $item_stats['games_count'], 1, ',', ' ') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table></div></div></div>';
            }

        ?>
        </div>

        <?php } ?>

    </body>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js" integrity="sha384-GNFwBvfVxBkLMJpYMOABq3c+d3KnQxudP/mGPkzpZSTYykLBNsZEnG2D9G/X/+7D" crossorigin="anonymous" async></script>

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
            // Tableaux champions joués
            let tables = document.getElementsByClassName('played-champions');
            for (let table of tables) {
                if (table.id == `played-champions-q-${queue}`) {
                    table.classList.remove('hide');
                } else {
                    table.classList.add('hide');
                }
            }

            // Tableaux champions adverses
            tables = document.getElementsByClassName('opponents-champions');
            for (let table of tables) {
                if (table.id == `opponents-champions-q-${queue}`) {
                    table.classList.remove('hide');
                } else {
                    table.classList.add('hide');
                }
            }
            try {
                if (queue == 450) document.getElementById('opponents-title').classList.add('hide');
                else document.getElementById('opponents-title').classList.remove('hide');
                if (queue == 450) document.getElementById('opponents-text').classList.add('hide');
                else document.getElementById('opponents-text').classList.remove('hide');
            } catch {}

            // Items
            tables = document.getElementsByClassName('items');
            for (let table of tables) {
                if (table.id == `items-q-${queue}`) {
                    table.classList.remove('hide');
                } else {
                    table.classList.add('hide');
                }
            }

        }

        change_queue(420);
    </script>

    <script src='js/sort_table.js'></script>

    <script type="text/javascript">

        var TIERS = {
            0: 'Iron',
            1: 'Bronze',
            2: 'Silver',
            3: 'Gold',
            4: 'Platine',
            5: 'Diamant',
            6: 'Master'
        };

        var DIVISIONS = {
            0: 'IV',
            1: 'III',
            2: 'II',
            3: 'I'
        };

        var master_limits = <?php echo json_encode($master_limits); ?>;

        function draw_elo_chart(type, data) {
            var x = [];
            var y = [];
            var wr = [];
            var last_digits = [];
            data.forEach(element => {
                x.push(1000 * element['time']);
                y.push(parseInt(element['lp'] / 10));
                last_digits.push(element['lp'] % 10);
                var wins = parseInt(element['wins']);
                var losses = parseInt(element['losses']);
                wr.push(100 * (wins) / (wins + losses));
            });

            var minRankY = 400 * Math.floor(Math.min(...y) / 400);
            var maxRankY = 400 * Math.ceil(Math.max(...y) / 400);
            if (minRankY == maxRankY) {
                minRankY -= 200;
                maxRankY += 200;
            }
            var rankTickAmount = Math.round((maxRankY - minRankY) / 100);

            var minWr = 10 * Math.floor(Math.min(...wr) / 10);
            var maxWr = 10 * Math.ceil(Math.max(...wr) / 10);
            if (minWr == maxWr) {
                minWr -= 5;
                maxWr += 5;
            }
            var wrTickAmout = Math.round((maxWr - minWr) / 2.5);

            var options = {
                chart: {
                    type: 'line'
                },
                series: [
                    {
                        name: 'Rang',
                        data: y
                    },
                    {
                        name: 'Winrate',
                        data: wr
                    }
                ],
                colors: [
                    '#0040ff',
                    '#2cab02'
                ],
                stroke: {
                    width: 2,
                    curve: 'stepline'
                },
                markers: {
                    size: 4
                },
                xaxis: {
                    type: 'datetime',
                    categories: x,
                    labels: {
                        format: 'dd/MM/yy',
                        style: {
                            colors: "#CCCCCC"
                        }
                    }
                },
                yaxis:[
                    {
                        title: {
                            text: 'Rang',
                            style: {
                                fontWeight: 'regular',
                                color: "#CCCCCC"
                            }
                        },
                        min: minRankY,
                        max: maxRankY,
                        tickAmount: rankTickAmount,
                        labels: {
                            formatter: function (val) {
                                if (val >= 2400) {
                                    var val_shift = val - 2400;
                                    if (val_shift > master_limits[type]['chall']) return `Challenger ${val_shift} LP`;
                                    else if (val_shift > master_limits[type]['gm']) return `Grandmaster ${val_shift} LP`;
                                    else return `Master ${val_shift} LP`;
                                } else {
                                    var tier = TIERS[Math.floor(val / 400)];
                                    var division = DIVISIONS[Math.floor((val % 400) / 100)];
                                    val = val % 100;
                                    return `${tier} ${division}`;
                                }
                            },
                            style: {
                                colors: "#CCCCCC"
                            }
                        }
                    },
                    {
                        title: {
                            text: 'Winrate',
                            style: {
                                fontWeight: 'regular',
                                color: "#CCCCCC"
                            }
                        },
                        min: minWr,
                        max: maxWr,
                        tickAmount: wrTickAmout,
                        opposite: true,
                        labels: {
                            formatter: function (val) {
                                return `${val.toFixed(0)} %`;
                            },
                            style: {
                                colors: "#CCCCCC"
                            }
                        }
                    }
                ],
                tooltip: {
                    x: {
                        format: 'dd/MM/yy'
                    },
                    y: [
                        {
                            formatter: function (val, serie) {
                                var last_digit = last_digits[serie['dataPointIndex']];
                                if (val >= 2400) {
                                    var val_shift = val - 2400;
                                    if (last_digit == 3) return `Challenger ${val_shift} LP`;
                                    else if (last_digit == 2) return `Grandmaster ${val_shift} LP`;
                                    else return `Master ${val_shift} LP`;
                                } else {
                                    if (last_digit == 1) {
                                        var tier = TIERS[Math.floor(val / 400) / 1];
                                        return `${tier} I 100 LP`;
                                    } else {
                                        var tier = TIERS[Math.floor(val / 400)];
                                        var division = DIVISIONS[Math.floor((val % 400) / 100)];
                                        val = val % 100;
                                        return `${tier} ${division} ${val} LP`;
                                    }
                                }
                            }
                        },
                        {
                            formatter: function(val) {
                                return `${val.toFixed(1)} %`;
                            }
                        }
                    ]
                },
                legend: {
                    labels: {
                        colors: "#CCCCCC"
                    }
                }
            };
            var chart = new ApexCharts(document.getElementById(`${type}-chart`), options);
            chart.render();
        }

        var soloq_data = <?php echo json_encode($ranks['soloq']); ?>;
        var flex_data = <?php echo json_encode($ranks['flex']); ?>;

        draw_elo_chart('soloq', soloq_data);
        draw_elo_chart('flex', flex_data);
    </script>

    <script type="text/javascript">
        var expand_state = {};
        function expand_collapse(id) {
            if (!id in expand_state) {
                expand_state[id] = false;
            }
            var expand = expand_state[id];
            if (expand) {
                document.getElementById(`block-${id}`).classList.remove('hide');
            } else {
                document.getElementById(`block-${id}`).classList.add('hide');
            }

            expand_state[id] = !expand;
            document.getElementById(`icon-${id}`).src = (expand_state[id] ? 'resources/expand.png' : 'resources/collapse.png');
        }
    </script>


</html>
