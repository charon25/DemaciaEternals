<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

    require_once('utils/bdd.php');
    require_once('utils/functions.php');
    require_once('utils/get_request.php');

    // USERS
    $users = get_all_users($bdd, TRUE);
    $users_games_count = get_users_games_count($bdd);

    function cmp_users_by_games($user1, $user2) {
        global $users_games_count;
        if ($users_games_count[$user1['user_id']]['games_count'] > $users_games_count[$user2['user_id']]['games_count']) return -1;
        elseif ($users_games_count[$user1['user_id']]['games_count'] < $users_games_count[$user2['user_id']]['games_count']) return 1;
        else return strcmp($user1['name'], $user2['name']);
    }

    $users_by_games = $users;
    usort($users_by_games, 'cmp_users_by_games');

    // CHAMPIONS
    $PATCH = file_get_contents('patch.txt');
    $champions = get_all_champions($bdd, $PATCH, TRUE);
    $champions_games_count = get_champions_games_count($bdd);

    function has_games_champion($champion) {
        global $champions_games_count;
        return $champions_games_count[$champion['name']]['games_count'] > 0;
    }
    $champions = array_filter($champions, 'has_games_champion');

    function cmp_champ_by_games($champion1, $champion2) {
        global $champions_games_count;
        if ($champions_games_count[$champion1['name']]['games_count'] > $champions_games_count[$champion2['name']]['games_count']) return -1;
        elseif ($champions_games_count[$champion1['name']]['games_count'] < $champions_games_count[$champion2['name']]['games_count']) return 1;
        else return strcmp($champion1['showname'], $champion2['showname']);
    }

    $champions_by_games = $champions;
    usort($champions_by_games, 'cmp_champ_by_games');


?>

<!DOCTYPE html>
<html lang='fr'>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Éternels 2 de Demacia</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="#">
    </head>

    <body class="cfont container-fluid">
        <div class="row mt-2 mb-3">
            <div class="col-12 col-sm-12 d-flex justify-content-center text-center"><h1>Éternels 2 de Demacia</h1></div>
        </div>

        <div class="row mt-3 mb-3">
            <div class="offset-1 offset-lg-4 col-10 col-lg-4">
                <a href="ranking">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-block" type="button">Classement global des Demaciens →</button>
                    </div>
                </a>
            </div>
        </div>
        <div class="row mt-3 mb-3">
            <div class="offset-1 offset-lg-4 col-10 col-lg-4">
                <a href="account">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-block" type="button">Connexion à son profil →</button>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12 col-sm-12 d-flex justify-content-center"><h3>Filtres</h3></div>
        </div>

        <div class="row mt-1 mb-1">
            <div class="offset-1 offset-lg-4 col-5 col-lg-2 d-grid gap-2">
                <button class="btn btn-dark mt-1 mb-1 type-button" id="btn-type-users" onclick="change('type', 'users');" type="button">Demaciens</button>
            </div>
            <div class="col-5 col-lg-2 d-grid gap-2">
                <button class="btn btn-dark mt-1 mb-1 type-button" id="btn-type-champions" onclick="change('type', 'champions');" type="button">Champions</button>
            </div>
        </div>
        <div class="row mt-1 mb-3">
            <div class="offset-1 offset-lg-4 col-5 col-lg-2 d-grid gap-2">
                <button class="btn btn-dark mt-1 mb-1 order-button" id="btn-order-alpha" onclick="change('order', 'alpha');" type="button">Alphabétique</button>
            </div>
            <div class="col-5 col-lg-2 d-grid gap-2">
                <button class="btn btn-dark mt-1 mb-1 order-button" id="btn-order-games" onclick="change('order', 'games');" type="button">Nombre de games</button>
            </div>
        </div>

        <div class="type-div hide" id="type-users">
        <div class="row mt-3 mb-3">
            <div class="col-12 col-sm-12 d-flex justify-content-center"><h3>Statistiques des Demaciens</h3></div>
        </div>
        
        <div class="row mt-3 mb-5 order-div" id="order-alpha">
                <?php
                    $COLS = 5;
                    $k = 0;
                    foreach ($users as $_ => $user) {
                        echo '<div class="col-4 col-lg-2 d-flex justify-content-center pt-2 pb-1 ' . ($k % 12 < 6 ? 'main-table-even' : 'main-table-odd') . ' bordered' . ($k < 6 ? ' top-bordered-lg' . ($k < 3 ? ' top-bordered' : '') : '') . ($k % 6 == 3 ? ' left-bordered-no-lg' : '') . ($k % 6 == 0 ? ' left-bordered' : '') . '"><a class="text-center" href="user?user_id=' . $user['user_id'] . '"><img src="' . $user['img'] . '"><br>' . $user['name'] . '<br>' . $users_games_count[$user['user_id']]['games_count'] . ' games</a></div>';
                        $k++;
                    }
                ?>
        </div>
        
        <div class="row mt-3 mb-5 order-div" id="order-games">
                <?php
                    $COLS = 5;
                    $k = 0;
                    foreach ($users_by_games as $_ => $user) {
                        echo '<div class="col-4 col-lg-2 d-flex justify-content-center pt-2 pb-1 ' . ($k % 12 < 6 ? 'main-table-even' : 'main-table-odd') . ' bordered' . ($k < 6 ? ' top-bordered-lg' . ($k < 3 ? ' top-bordered' : '') : '') . ($k % 6 == 3 ? ' left-bordered-no-lg' : '') . ($k % 6 == 0 ? ' left-bordered' : '') . '"><a class="text-center" href="user?user_id=' . $user['user_id'] . '"><img src="' . $user['img'] . '"><br>' . $user['name'] . '<br>' . $users_games_count[$user['user_id']]['games_count'] . ' games</a></div>';
                        $k++;
                    }
                ?>
        </div>
        </div>

        <div class="hide type-div" id="type-champions">
        <div class="row mt-3 mb-3">
            <div class="col-12 col-sm-12 d-flex justify-content-center"><h3>Statistiques des champions</h3></div>
        </div>

        <div class="row mt-3 mb-5 order-div" id="order-alpha">
                <?php
                    $COLS = 5;
                    $k = 0;
                    foreach ($champions as $_ => $champion) {
                        echo '<div class="col-4 col-lg-2 d-flex justify-content-center pt-2 pb-1 ' . ($k % 12 < 6 ? 'main-table-even' : 'main-table-odd') . ' bordered' . ($k < 6 ? ' top-bordered-lg' . ($k < 3 ? ' top-bordered' : '') : '') . ($k % 6 == 3 ? ' left-bordered-no-lg' : '') . ($k % 6 == 0 ? ' left-bordered' : '') . '"><a class="text-center" href="ranking?champion=' . $champion['name'] . '"><img width="50" src="' . $champion['img'] . '"><br>' . $champion['showname'] . '<br>' . $champions_games_count[$champion['name']]['games_count'] . ' games</a></div>';
                        $k++;
                    }
                ?>
        </div>

        
        <div class="row mt-3 mb-5 order-div" id="order-games">
                <?php
                    $COLS = 5;
                    $k = 0;
                    foreach ($champions_by_games as $_ => $champion) {
                        echo '<div class="col-4 col-lg-2 d-flex justify-content-center pt-2 pb-1 ' . ($k % 12 < 6 ? 'main-table-even' : 'main-table-odd') . ' bordered' . ($k < 6 ? ' top-bordered-lg' . ($k < 3 ? ' top-bordered' : '') : '') . ($k % 6 == 3 ? ' left-bordered-no-lg' : '') . ($k % 6 == 0 ? ' left-bordered' : '') . '"><a class="text-center" href="ranking?champion=' . $champion['name'] . '"><img width="50" src="' . $champion['img'] . '"><br>' . $champion['showname'] . '<br>' . $champions_games_count[$champion['name']]['games_count'] . ' games</a></div>';
                        $k++;
                    }
                ?>
        </div>
        </div>

    </body>

    <script type="text/javascript">
        function change(category, value) {
            var buttons = document.getElementsByClassName(`${category}-button`);
            for (let button of buttons) {
                if (button.id == `btn-${category}-${value}`) {
                    button.classList.remove('btn-dark');
                    button.classList.add('btn-primary');
                } else {
                    button.classList.add('btn-dark');
                    button.classList.remove('btn-primary');
                }
            }

            var divs = document.getElementsByClassName(`${category}-div`);
            for (let div of divs) {
                if (div.id == `${category}-${value}`) {
                    div.classList.remove('hide');
                } else {
                    div.classList.add('hide');
                }
            }
        }

        change('type', 'users');
        change('order', 'alpha');
    </script>

</html>