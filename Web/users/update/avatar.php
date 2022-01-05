<?php

    require_once('../../utils/check_token.php');

    if (!array_key_exists('user_id', $_GET)) {
        echo '{"status":{"message":"Bad Request - No user specified","status_code":400}}';
        exit;
    }

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('avatar', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain avatar","status_code":400}}';
        exit;
    }

    require_once('../../utils/bdd.php');

    $request = $bdd->prepare('SELECT `id` FROM `et2_users` WHERE `user_id`=?');
    $request->execute(array($_GET['user_id']));
    $row = $request->fetch();

    if (!is_array($row)) {
        echo '{"status":{"message":"Bad request - User does not exist","status_code":400}}';
    } else {
        $request = $bdd->prepare('UPDATE `et2_users` SET `avatar`=? WHERE `user_id`=?');
        $request->execute(array($DATA['avatar'], $_GET['user_id']));
        echo '{"status":{"message":"User avatar updated","status_code":200}}';
    }


?>