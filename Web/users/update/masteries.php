<?php

    require_once('../../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('masteries', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain masteries","status_code":400}}';
        exit;
    }

    require_once('../../utils/bdd.php');

    $request = $bdd->prepare('SELECT `id` FROM `et2_masteries` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));
    $row = $request->fetch();

    if (!is_array($row)) {
        $request = $bdd->prepare('INSERT INTO `et2_masteries`(`user_id`, `masteries`) VALUES (?, ?)');
        $request->execute(array($_GET['user_id'], json_encode($DATA['masteries'])));
        echo '{"status":{"message":"User created","status_code":200}}';
    } else {
        $request = $bdd->prepare('UPDATE `et2_masteries` SET `masteries`=? WHERE `user_id`=?');
        $request->execute(array(json_encode($DATA['masteries']), $_GET['user_id']));
        echo '{"status":{"message":"User updated","status_code":200}}';
    }


?>