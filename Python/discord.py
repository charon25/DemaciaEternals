import json

import requests


class Discord:
    def __init__(self, token, logger) -> None:
        self.token = token
        self.logger = logger

    def get_avatar(self, discord_id):
        url = f'https://discordapp.com/api/v6/users/{discord_id}'

        try:
            req = requests.get(
                url=url,
                headers={'Authorization': f'Bot {self.token}', 'content-type': 'application/json'}
            )

            self.logger.add_line('discord', f"Request for avatar of '{discord_id}' : code {req.status_code}")

            return json.loads(req.text)['avatar']
        except requests.ConnectionError:
            self.logger.add_line('discord-error', f"Cannot connect to server for url '{url}'")
            return None
        except Exception as e:
            self.logger.add_line('discord-error', f"Error '{str(e)}' while requesting url '{url}'")
            return None

    def send_message(self, channel, content):
        url = f'https://discordapp.com/api/v6/channels/{channel}/messages'
        data = {'content': content}
        headers = {'Authorization': f'Bot {self.token}', 'content-type': 'application/json'}

        try:
            requests.post(url=url, json=data, headers=headers)

            self.logger.add_line('discord', f"Requestion to send message with content '{content}' to channel '{channel}'")

            return 1
        except requests.ConnectionError:
            self.logger.add_line('discord-error', f"Cannot connect to server for url '{url}'")
            return None
        except Exception as e:
            self.logger.add_line('discord-error', f"Error '{str(e)}' while requesting url '{url}'")
            return None
