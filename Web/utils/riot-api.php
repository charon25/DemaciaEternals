<?php

    class RiotAPI {
        var $key;

        function __construct($_key) {
            $this->key = $_key;
        }

        private function get_request($url) {
            usleep(25 * 1000);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Riot-Token: ' . $this->key));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response, TRUE);

        }

        function get_summoner_name_from_id($summoner_id) {
            $url = 'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/' . $summoner_id;
            $summoner = $this->get_request($url);
            return $summoner['name'] ?? '';
        }

        function get_puuid_id_from_name($summoner_name) {
            $url = 'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/' . $summoner_name;
            $summoner = $this->get_request($url);
            if (!array_key_exists('puuid', $summoner)) return FALSE;
            if (!array_key_exists('id', $summoner)) return FALSE;
            return array('puuid' => $summoner['puuid'], 'summoner_id' => $summoner['id']);
        }
    }
