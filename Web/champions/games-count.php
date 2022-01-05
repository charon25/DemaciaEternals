<?php

    require_once('../utils/check_token.php');

    require_once('../utils/bdd.php');

    $request = $bdd->query('SELECT `name` FROM `et2_champions`');

    $output = array();
    while ($row = $request->fetch()) {
        $req = $bdd->prepare('SELECT COUNT(*) FROM `et2_matchs` WHERE `champion`=?');
        $req->execute(array($row['name']));
        $output[$row['name']] = array(
            'games_count' => $req->fetch()['COUNT(*)']
        );
    }

    echo json_encode($output);

?>