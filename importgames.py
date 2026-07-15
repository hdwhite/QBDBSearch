from datetime import datetime, timedelta
import getopt
import mysql.connector
import re
import requests
import sys
import time
import urllib.request

sys.path.insert(1, "/home/harry")
from dbconfig import DB_CONFIG, HSQB_KEY, HSQB_SECRET, NAQT_API_KEY

# Mock connection and cursor classes so to do test runs without altering the database
class MockCursor:
    def __init__(self):
        self.queries = []
    
    def execute(self, query, params=None):
        self.queries.append((query, params))
        print(f"[MOCK] {query[:80]}...")
    
    def fetchone(self):
        return (0, 0)
    
    def fetchall(self):
        return []
    
    def close(self):
        pass

class MockConnection:
    def __init__(self):
        self.cursor_obj = MockCursor()
    
    def cursor(self):
        return self.cursor_obj
    
    def commit(self):
        print("[MOCK] Commit called")
    
    def close(self):
        pass

# Parses command line arguments and returns their values. Possible arguments are:
# -h, --hsqb: Specifies whether to load "all", "none", or "recent" (last 1000) HSQB tournaments
# -n, --naqt: Specifies whether to load "all", "none", or "recent" (last 4 weeks) NAQT tournaments
# -t, --test: If included, runs in test mode without altering the database
def parse_args(argv):
    def parse_opt(arg):
        if arg in (None, "a", "all"):
            return "all"
        if arg in ("r", "recent"):
            return "recent"
        if arg in ("n", "none"):
            return "none"
        return "recent"

    hsqb = "recent"
    naqt = "recent"
    test = False
    optlist, _ = getopt.getopt(argv, "htn", ["hsqb", "naqt", "test"])
    for opt, arg in optlist:
        if opt in ("-h", "--hsqb"):
            hsqb = parse_opt(arg)
        if opt in ("-n", "--naqt"):
            naqt = parse_opt(arg)
        if opt in ("-t", "--test"):
            test = True
    return hsqb, naqt, test

# Loads an individual NAQT tournament into the database
def load_naqt_tournament(tournament, cursor: mysql.connector.cursor.MySQLCursor):
    # We skip Buzzword
    if str(tournament["scoring_type"]).lower() == "buzzword":
        return
    tournament_id = tournament["tournament_id"]
    cursor.execute("DELETE FROM newstats WHERE source=1 AND tournid=%s", (tournament_id,))
    cursor.execute("DELETE FROM newplayers WHERE source=1 AND tournid=%s", (tournament_id,))
    tournament_name = tournament["name"]
    tournament_date = tournament["end"]
    print(f"NAQT {tournament_id}: {tournament_name}\n")
    # Tournaments are divided into Divisions, so we will iterate over them
    for division in tournament["divisions"]:
        division_id = division["division_id"]
        division_name = division["name"]
        division_data = requests.get(f"https://www.naqt.com/api/stats/TournamentResults?tournament_id={tournament_id}&division_id={division_id}",
                                     headers={"Authorization": f"Bearer {NAQT_API_KEY}"}).json()
        time.sleep(1)
        # Within each Division, Teams are grouped by School
        for index, school_data in enumerate(division_data["objects"]):
            if index == 0: # Skipping registration data
                continue
            for team_data in school_data["teams"]:
                team_id = team_data["team_id"]
                team_name = team_data["name"]
                cursor.execute("INSERT INTO newstats (source, team, teamid, date, tournament, tournid, division, divisionid) "
                               "VALUES (1, %s, %s, %s, %s, %s, %s, %s)",
                               (team_name, team_id, tournament_date, tournament_name, tournament_id, division_name, division_id))
                # We've inserted in teams, now do players
                for player_data in team_data["players"]:
                    player_id = player_data["team_member_id"]
                    cursor.execute("INSERT INTO newplayers (source, player, playerid, team, date, tournament, tournid, division, divisionid) "
                                   "VALUES (1, %s, %s, %s, %s, %s, %s, %s, %s)",
                                   (player_data["name"], player_id, team_name, tournament_date, tournament_name, tournament_id, division_name, division_id))

# Loads NAQT tournaments, given the scope of events to load
def load_naqt_tournaments(scope: str, cursor: mysql.connector.cursor.MySQLCursor):
    # We only know which tournaments to delete if we're deleting all of them
    if scope != "all":
        cursor.execute("INSERT INTO newplayers (source, player, playerid, team, date, tournament, tournid, division, divisionid) "
                       "SELECT source, player, playerid, team, date, tournament, tournid, division, divisionid "
                       "FROM players WHERE source = 1")
        cursor.execute("INSERT INTO newstats (source, team, teamid, date, tournament, tournid, division, divisionid) "
                       "SELECT source, team, teamid, date, tournament, tournid, division, divisionid "
                       "FROM stats WHERE source = 1")
    print("Loaded in saved NAQT tournaments")

    if scope == "none":
        return
    # Default for "recent" is four weeks ago, which should cover weekly runs
    start_date = (datetime.now() - timedelta(weeks=4)).strftime("%Y-%m-%d") if scope == "recent" else "1990-01-01"
    end_date = datetime.now().strftime("%Y-%m-%d")
    naqt_data = requests.get(f"https://www.naqt.com/api/stats/AvailableResults?start={start_date}&end={end_date}",
                             headers={"Authorization": f"Bearer {NAQT_API_KEY}"}).json()
    time.sleep(1) # Sleep for 1 second for rate limiting
    for tournament in naqt_data["objects"]:
        load_naqt_tournament(tournament, cursor)
    print("All NAQT tournaments inserted.\n")

# Loads an individual HSQB tournament into the database
def load_hsqb_tournament(tournament_id: int, cursor: mysql.connector.cursor.MySQLCursor):
    req = urllib.request.Request(f"http://hsquizbowl.org/db/tournaments/{tournament_id}")
    req.add_header(HSQB_KEY, HSQB_SECRET)
    with urllib.request.urlopen(req) as response:
        tournament_page = response.read().decode("utf-8")
    # Gets the tounament name
    match = re.search(r"<H2>(.*)</H2>", tournament_page)
    if not match:
        print(f"Could not find tournament name for HSQB tournament {tournament_id}")
        return
    tournament_name = match.group(1)
    # Gets the tournament date, which can potentially be multiple days
    match = re.search(r"<H5>.*([A-Z][a-z]*) .*([0-9]{2}, [0-9]{4})</H5>", tournament_page)
    if not match:
        print(f"Could not find tournament date for HSQB tournament {tournament_id}")
        return
    tournament_date = datetime.strptime(match.group(1) + " " + match.group(2), "%B %d, %Y").strftime("%Y-%m-%d")
    print(f"HSQB {tournament_id}: {tournament_name}\n")

    # Gets the stat report links for each phase
    link_matches = re.findall(r"stats/(.*)/\">(.*)<", tournament_page)
    if not link_matches:
        print(f"Could not find any stat report links for HSQB tournament {tournament_id}")
        return
    for phase_id, phase_name in link_matches:
        req = urllib.request.Request(f"http://hsquizbowl.org/db/tournaments/{tournament_id}/stats/{phase_id}")
        req.add_header(HSQB_KEY, HSQB_SECRET)
        with urllib.request.urlopen(req) as response:
            stats_page = response.read().decode("utf-8")
        # Gets the team information from the stats page
        team_matches = re.findall(r"teamdetail/#(\w*)>(.*)</[Aa]", stats_page)
        if not team_matches:
            print(f"Could not find any teams for HSQB tournament {tournament_id} phase {phase_name}")
            continue
        for team_id, team_name in team_matches:
            cursor.execute("INSERT INTO newstats (source, team, teamid, date, tournament, tournid, division, divisionid) "
                           "VALUES (0, %s, %s, %s, %s, %s, %s, %s)",
                           (team_name.strip(), team_id.strip(), tournament_date, tournament_name.strip(), tournament_id,
                            phase_name.strip(), phase_id.strip()))

        # Now to get individual player info
        req = urllib.request.Request(f"http://hsquizbowl.org/db/tournaments/{tournament_id}/stats/{phase_id}/individuals")
        req.add_header(HSQB_KEY, HSQB_SECRET)
        with urllib.request.urlopen(req) as response:
            individuals_page = response.read().decode("utf-8")
        
        # This regex will match SQBS tournaments
        player_matches = re.findall(r"playerdetail/#(p[0-9]*_[0-9]*)>(.*)</A.*\n.*LEFT>(.*)</td", individuals_page, re.DOTALL)
        
        # If that doesn't work, it's a good chance it's Yellowfruit
        if not player_matches:
            player_matches = re.findall(r"playerdetail/#(\w*-\w*)>(.*)</a.*teamdetail/#\w*>(.+)</a></td", individuals_page, re.IGNORECASE | re.DOTALL)
        
        if not player_matches:
            print(f"No player stats found for HSQB tournament {tournament_id} phase {phase_name}")
            continue
        
        # Insert each player
        for player_id, player_name, team_name in player_matches:
            cursor.execute("INSERT INTO newplayers (source, player, playerid, team, date, tournament, tournid, division, divisionid) "
                           "VALUES (0, %s, %s, %s, %s, %s, %s, %s, %s)",
                           (player_name.strip(), player_id.strip(), team_name.strip(), 
                            tournament_date, tournament_name.strip(), tournament_id, 
                            phase_name.strip(), phase_id.strip()))

# Loads HSQB tournaments, given the scope of events to load
def load_hsqb_tournaments(scope: str, cursor: mysql.connector.cursor.MySQLCursor):
    req = urllib.request.Request("http://hsquizbowl.org/db/tournaments/dbstats.php")
    req.add_header(HSQB_KEY, HSQB_SECRET)
    with urllib.request.urlopen(req) as response:
        db_stats = response.read().decode("utf-8")
    # HSQB page which contains the current max tournamnent ID
    match = re.search(r"max=(\d+)", db_stats)
    if match:
        max_tournament_id = int(match.group(1))
    else:
        print("Could not find max tournament ID in HSQB")
        return
    # Load in tournaments we aren't parsing
    start_id = 1 if scope == "all" else max(max_tournament_id - 1000, 1) if scope == "recent" else max_tournament_id + 1
    cursor.execute("INSERT INTO newplayers (source, player, playerid, team, date, tournament, tournid, division, divisionid) "
                   "SELECT source, player, playerid, team, date, tournament, tournid, division, divisionid "
                   "FROM players WHERE source = 0 AND tournid < %s", (start_id,))
    cursor.execute("INSERT INTO newstats (source, team, teamid, date, tournament, tournid, division, divisionid) "
                   "SELECT source, team, teamid, date, tournament, tournid, division, divisionid "
                   "FROM stats WHERE source = 0 AND tournid < %s", (start_id,))
    print(f"Loaded in HSQB tournaments through ID {start_id - 1}")
    if scope == "none":
        return
    for tournament_id in range(start_id, max_tournament_id + 1):
        load_hsqb_tournament(tournament_id, cursor)
    print("All HSQB tournaments inserted.\n")

# Once the tournaments have been loaded, back up the current tables and update them
# Additionally, pull up some stats about the database
def update_databases(cursor: mysql.connector.cursor.MySQLCursor):
    # Get number of distinct tournaments, teams, and players in the database
    cursor.execute("SELECT COUNT(DISTINCT tournid, source) AS numtourneys, "
                   "COUNT(DISTINCT team, tournid, source) AS numteams "
                   "FROM newstats")
    total_tournaments, total_teams = cursor.fetchone()
    cursor.execute("UPDATE searchstats SET number=%s WHERE statistic='tournaments'", (total_tournaments,))
    cursor.execute("UPDATE searchstats SET number=%s WHERE statistic='teams'", (total_teams,))
    cursor.execute("SELECT COUNT(DISTINCT player, team, tournid, source) AS numplayers "
                   "FROM newplayers")
    total_players = cursor.fetchone()[0]
    cursor.execute("UPDATE searchstats SET number=%s WHERE statistic='players'", (total_players,))

    # Gets tournaments ran in the past week
    cursor.execute("TRUNCATE TABLE newtournaments")
    cursor.execute("INSERT INTO newtournaments (tournid, source, date, tournament, division, divisionid) "
                   "SELECT DISTINCT tournid, source, date, tournament, division, divisionid FROM newstats "
                   "WHERE date >= DATE_SUB(NOW(), INTERVAL 1 WEEK) AND date <= NOW()")
    print("New tournaments entered.")

    # Moves the current tables to a backup table and moves the new tables to the current tables
    cursor.execute("DROP TABLE IF EXISTS statsbak, playersbak")
    cursor.execute("RENAME TABLE stats TO statsbak, players TO playersbak, newstats TO stats, newplayers TO players")
    print("Tables backed up.")

def main():
    hsqb, naqt, test = parse_args(sys.argv[1:])
    # If we run in test mode, use a mock connection to not touch the database
    if test:
        conn = MockConnection()
    else:
        conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    try:
        cursor.execute("DROP TABLE IF EXISTS newplayers, newstats")
        cursor.execute("CREATE TABLE newplayers LIKE players")
        cursor.execute("CREATE TABLE newstats LIKE stats")
        conn.commit()

        load_naqt_tournaments(naqt, cursor)
        conn.commit()

        load_hsqb_tournaments(hsqb, cursor)
        conn.commit()

        update_databases(cursor)
        conn.commit()
        print("Script finished.")
    finally:
        cursor.close()
        conn.close()