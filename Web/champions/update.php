<?php

    require_once('../utils/check_token.php');

    $DATA = json_decode(file_get_contents('php://input'), TRUE);

    if (!array_key_exists('champions', $DATA)) {
        echo '{"status":{"message":"Bad Request - Data does not contain champions","status_code":400}}';
        exit;
    }

    require_once('../utils/bdd.php');

    foreach ($DATA['champions'] as $id => $names) {
        $request = $bdd->prepare('REPLACE INTO `et2_champions` VALUES (?, ?, ?)');
        $request->execute(array(intval($id), $names[0], $names[1]));
    }

    echo '{"status":{"message":"Champions updated","status_code":200}}';


?>