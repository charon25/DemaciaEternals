<?php

    session_start();

    if (isset($_POST['disconnect'])) {
        $_SESSION = array();
    }

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ignore_user_abort(true);

    require_once('utils/bdd.php');
    require_once('utils/functions.php');
    require_once('utils/get_request.php');
    require_once('utils/riot-api.php');

    $KEY = '';
    $riotapi = new RiotAPI($KEY);

    $COST = 10;

    $NEW_PLAYER_TIME = 1641373200;

    $_SESSION['state'] = $_SESSION['state'] ?? 0;

    if (isset($_POST['authentify'])) {
        $user_pw = get_userid_from_password($bdd, $_POST['password']);
        if (!$user_pw) {
            $_SESSION['state'] = 1;
        } else {
            $user = get_user($bdd, $user_pw['user_id']);
            $_SESSION['user_id'] = $user_pw['user_id'];
            $_SESSION['discord_id'] = $user_pw['discord_id'];
            if ($user) {
                $_SESSION['state'] = 3;
                $_SESSION['name'] = $user['name'];
                $_SESSION['main_summoner_name'] = $riotapi->get_summoner_name_from_id($user['summoner_id']);
                $_SESSION['smurfs_summoner_names'] = array();
                foreach (explode(',', $user['smurfs_sid']) as $_ => $smurf_id) {
                    usleep(50 * 1000);
                    if (strlen($smurf_id) > 10) {
                        $_SESSION['smurfs_summoner_names'][] = $riotapi->get_summoner_name_from_id($smurf_id);
                    }
                }
                $_SESSION['auth'] = intval($user['auth']);
                $_SESSION['display'] = intval($user['display']);
            } else {
                $_SESSION['state'] = 2;
                $_SESSION['name'] = $user_pw['temp_name'];
                $_SESSION['main_summoner_name'] = '';
                $_SESSION['smurfs_summoner_names'] = array();
                $_SESSION['auth'] = 255;
                $_SESSION['display'] = 1;
            }
        }
    }

    if (isset($_POST['submit_showname'])) {
        $_POST['showname'] = trim($_POST['showname']);
        if ($_SESSION['name'] == $_POST['showname']) {
            // Rien
        } elseif (preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9 '-]+$/i", $_POST['showname']) && strlen($_POST['showname']) <= 24) {
            $request = $bdd->prepare('UPDATE `et2_users` SET `name`=? WHERE `user_id`=?');
            $request->execute(array($_POST['showname'], $_SESSION['user_id']));
            $_SESSION['name'] = $_POST['showname'];
            $showname_result = TRUE;
        } else {
            $showname_result = FALSE;
        }
    }

    if (isset($_POST['submit_main_summoner_name'])) {
        $_POST['main_summoner_name'] = trim($_POST['main_summoner_name']);
        if ($_SESSION['main_summoner_name'] == $_POST['main_summoner_name']) {
            // Rien
        } elseif (!preg_match("/[.!,@$*:;^?()[\]\\-]/i", $_POST['main_summoner_name']) && strlen($_POST['main_summoner_name']) <= 16) {
            $summoner = $riotapi->get_puuid_id_from_name($_POST['main_summoner_name']);
            if ($summoner) {
                $request = $bdd->prepare('UPDATE `et2_users` SET `puuid`=?, `summoner_id`=? WHERE `user_id`=?');
                $request->execute(array($summoner['puuid'], $summoner['summoner_id'], $_SESSION['user_id']));
                $_SESSION['main_summoner_name'] = $_POST['main_summoner_name'];
                $main_summoner_name_result = TRUE;
            } else {
                $main_summoner_name_result = FALSE;
            }
        } else {
            $main_summoner_name_result = FALSE;
        }
    }

    if (isset($_POST['submit_smurfs_summoner_names'])) {
        $smurfs_count = 0;
        $_SESSION['smurfs_summoner_names'] = array();
        $smurfs_sid = array();
        foreach (explode("\n", $_POST['smurfs_summoner_names']) as $_ => $smurf_name) {
            $smurf_name = trim($smurf_name);
            if (!preg_match("/[.!,@$*:;^?()[\]\\-]/i", $smurf_name) && strlen($smurf_name) <= 16) {
                $smurf = $riotapi->get_puuid_id_from_name($smurf_name);
                if ($smurf) {
                    $smurfs_sid[] = $smurf['summoner_id'];
                    $_SESSION['smurfs_summoner_names'][] = $smurf_name;
                    $smurfs_count++;
                }
            }
        }

        $request = $bdd->prepare('UPDATE `et2_users` SET `smurfs_sid`=? WHERE `user_id`=?');
        $request->execute(array(implode(',', $smurfs_sid), $_SESSION['user_id']));
    }

    if (isset($_POST['submit_auth'])) {
        $auth = 0;
        for ($i = 0; $i < 6; $i++) { 
            $checkbox = "auth_$i";
            if (isset($_POST[$checkbox])) {
                $auth += pow(2, $i);
            }
        }

        $request = $bdd->prepare('UPDATE `et2_users` SET `auth`=? WHERE `user_id`=?');
        $request->execute(array($auth, $_SESSION['user_id']));
        $_SESSION['auth'] = $auth;
        $auth_result = TRUE;
    }

    if (isset($_POST['submit_display_0'])) {
        $request = $bdd->prepare('UPDATE `et2_users` SET `display`=0 WHERE `user_id`=?');
        $request->execute(array($_SESSION['user_id']));
        $_SESSION['display'] = 0;
    }
    if (isset($_POST['submit_display_1'])) {
        $request = $bdd->prepare('UPDATE `et2_users` SET `display`=1 WHERE `user_id`=?');
        $request->execute(array($_SESSION['user_id']));
        $_SESSION['display'] = 1;
    }

    if (isset($_POST['create_profile'])) {
        $showname_result = (preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9 '-]+$/i", $_POST['showname']) && strlen($_POST['showname']) <= 24);
        $main_summoner_name_result = (!preg_match("/[.!,@$*:;^?()[\]\\-]/i", $_POST['main_summoner_name']) && strlen($_POST['main_summoner_name']) <= 16);
        if ($main_summoner_name_result) {
            $summoner = $riotapi->get_puuid_id_from_name($_POST['main_summoner_name']);
            $main_summoner_name_result = $main_summoner_name_result && boolval($summoner);
        }

        $auth = 0;
        for ($i = 0; $i < 6; $i++) { 
            $checkbox = "auth_$i";
            if (isset($_POST[$checkbox])) {
                $auth += pow(2, $i);
            }
        }

        if ($showname_result && $main_summoner_name_result) {
            $request = $bdd->prepare('INSERT INTO `et2_users` (`new`, `user_id`, `puuid`, `summoner_id`, `time`, `discord_id`, `avatar`, `name`, `auth`, `display`, `smurfs_sid`) VALUES ("", ?, ?, ?, ?, ?, "", ?, ?, 1, "")');
            $request->execute(array(
                $_SESSION['user_id'],
                $summoner['puuid'],
                $summoner['summoner_id'],
                $NEW_PLAYER_TIME,
                $_SESSION['discord_id'],
                $_POST['showname'],
                $auth,
            ));
            $_SESSION['name'] = $_POST['showname'];
            $_SESSION['main_summoner_name'] = $_POST['main_summoner_name'];
            $_SESSION['auth'] = $auth;
            $_SESSION['state'] = 3;
            $account_created = TRUE;
            unset($showname_result);
            unset($main_summoner_name_result);
        }

    }

?>

<!DOCTYPE html>
<html lang='fr'>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Éternels 2 de Demacia - Page personnelle</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="#">
    </head>
    <body class="cfont container-fluid">

        <?php if ($_SESSION['state'] <= 1) { ?>

            <div class="row mt-2 mb-3">
                <div class="col-12 col-sm-12 d-flex justify-content-center text-center"><h1>Accès page personnelle</h1></div>
            </div>
            <div class="row mb-3">
                <div class="offset-lg-4 col-12 col-lg-4 text-center">
                    <a href=".">
                        <button class="btn btn-success auth-btn" type="button">← Retour à l'accueil</button>
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-lg-4 offset-lg-4 text-center">
                    <div class="panel panel-default">
                        <form action="" method="post">
                            <p>Mot de passe :   <input type="password" name="password"></p>
                            <?php if ($_SESSION['state'] == 1) { ?>
                                <p class="wrong-password">Mot de passe incorrect.</p>
                            <?php } ?>
                            <p class="mb-2">Si vous l'avez oublié, consultez vos DM avec le Bot Hextech.</p>
                            <button type="submit" class="btn btn-primary auth-btn mt-2" name="authentify">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php } elseif ($_SESSION['state'] == 3) { ?>

            <div class="row mt-2 mb-3">
                <div class="col-12 col-sm-12 d-flex justify-content-center text-center"><h1>Page personnelle - <?php echo $_SESSION['name']; ?></h1></div>
            </div>
            <div class="row mb-3">
                <div class="offset-1 offset-lg-3 col-5 col-lg-3">
                    <form class="no-margin-bottom">
                        <a href=".">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" type="button">← Retour à l'accueil</button>
                            </div>
                        </a>
                    </form>
                </div>
                <div class="col-5 col-lg-3">
                    <form action="" method="post" class="no-margin-bottom">
                        <div class="d-grid gap-2">
                            <button class="btn btn-secondary" type="submit" name="disconnect">Déconnexion</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="offset-lg-3 col-12 col-lg-6">
                    <form action="" method="post">
                        <div class="d-grid gap-2">
                            <?php
                                if ($_SESSION['display'] == 0) {
                                    echo '<button class="btn btn-primary" type="submit" name="submit_display_1">Réapparaître sur le site</button>';
                                } else {
                                    echo '<button class="btn mb-4 btn-danger" type="submit" name="submit_display_0">Ne plus apparaître sur le site</button>';
                                }
                            ?>
                        </div>
                    </form>
                    <?php if (isset($account_created)) { ?>
                        <div class="text-center" id="profile_created">
                            <h4 class="mb-4 form-success">Profil créé !</h4>
                        </div>
                    <?php } ?>
                    <?php if ($_SESSION['display'] == 1) { ?>
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="showname">Nom à afficher</label>
                                <input type="text" class="form-control" id="showname" name="showname" value="<?php echo $_SESSION['name']; ?>">
                                <?php
                                    if (isset($showname_result)) {
                                        if ($showname_result) echo '<p class="form-success mt-2">Nom mis à jour!</p>';
                                        else echo '<p class="form-error mt-2">Le nom est trop long ou contient des caractères invalides.</p>';
                                    }
                                ?>
                                <button class="btn btn-primary mt-3" type="submit" name="submit_showname">Mettre à jour</button>
                            </div>
                        </form>
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="main_summoner_name">Pseudo LoL du compte principal</label>
                                <input type="text" class="form-control" id="main_summoner_name" name="main_summoner_name" value="<?php echo $_SESSION['main_summoner_name']; ?>">
                                <?php
                                    if (isset($main_summoner_name_result)) {
                                        if ($main_summoner_name_result) echo '<p class="form-success mt-2">Pseudo principal mis à jour!</p>';
                                        else echo '<p class="form-error mt-2">Ce pseudo n\'existe pas.</p>';
                                    }
                                ?>
                                <small class="form-text">Compte utilisé pour les statistiques et l'ELO.</small><br>
                                <button class="btn btn-primary mt-3" type="submit" name="submit_main_summoner_name">Mettre à jour</button>
                            </div>
                        </form>
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="smurfs_summoner_names">Pseudos LoL des smurfs (un par ligne ; optionnel)</label>
                                <textarea type="text" class="form-control" id="smurfs_summoner_names" name="smurfs_summoner_names" rows="4"><?php echo implode("\n", $_SESSION['smurfs_summoner_names']); ?></textarea>
                                <?php
                                    if (isset($smurfs_count)) {
                                        if ($smurfs_count > 1) echo '<p class="form-success mt-2">' . $smurfs_count . ' smurfs ajoutés !</p>';
                                        elseif ($smurfs_count == 1) echo '<p class="form-success mt-2">1 smurf ajouté !</p>';
                                        else echo '<p class="form-error mt-2">Aucun des pseudos rentrés n\'existe.</p>';
                                    }
                                ?>
                                <small class="form-text">Uniquement utilisés pour les points de maîtrise.</small><br>
                                <button class="btn btn-primary mt-3" type="submit" name="submit_smurfs_summoner_names">Mettre à jour</button>
                            </div>
                        </form>
                        <form action="" method="post">
                            <p class="mt-2" style="font-weight: bold;">Choix des données affichées sur sa page et dans les classements :</p>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 1) > 0 ? 'checked' : ''); ?> name="auth_0" id="auth_0">
                                <label class="form-check-label" for="auth_0">Afficher les statistiques générales</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 2) > 0 ? 'checked' : ''); ?> name="auth_1" id="auth_1">
                                <label class="form-check-label" for="auth_1">Afficher l'ELO</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 4) > 0 ? 'checked' : ''); ?> name="auth_2" id="auth_2">
                                <label class="form-check-label" for="auth_2">Afficher les points de maîtrises</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 8) > 0 ? 'checked' : ''); ?> name="auth_3" id="auth_3">
                                <label class="form-check-label" for="auth_3">Afficher les statistiques d'items</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 16) > 0 ? 'checked' : ''); ?> name="auth_4" id="auth_4">
                                <label class="form-check-label" for="auth_4">Apparaître dans les classements de Demaciens</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 32) > 0 ? 'checked' : ''); ?> name="auth_5" id="auth_5">
                                <label class="form-check-label" for="auth_5">Apparaître dans le classement des points de maîtrise</label>
                            </div>
                            <?php
                                if (isset($auth_result)) echo '<p class="form-success mt-2">Données affichées mises à jour !</p>';
                            ?>
                            <button class="btn btn-primary mt-3" type="submit" name="submit_auth">Mettre à jour</button>
                        </form>
                    <?php } ?>
                </div>
            </div>

        <?php } elseif ($_SESSION['state'] == 2) { ?>

            <div class="row mt-2 mb-3">
                <div class="col-12 col-sm-12 d-flex justify-content-center text-center"><h1>Création profil - <?php echo $_SESSION['name']; ?></h1></div>
            </div>
            <div class="row mb-3">
                <div class="offset-1 offset-lg-3 col-5 col-lg-3 d-grid gap-2">
                    <form class="no-margin-bottom">
                        <a href=".">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" type="button">← Retour à l'accueil</button>
                            </div>
                        </a>
                    </form>
                </div>
                <div class="col-5 col-lg-3 d-grid gap-2">
                    <form action="" method="post">
                        <div class="d-grid gap-2">
                            <button class="btn btn-secondary" type="submit" name="disconnect">Déconnexion</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php if ($_SESSION['display'] == 1) { ?>
                <form action="" method="post">
                    <div class="row">
                        <div class="offset-lg-3 col-12 col-lg-6">
                            <div class="form-group">
                                <label for="showname">Nom à afficher</label>
                                <input type="text" class="form-control" id="showname" name="showname" value="<?php echo $_SESSION['name']; ?>">
                                <?php
                                    if (isset($showname_result) && !$showname_result) echo '<p class="form-error mt-2">Le nom est trop long ou contient des caractères invalides.</p>'
                                ?>
                            </div>
                            <div class="form-group">
                                <label for="main_summoner_name">Pseudo LoL du compte principal</label>
                                <input type="text" class="form-control" id="main_summoner_name" name="main_summoner_name" value="<?php echo $_SESSION['main_summoner_name']; ?>">
                                <?php
                                    if (isset($main_summoner_name_result) && !$main_summoner_name_result) echo '<p class="form-error mt-2">Ce pseudo n\'existe pas.</p>';
                                ?>
                                <small class="form-text">Compte utilisé pour les statistiques et l'ELO.</small><br>
                            </div>
                            <p class="mt-2" style="font-weight: bold;">Choix des données affichées sur sa page et dans les classements :</p>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 1) > 0 ? 'checked' : ''); ?> name="auth_0" id="auth_0">
                                <label class="form-check-label" for="auth_0">Afficher les statistiques générales</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 2) > 0 ? 'checked' : ''); ?> name="auth_1" id="auth_1">
                                <label class="form-check-label" for="auth_1">Afficher l'ELO</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 4) > 0 ? 'checked' : ''); ?> name="auth_2" id="auth_2">
                                <label class="form-check-label" for="auth_2">Afficher les points de maîtrises</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 8) > 0 ? 'checked' : ''); ?> name="auth_3" id="auth_3">
                                <label class="form-check-label" for="auth_3">Afficher les statistiques d'items</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 16) > 0 ? 'checked' : ''); ?> name="auth_4" id="auth_4">
                                <label class="form-check-label" for="auth_4">Apparaître dans les classements de Demaciens</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" <?php echo (($_SESSION['auth'] & 32) > 0 ? 'checked' : ''); ?> name="auth_5" id="auth_5">
                                <label class="form-check-label" for="auth_5">Apparaître dans le classement des points de maîtrise</label>
                            </div>
                            <?php
                                if (isset($auth_result)) echo '<p class="form-success mt-2">Données affichées mises à jour !</p>';
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="offset-lg-3 col-12 col-lg-6 text-center">
                            <button class="btn btn-primary mt-4 auth-btn" type="submit" name="create_profile">Créer le profil</button>
                        </div>
                    </div>
                </form>
            <?php } ?>
        
        <?php } ?>

    </body>


    <script type="text/javascript">

        document.getElementById('profile_created').onclick = function() {
            document.getElementById('profile_created').parentElement.removeChild(document.getElementById('profile_created'));
        }

    </script>

</html>