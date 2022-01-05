<?php

    require_once('../utils/check_token.php');

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('items', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain items","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    foreach ($DATA['items'] as $id => $item) {
        $request = $bdd->prepare('REPLACE INTO `et2_items` VALUES (?, ?, ?, ?)');
        $request->execute(array(intval($id), $item['type'], $item['name'], $item['name_fr']));
    }

    echo '{"status":{"message":"Items updated","status_code":200}}';


?>