<?php

    require_once('utils/check_token.php');

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('patch', $DATA)) {
        echo '{"status":{"message":"Bad Request - No patch specified","status_code":400}}';
        exit;
    }

    file_put_contents('patch.txt', $DATA['patch']);

    echo '{"status":{"message":"Patch updated","status_code":200}}';

?>