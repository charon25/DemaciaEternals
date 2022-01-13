import json
import time

import requests


URL = 'https://www.charon25.fr/eternals'  # Ne pas mettre de / à la fin

class Charon25FR:
    def __init__(self, token, logger) -> None:
        self.token = token
        self.logger = logger
    
    def _post_request(self, url, data):
        try:
            req = requests.post(
                url,
                headers={'token': self.token, 'content-type': 'application/json'},
                json=data
            )
        except requests.ConnectionError as e:
            self.logger.add_line('charon-error', f"Cannot connect to server for url '{url}'")
            return None
        except Exception as e:
            self.logger.add_line('charon-error', f"Error '{str(e)}' while requesting url '{url}'")
            return None
        
        self.logger.add_line('charon', f"Request for url '{url}' : {req.status_code} - {req.text}")
        if req.status_code >= 400:
            self.logger.add_line('charon-error', f"Error {req.status_code} : '{req.text[:100]}'")

        try:
            return json.loads(req.text)
        except Exception:
            self.logger.add_line('charon-error', f"Cannot parse JSON")
            return req.text

    def get_users(self):
        try:
            url = f'{URL}/users/'
            return json.loads(requests.get(url, headers={'token': self.token}).text)
        except requests.ConnectionError as e:
            self.logger.add_line('charon-error', f"Cannot connect to server for url '{url}'")
        except Exception as e:
            self.logger.add_line('charon-error', f"Error '{str(e)}' while requesting url '{url}'")

        return []
    
    def update_patch(self, patch):
        url = f'{URL}/update-patch'
        return self._post_request(url, {'patch': patch})
    
    def update_user(self, user_id, puuid, summoner_id):
        url = f'{URL}/users/update/?user_id={user_id}'
        return self._post_request(url, {'user': {'puuid': puuid, 'summoner_id': summoner_id}})
    
    def update_user_avatar(self, user_id, avatar):
        url = f'{URL}/users/update/avatar?user_id={user_id}'
        return self._post_request(url, {'avatar': avatar})
    
    def update_user_time(self, user_id, time):
        url = f'{URL}/users/update/time?user_id={user_id}'
        return self._post_request(url, {'time': time})

    def update_masteries(self, user_id, masteries):
        url = f'{URL}/users/update/masteries?user_id={user_id}'
        return self._post_request(url, {'masteries': masteries})

    def add_match(self, user_id, match_data):
        url = f'{URL}/users/add/match?user_id={user_id}'
        return self._post_request(url, {'match': match_data})

    def add_rank(self, user_id, rank_type, rank):
        url = f'{URL}/users/add/rank?user_id={user_id}'
        return self._post_request(url, {'rank': {'time': time.time(), 'type': rank_type, **rank}})

    def update_champions(self, champions):
        url = f'{URL}/champions/update'
        return self._post_request(url, {'champions': champions})

    def update_items(self, items):
        url = f'{URL}/items/update'
        return self._post_request(url, {'items': items})
    
    def update_master_limites(self, master_limits):
        url = f'{URL}/update-master-limits'
        return self._post_request(url, {'limits': master_limits})
