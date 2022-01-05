<?php

    require_once('utils/bdd.php');
    require_once('utils/functions.php');

    $START_TIME = '1637585729';

    if (isset($_POST['add'])) {
        if (hash('sha256', $_POST['password']) === 'b82c4b94733d9338f4d061c688945ac86a80bf05fe49721c6bef6688d4e0c6cf') {
            $request = $bdd->prepare('INSERT INTO `et2_users`(`new`, `user_id`, `puuid`, `summoner_id`, `time`, `discord_id`, `name`) VALUES (?, ?, "", "", ?, ?, ?)');
            $request->execute(array($_POST['lol_name'], generate_new_user_id($bdd), $START_TIME, $_POST['discord_id'], $_POST['name']));
            echo '<p>\'' . $_POST['name'] . '\' ajouté ! </p>';
        } else {
            echo '<p>Mot de passe incorrect</p>';
        }
    }

?>


<!DOCTYPE html>
<html>
<head>
<title>Éternels de Demacia - Nouveau Demacien - V3</title>
<link rel="icon" href="icon.png">
</head>
<body>

    <form method="post">
        <p>Pseudo : <input type="text" name="name"></p>
        <p>Pseudo LoL : <input type="text" name="lol_name"></p>
        <p>ID Discord: <input type="text" name="discord_id"></p>
        <p>Mot de passe : <input type="password" name="password"></p>
        <input type="submit" name="add" value="Ajouter">
    </form>
    <hr>
    <p>V1</p>

</body>
</html>