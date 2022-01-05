<?php

    require_once('../utils/check_token.php');

    require_once('../utils/bdd.php');

    $request = $bdd->query('SELECT `user_id`, `name` FROM `et2_users`');

    $output = array();
    while ($row = $request->fetch()) {
        $req = $bdd->prepare('SELECT COUNT(*) FROM `et2_matchs` WHERE `user_id`=?');
        $req->execute(array($row['user_id']));
        $output[$row['user_id']] = array(
            'name' => $row['name'],
            'games_count' => $req->fetch()['COUNT(*)']
        );
    }

    echo json_encode($output);

?>