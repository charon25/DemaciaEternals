<?php

    $TOKEN_HASH = '';

    if (!array_key_exists('HTTP_TOKEN', $_SERVER) || !password_verify($_SERVER['HTTP_TOKEN'],  $TOKEN_HASH)) {
        echo '{"status":{"message":"Unauthorized","status_code":401}}';
        exit;
    }

