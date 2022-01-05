import json
import requests
import time


RASPBERRY_PI = True

SAVED_STATS_PATH = ('/home/pi/eternals/' if RASPBERRY_PI else '') + 'saved_stats.txt'

TIERS_LP = {
    'IRON': 0,
    'BRONZE': 400,
    'SILVER': 800,
    'GOLD': 1200,
    'PLATINUM': 1600,
    'DIAMOND': 2000,
    'MASTER': 2400,
    'GRANDMASTER': 2400,
    'CHALLENGER': 2400
}

DIVISIONS_LP = {
    'IV': 0,
    'III': 100,
    'II': 200,
    'I': 300
}

LEAGUES = {
    'RANKED_SOLO_5x5': 0,
    'RANKED_FLEX_SR': 1
}

class RiotAPI:
    def __init__(self, token, logger) -> None:
        self.token = token
        self.logger = logger
        with open(SAVED_STATS_PATH, 'r', encoding='utf-8') as fi:
            self.saved_stats = fi.read().splitlines()

    def _get_response(self, url, token=True):
        time.sleep(0.05)
        headers = {'X-Riot-Token': self.token} if token else None
        try:
            req = requests.get(url, headers=headers)
        except requests.ConnectionError as e:
            self.logger.add_line('riot-error', f"Cannot connect to server for url '{url}'")
            return None
        except Exception as e:
            self.logger.add_line('riot-error', f"Error '{str(e)}' while requesting url '{url}'")
            return None

        self.logger.add_line('riot', f"Request (token={token}) for url '{url}' : {req.status_code}")
        if req.status_code >= 400:
            self.logger.add_line('riot-error', f"Error : {req.text}")

        try:
            return json.loads(req.text)
        except Exception:
            return None

    def get_summoner_from_name(self, name):
        url = f'https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/{name}'
        return self._get_response(url)

    def _get_matchlists(self, puuid, start_time, start=0):
        url = f'https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/{puuid}/ids?startTime={start_time}&start={start}&count=100'
        return self._get_response(url)

    def _get_match_json(self, match_id):
        url = f'https://europe.api.riotgames.com/lol/match/v5/matches/{match_id}'
        return self._get_response(url)
    
    def _get_participant(self, match, puuid):
        for participant in match['info']['participants']:
            if participant['puuid'] == puuid:
                return participant
        return None
    
    def _get_opponent_participant(self, match, participant):
        for other_participant in match['info']['participants']:
            if other_participant['teamId'] != participant['teamId'] and other_participant['individualPosition'] == participant['individualPosition']:
                return other_participant
        return None
    
    def _get_match_general_data(self, match):
        info = match['info']
        return {
            'id': info['gameId'],
            'time': info['gameEndTimestamp'] // 1000,
            'duration': info['gameDuration'],
            'queue': info['queueId']
            # 'totalKills': sum(participant['kills'] for participant in info['participants']),
            # 'totalDeaths': sum(participant['deaths'] for participant in info['participants']),
        }

    def get_matchs_data(self, user_id, puuid, start_time):
        output = []

        start = 0
        matchlist = []
        # Permet de récupérer plus de 100 matchs
        while True:
            temp_list = self._get_matchlists(puuid, start_time, start)
            matchlist += temp_list
            if len(temp_list) < 100:
                break
            start += 100

        for match_id in matchlist:
            try:
                match = self._get_match_json(match_id)
                match_data = self._get_match_general_data(match)
                
                if match_data['duration'] < 10 * 60:
                    continue

                participant = self._get_participant(match, puuid)
                opponent = self._get_opponent_participant(match, participant)
                if participant is None or opponent is None:
                    continue

                for stat in self.saved_stats:
                    match_data[stat] = participant[stat]
                match_data['win'] = int(participant['win'])
                match_data['teamId'] = 0 if participant['teamId'] == 100 else 1
                match_data['totalKills'] = sum(p['kills'] for p in match['info']['participants'] if p['teamId'] == participant['teamId'])
                match_data['totalDeaths'] = sum(p['deaths'] for p in match['info']['participants'] if p['teamId'] == participant['teamId'])
                match_data['items'] = json.dumps([participant[f'item{i}'] for i in range(0, 7) if participant[f'item{i}'] != 0])
                match_data['opponent'] = opponent['championName']
                match_data['user_id'] = user_id
            except Exception as e:
                self.logger.add_line('riot-error', f"Error while analysing match '{match_id}' : {str(e)}")
            else:
                output.append(match_data)
        
        return output

    def get_current_version(self):
        return self._get_response('https://ddragon.leagueoflegends.com/api/versions.json', token=False)[0]

    def get_champions_dict(self, patch=None):
        if patch is None:
            patch = self.get_current_version()
        if patch is None:
            return {}

        url = f'https://ddragon.leagueoflegends.com/cdn/{patch}/data/en_US/champion.json'
        data = self._get_response(url, token=False)['data']
        if data is None:
            return {}

        output = {}

        try:
            for _, champion in data.items():
                id = champion['key']
                name = champion['id']
                show_name = champion['name']
                output[int(id)] = (name, show_name)
        except Exception as e:
            self.logger.add_line('riot-error', f"Error while fetching champions : {str(e)}")
        else:
            return output
    
    def get_champion_masteries(self, summoner_id, champions_dict):
        url = f'https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/{summoner_id}'
        masteries = self._get_response(url)

        output = {}
        for champion in masteries:
            try:
                output[champions_dict[champion['championId']][0]] = champion['championPoints']
            except Exception as e:
                self.logger.add_line('riot-error', f"Error while getting champion '{champion}' mastery : {str(e)}")
        
        return output

    def get_ranks(self, summoner_id):
        url = f'https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/{summoner_id}'
        ranks = self._get_response(url)
        output = {}

        for league in ranks:
            if league['queueType'] in LEAGUES:
                rank_type = LEAGUES[league['queueType']]
                output[rank_type] = {'wins': league['wins'], 'losses': league['losses']}
                if league['tier'] in ('MASTER', 'GRANDMASTER', 'CHALLENGER'):
                    output[rank_type]['lp'] = 10 * (2400 + int(league['leaguePoints']))
                    output[rank_type]['lp'] += {'MASTER': 0, 'GRANDMASTER': 2, 'CHALLENGER': 3}[league['tier']]
                else:
                    output[rank_type]['lp'] = 10 * (TIERS_LP[league['tier']] + DIVISIONS_LP[league['rank']] + int(league['leaguePoints']))
                    output[rank_type]['lp'] += int('miniSeries' in league)

        return output
    
    def _get_item_type(self, id, item):
        if id == '1001' or ('from' in item and '1001' in item['from']):
            return 'boots'
        if 'tags' in item and 'Trinket' in item['tags'] and 'Vision' in item['tags']:
            return 'trinket'
        if id == '2010' or ('tags' in item and 'Consumable' in item['tags'] and item['gold']['total'] < 1000):
          return 'consumable'
        if 'description' in item and 'Mythic Passive' in item['description']:
          return 'mythic'
        if 'tags' in item and 'Jungle' in item['tags']:
          return 'jungle'
        if 'into' in item:
          return 'component'
        if (not 'from' in item and 0 < item['gold']['total'] <= 500 and item['gold']['purchasable']) or 'starting' in item['plaintext']:
          return 'starter'
        if not item['gold']['purchasable'] and 'tags' in item and 'GoldPer' in item['tags']:
          return 'support'
        if item['gold']['total'] > 0:
            return 'legendary'
        return 'unknown'

    def get_items_dict(self, patch=None):
        if patch is None:
            patch = self.get_current_version()
        if patch is None:
            return {}

        url = f'https://ddragon.leagueoflegends.com/cdn/{patch}/data/en_US/item.json'
        response = self._get_response(url, token=False)
        if response is None:
            return {}
        data = response['data']

        url_fr = f'https://ddragon.leagueoflegends.com/cdn/{patch}/data/fr_FR/item.json'
        response = self._get_response(url_fr, token=False)
        if response is None:
            return {}
        data_fr = response['data']

        output = {}

        try:
            for id in data:
                item = data[id]
                output[id] = {'name': item['name'], 'name_fr': data_fr[id]['name'], 'type': self._get_item_type(id, item)}
        except Exception as e:
            self.logger.add_line('riot-error', f"Error while fecthing items : {str(e)}")
        
        return output

    def _get_smaller_lp(self, league, type):
        url = f'https://euw1.api.riotgames.com/lol/league/v4/{league}leagues/by-queue/{type}'
        response = self._get_response(url)

        if response is None:
            return None

        try:
            min_lp = 10**6
            for player in response['entries']:
                if min_lp > player['leaguePoints']:
                    min_lp = player['leaguePoints']
            return min_lp
        except Exception as e:
            self.logger.add_line('riot-error', f"Error while fetching league {league} (type = {type}) : {str(e)}")
            return None

    def get_master_limits(self):
        output = {'soloq': {}, 'flex':{}}
        soloq_challengers = self._get_smaller_lp('challenger', 'RANKED_SOLO_5x5')
        if soloq_challengers is not None:
            output['soloq']['chall'] = soloq_challengers

        soloq_challengers = self._get_smaller_lp('challenger', 'RANKED_FLEX_SR')
        if soloq_challengers is not None:
            output['flex']['chall'] = soloq_challengers

        soloq_challengers = self._get_smaller_lp('grandmaster', 'RANKED_SOLO_5x5')
        if soloq_challengers is not None:
            output['soloq']['gm'] = soloq_challengers

        soloq_challengers = self._get_smaller_lp('grandmaster', 'RANKED_FLEX_SR')
        if soloq_challengers is not None:
            output['flex']['gm'] = soloq_challengers
        
        return output

