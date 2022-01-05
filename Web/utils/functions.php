<?php

    function generate_new_user_id($bdd) {
        do {
            $user_id = bin2hex(openssl_random_pseudo_bytes(4));
            $request = $bdd->prepare('SELECT `id` FROM `et2_users` WHERE `user_id`=?');
            $request->execute(array($user_id));
            if (!is_array($request)) break;
        } while (True);

        return $user_id;
    }

    function compute_stats_raw($game, $stat) {
        if ($stat['type'] == 0) {
            $div = explode('/', $stat['calculation']);
            if ($game[$div[1]] === 0) return 0;
            return $game[$div[0]] / $game[$div[1]];
        } elseif ($stat['type'] == 1) {
            return $game[$stat['calculation']];
        } elseif ($stat['type'] == 2) {
            return $game[$stat['calculation']] / ($game['duration'] / 60);
        } else {
            if ($stat['symbol'] === 'KDA') {
                if ($game['deaths'] == 0) return $game['kills'] + $game['assists'];
                return ($game['kills'] + $game['assists']) / $game['deaths'];
            } elseif ($stat['symbol'] === 'KP (%)') {
                if ($game['total_kills'] == 0) return 0;
                return ($game['kills'] + $game['assists']) / $game['total_kills'];
            } elseif ($stat['symbol'] === "Durée (min)") {
                return $game['duration'] / 60;
            }
        }
    }

    function compute_stat($game, $stat) {
        $raw_value = compute_stats_raw($game, $stat);
        if (strpos($stat['symbol'], '%') === FALSE) return $raw_value;
        return 100 * $raw_value;
    }

    $TIERS = array(
        0 => 'Iron',
        1 => 'Bronze',
        2 => 'Silver',
        3 => 'Gold',
        4 => 'Platine',
        5 => 'Diamant',
        6 => 'Master'
    );
    $DIVISIONS = array(
        0 => 'IV',
        1 => 'III',
        2 => 'II',
        3 => 'I'
    );

    function get_ranks_from_lp($lp) {
        global $TIERS, $DIVISIONS;
        $last_digit = $lp % 10;
        $lp = intdiv($lp, 10);

        if ($lp > 2400 || ($lp == 2400 && $last_digit != 1)) {
            $lp -= 2400;
            if ($last_digit == 3) {
                return 'Challenger ' . $lp . ' LP';
            } elseif ($last_digit == 2) {
                return 'Grandmaster ' . $lp . ' LP';
            } else {
                return 'Master ' . $lp . ' LP';
            }
        } else {
            if ($last_digit == 1) {
                $tier = $TIERS[intdiv($lp, 400) - 1];
                $division = 'I';
                $lp = 100;
            } else {
                $tier = $TIERS[intdiv($lp, 400)];
                $division = $DIVISIONS[intdiv($lp % 400, 100)];
                $lp = $lp % 100;
            }
            return $tier . ' ' . $division . ' ' . $lp . ' LP';
        }
    }

    function get_winrate($wins, $losses) {
        return $wins / ($wins + $losses);
    }

    function print_title_with_expand($title, $id) {
        echo '<div><img id="icon-' . $id . '" src="resources/collapse.png" width="16" height="16" class="expand-icon clickable" onclick="expand_collapse(\'' . $id . '\')"><h3 class="h3-title"> ' . $title . '</h3></div>';
    }

    function get_all_stats($bdd) {
        $request = $bdd->query('SELECT * FROM `et2_stats`');
        $stats = array();
        while ($row = $request->fetch()) {
            $stats[$row['symbol']] = $row;
        }
        return $stats;
    }

    function user_cmp($user1, $user2) {
        return strcmp($user1['name'], $user2['name']);
    }


    // Ne renvoie pas les users avec display=0
    function get_all_users($bdd, $alphabetic=FALSE) {
        $request = $bdd->query('SELECT * FROM `et2_users` WHERE `new`="" AND `display`=1');
        $users = array();
        while ($row = $request->fetch()) {
            $users[$row['user_id']] = $row;
            $users[$row['user_id']]['img'] = "https://cdn.discordapp.com/avatars/" . $row['discord_id'] . "/" . $row['avatar'] . "?size=32";
        }

        if ($alphabetic) {
            usort($users, 'user_cmp');
            $new_users = array();
            foreach ($users as $_ => $user) {
                $new_users[$user['user_id']] = $user;
            }
            return $new_users;
        } else {
            return $users;
        }
    }

    function champion_cmp($champion1, $champion2) {
        return strcmp($champion1['showname'], $champion2['showname']);
    }

    function get_all_champions($bdd, $patch, $alphabetic=FALSE) {        
        $request = $bdd->query('SELECT * FROM `et2_champions`');
        $champions = array();

        while ($row = $request->fetch()) {
            $champions[$row['name']] = $row;
            $champions[$row['name']]['img'] = 'https://ddragon.leagueoflegends.com/cdn/' . $patch . '/img/champion/' . $row['name'] . '.png';
        }

        if ($alphabetic) {
            usort($champions, 'champion_cmp');
            $new_champions = array();
            foreach ($champions as $_ => $champion) {
                $new_champions[$champion['name']] = $champion;
            }
            return $new_champions;
        } else {
            return $champions;
        }
    }

    function get_userid_from_password($bdd, $password) {
        $request = $bdd->query('SELECT * FROM `et2_passwords`');
        while ($user = $request->fetch()) {
            if (password_verify(trim($password), $user['password'])) return $user;
        }
        return FALSE;
    }

    function get_user($bdd, $user_id) {
        $request = $bdd->prepare('SELECT * from `et2_users` WHERE `user_id`=?');
        $request->execute(array($user_id));
        $user = $request->fetch();

        if (is_array($user)) return $user;
        else return FALSE;
    }

    function get_mp_content($name, $password, $already_has_profile) {
        $message = array(
            "========================================",
            "Salut **" . $name . "** ! Je suis le bot de Charon et je viens pour te permettre de " . ($already_has_profile ? 'modifier' : 'créer') . " ton profil utilisateur sur la toute nouvelle version des Éternels de Demacia !",
            "",
            "Si tu ne sais pas ce dont il s'agit, c'est un site web, créé par Charon, qui te permet d'avoir accès à de nombreuses statistiques sur tes games de LoL, ton ELO, ..., ainsi que des classements entre Demaciens sur ces données. Le site est accessible à ce lien : **https://www.charon25.fr/eternals**, et si tu veux un exemple, tu peux voir les stats de Charon ici : **https://charon25.fr/eternals/user?user_id=2ebcbccb**.",
            "",
            "Cette version a l'avantage de disposer d'une page permettant à chacun de choisir son nom, le pseudo LoL à utiliser ainsi que les informations exactes qu'on désire afficher. Si tu n'as pas envie que les gens voient ton ELO, ou d'apparaître dans les classements, c'est possible !",
            "",
            ($already_has_profile ? "Comme tu étais déjà inscrit à la version précédente des Éternels, tu as automatiquement été ré-inscrit à la nouvelle version. Tu peux cependant changer tes informations sur cette page si nécessaire : **https://charon25.fr/eternals/account**. Si tu ne veux plus figurer sur le site, tu peux également à l'aide du bouton \"Ne plus apparaître sur le site\"." : "Tu n'étais pas inscrit à la version précédente, donc si tu veux apparaître sur cette version, tu dois créer ton profil en allant sur cette page : **https://charon25.fr/eternals/account** et en choisissant ton nom, ton pseudo LoL et les informations que tu souhaites voir. Tu pourras toujours changer ces informations plus tard sur la même page. Si jamais tu ne souhaites pas t'inscrire, il te suffit de ne pas remplir cette page."),
            "",
            "Ton mot de passe est : **" . $password . "** (non-modifiable, donc garde le secret).",
            "",
            "Si jamais tu as des questions ou des suggestions, n'hésite pas à aller mp Charon !",
            "========================================"
        );
        return implode("\r\n", $message);
    }
