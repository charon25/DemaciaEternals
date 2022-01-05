<?php

    require_once('utils/check_token.php');

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('limits', $DATA)) {
        echo '{"status":{"message":"Bad Request - No limits specified","status_code":400}}';
        exit;
    }

    file_put_contents('master-limits.txt', json_encode($DATA['limits']));

    echo '{"status":{"message":"Master limites updated","status_code":200}}';


?>