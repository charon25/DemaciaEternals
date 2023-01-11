<?php

    require_once('../utils/check_token.php');

    require_once('../utils/bdd.php');

    if (!array_key_exists('user_id', $_GET)) {
        $request = $bdd->query('SELECT * FROM `et2_users`');

        $output = array();
        while ($row = $request->fetch()) {
            $output[] = array(
                'new' => $row['new'],
                'user_id' => $row['user_id'],
                'puuid' => $row['puuid'],
                'summoner_id' => $row['summoner_id'],
                'time' => $row['time'],
                'discord_id' => $row['discord_id'],
                'name' => $row['name'],
                'smurfs_sid' => $row['smurfs_sid']
            );
        }

        echo json_encode($output);

    } else {
        $request = $bdd->prepare('SELECT * FROM `et2_users` WHERE `user_id`=?');
        $request->execute(array($_GET['user_id']));
        $user = $request->fetch();
        $output = array(
            'new' => $user['new'],
            'user_id' => $user['user_id'],
            'puuid' => $user['puuid'],
            'summoner_id' => $user['summoner_id'],
            'time' => $user['time'],
            'discord_id' => $user['discord_id'],
            'name' => $user['name'],
            'smurfs_sid' => $row['smurfs_sid']
        );

        echo json_encode($output);
    }


?>