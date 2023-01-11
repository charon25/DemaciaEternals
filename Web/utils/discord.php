<?php

	class DiscordBot {
		var $token;

		function __construct($_token) {
			$this->token = $_token;
		}

        function _get_dm_id($discord_id) {
            try {
                $ch = curl_init("https://discordapp.com/api/v6/users/@me/channels");
                $data = array("recipient_id" => $discord_id);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $response = curl_exec($ch);
                $responseData = json_decode($response, TRUE);
                return $responseData['id'];
            } catch (Exception $e) {
                return null;
            }
        }

		function send_message($discord_id, $content) {
            try {
                $ch = curl_init("https://discordapp.com/api/v6/channels/" . $this->_get_dm_id($discord_id) . "/messages");
                $data = array("content" => $content);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $response = curl_exec($ch);
                $responseData = json_decode($response, TRUE);
                return $responseData;
            } catch (Exception $e) {
                return array('code' => 0);
            }
		}

	}
