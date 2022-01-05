<?php

    $TOKEN = '';

    if (!array_key_exists('HTTP_TOKEN', $_SERVER) || $_SERVER['HTTP_TOKEN'] !== $TOKEN) {
        echo '{"status":{"message":"Unauthorized","status_code":401}}';
        exit;
    }

