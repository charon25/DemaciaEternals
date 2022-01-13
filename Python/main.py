import math
import time
import traceback

from charon25fr import *
from discord import *
from logger import *
from riot import *


logger = Logger()

DEV = False
WAKEUP_PERIOD = 3600 if not DEV else 60

logger.add_line('main', f'Starting program with DEV = {DEV}')

RIOT_TOKEN = ''
CHARON_TOKEN = ''
DISCORD_TOKEN = ''

riot = RiotAPI(RIOT_TOKEN, logger)
charon = Charon25FR(CHARON_TOKEN, logger)
discord = Discord(DISCORD_TOKEN, logger)

def update_user(user, update_avatar):
    if user['new'] != '':
        logger.add_line('main', f"Creating new user '{user['name']}' ({user['user_id']})")

        summoner = riot.get_summoner_from_name(user['new'])
        charon.update_user(user['user_id'], summoner['puuid'], summoner['id'])
        user['puuid'] = summoner['puuid']
        user['summoner_id'] = summoner['id']
        update_avatar = True

    if update_avatar:
        charon.update_user_avatar(user['user_id'], discord.get_avatar(user['discord_id']) or '')

def get_sleep_duration():
    current_time = time.time()
    next_hour = WAKEUP_PERIOD * math.ceil(current_time / WAKEUP_PERIOD)
    return next_hour - current_time


previous_day = None  # time.strftime('%Y%m%d')
champions = None
items = None
matches_done = {}

while True:
    try:
        current_day = time.strftime('%Y%m%d')
        patch = riot.get_current_version()
        logger.add_line('main', f"Current patch is : '{patch}'")
        if patch is not None:
            charon.update_patch(patch)

        if DEV or champions is None or items is None or current_day != previous_day:

            if patch is not None:
                champions = riot.get_champions_dict(patch)
                items = riot.get_items_dict(patch)

            master_limits = riot.get_master_limits()
            charon.update_master_limites(master_limits)

        users = charon.get_users()

        if DEV or current_day != previous_day:
            logger.add_line('main', 'Updating champions and items')
            charon.update_champions(champions)
            charon.update_items(items)

        for user in users:
            time.sleep(0.1)

            if not user['user_id'] in matches_done:
                matches_done[user['user_id']] = {}

            try:
                update_user(user, DEV or current_day != previous_day)

                if DEV or current_day != previous_day:
                    logger.add_line('main', f"Updating champion masteries and ranks for user '{user['name']}'")

                    if champions is not None:
                        masteries = riot.get_champion_masteries(user['summoner_id'], champions)
                        for smurf_sid in user['smurfs_sid'].split(','):
                            if smurf_sid == '':continue

                            logger.add_line('main', f"Smurf masteries for user '{user['name']}' with id : '{smurf_sid}'")
                            smurf_masteries = riot.get_champion_masteries(smurf_sid, champions)

                            for champion in smurf_masteries:
                                masteries[champion] = masteries.get(champion, 0) + smurf_masteries[champion]

                        charon.update_masteries(user['user_id'], masteries)
                    else:
                        logger.add_line('main-error', f"Can't update masteries because champions dict is None")

                    ranks = riot.get_ranks(user['summoner_id'])
                    for rank_type in ranks:
                        charon.add_rank(user['user_id'], rank_type, ranks[rank_type])
                    
                    if previous_day is not None:
                        matchs = riot.get_matchs_data(user['user_id'], user['puuid'], int(time.time()) - 24 * 60 * 60, matches_done[user['user_id']])
                        for match in matchs:
                            charon.add_match(user['user_id'], match)
                            matches_done[user['user_id']][match['id']] = None
                        if len(matches_done[user['user_id']]) > 350:
                            matches_done[user['user_id']] = {k: None for k in list(matches_done[user['user_id']].keys())[-350:]}
                
                logger.add_line('main', f"Adding matches of user '{user['name']}'")
                matchs = riot.get_matchs_data(user['user_id'], user['puuid'], user['time'], matches_done)
                for match in matchs:
                    charon.add_match(user['user_id'], match)
                    matches_done[user['user_id']][match['id']] = None
                
                charon.update_user_time(user['user_id'], time.time())
            except Exception as e:
                logger.add_line('main-error', f"Error while doing user '{user['name']}' : {str(e)}")

        previous_day = current_day
    except Exception as e:
        logger.add_line('main-error', f"Error while doing main loop : {str(e)}")

    sleep_duration = get_sleep_duration()
    logger.add_line('main', f"Going to sleep for {sleep_duration:.0f} sec")

    logger.save()

    if DEV:
        break

    time.sleep(get_sleep_duration())
