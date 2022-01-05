<?php

    require_once('../utils/check_token.php');

    require_once('../utils/bdd.php');

    $request = $bdd->query('SELECT `id`, `type`, `name_fr` FROM `et2_items`');

    $output = array();
    while ($row = $request->fetch()) {
        $req = $bdd->prepare('SELECT COUNT(*) FROM `et2_matchs` WHERE `items` LIKE ?');
        $req->execute(array('%' . $row['id'] . '%'));

        $count = $req->fetch()['COUNT(*)'];
        if ($count == 0) continue;
        $output[$row['id']] = array(
            'type' => $row['type'],
            'name' => $row['name_fr'],
            'games_count' => $count
        );
    }

    echo json_encode($output);

?>