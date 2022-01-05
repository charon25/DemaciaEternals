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
            print(req.text)
        except requests.ConnectionError as e:
            self.logger.add_line('discord-error', f"Cannot connect to server for url '{url}'")
            return None
        except Exception as e:
            self.logger.add_line('discord-error', f"Error '{str(e)}' while requesting url '{url}'")
            return None

        # self.logger.add_line('discord', f"Request for avatar of '{discord_id}' : code {req.status_code}")

        return json.loads(req.text)['avatar']
