<?php 

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	class Bot {
		var $token;
		var $guild_id;

		function __construct($_token, $_guild_id) {
			$this->token = $_token;
			$this->guild_id = $_guild_id;
		}

		function setRole($user_id, $role_id) {
			$ch = curl_init("https://discordapp.com/api/v6/guilds/" . $this->guild_id . "/members/" . $user_id . "/roles/" . $role_id);
			$data = array("content" => "");
			curl_setopt($ch, CURLOPT_PUT, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function removeRole($user_id, $role_id) {
			$ch = curl_init("https://discordapp.com/api/v6/guilds/" . $this->guild_id . "/members/" . $user_id . "/roles/" . $role_id);
			$data = array("content" => "");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function getMessages($channel_id, $message_id) {
			$ch = curl_init("https://discordapp.com/api/v6/channels/" . $channel_id . "/messages?limit=100&after=" . $message_id);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function deleteMessage($channel_id, $message_id) {
			$ch = curl_init("https://discordapp.com/api/v6/channels/" . $channel_id . "/messages/" . $message_id);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function createDM($user_id) {
			$ch = curl_init("https://discordapp.com/api/v6/users/@me/channels");
			$data = array("recipient_id" => $user_id);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function sendMessage($channel_id, $content) {
			$ch = curl_init("https://discordapp.com/api/v6/channels/" . $channel_id . "/messages");
			$data = array("content" => $content);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			return $responseData;
		}

		function getAvatar($user_id) {
			$ch = curl_init("https://discordapp.com/api/v6/users/" . $user_id);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bot ' . $this->token));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    		//Pour avoir le header
    		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
    		//curl_setopt($ch, CURLOPT_HEADER, 1);
			$response = curl_exec($ch);
			$responseData = json_decode($response, TRUE);
			if (array_key_exists('avatar', $responseData)) {
				return $responseData['avatar'];
			} else {
				return '';
			}
		}

		function getAvatarURL($user_id, $avatar_id) {
			return 'https://cdn.discordapp.com/avatars/' . $user_id . '/' . $avatar_id;
		}

		function createAvatarURL($user_id, $avatar_id) {
			//TROP LONG A CHARGER
			/*if (@getimagesize('https://cdn.discordapp.com/avatars/' . $user_id . '/' . $avatar_id)) {
				return 'https://cdn.discordapp.com/avatars/' . $user_id . '/' . $avatar_id;
			} else {
				return 'https://www.charon25.fr/eternals/default_avatar.png';
			}*/
			if (strlen($avatar_id) < 5) {
				return "https://cdn.discordapp.com/embed/avatars/2.png";
			} else {
				return 'https://cdn.discordapp.com/avatars/' . $user_id . '/' . $avatar_id;
			}
		}

	}

 ?>