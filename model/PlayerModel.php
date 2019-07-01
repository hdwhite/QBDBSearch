<?php
//The Model used for displaying results of the player search
class PlayerModel extends Model
{
	private $playerlist, $playersearch, $teamlist, $teamsearch, $exactplayer, $exactteam;

	//Nothing mindblowing here
	public function __construct()
	{
		$this->init();
		$this->title = "Search results for ";
		$this->headertext = "Search Results";
	}
	public function gettype()
	{
		return "player";
	}

	//Parses the URL to determine the search terms
	public function setparams($params)
	{
		//Decodes the URL into applicable text and check to see if we should use
		//exact strings
		if (substr($params[0], 0, 3) == "%7E")
		{
			$this->exactplayer = true;
			$playerstr = urldecode(substr($params[0], 3));
		}
		elseif ($params[0][0] == "~")
		{
			$this->exactplayer = true;
			$playerstr = urldecode(substr($params[0], 1));
		}
		else
		{
			$this->exactplayer = false;
			$playerstr = urldecode($params[0]);
		}
		
		if (substr($params[1], 0, 3) == "%7E")
		{
			$this->exactteam = true;
			$teamstr = urldecode(substr($params[1], 3));
		}
		elseif ($params[1][0] == "~")
		{
			$this->exactteam = true;
			$teamstr = urldecode(substr($params[1], 1));
		}
		else
		{
			$this->exactteam = false;
			$teamstr = urldecode($params[1]);
		}


		//Stores the search strings as class variables
		$this->playersearch = htmlentities($playerstr);
		$this->teamsearch = htmlentities($teamstr);

		//Appends the page title
		$this->title .= $this->playersearch;

		//Case sensitivity doesn't matter for the search
		$playerstr = strtolower($playerstr);
		$teamstr = strtolower($teamstr);

		if($playerstr == "mod me myers")
			$playerstr = "jakob myers";
		if($playerstr == "william taylor")
			$playerstr = "william golden";

		//Make wildcards the correct symbol
		$playerstr = str_replace("*", "%", $playerstr);
		$teamstr = str_replace("*", "%", $teamstr);

		//Allows people to search for multiple queries, and stores search strings
		//as arrays
		$this->playerlist = explode(" or ", $playerstr);
		$this->teamlist = explode(" or ", $teamstr);
	}

	//Searches the database and returns an array of matches
	protected function search()
	{
		//Don't return anything if the query is too short
		if(max(array_map('strlen', $this->playerlist)) < 2) return array();

		//Puts wildcards on each side of the search strings and turns them into references
		//For some reason, references can't be deferenced
		$playerqueries = array();
		$playerref = array();
		for($i = 0; $i < count($this->playerlist); $i++)
		{
			if ($this->exactplayer)
				$playerref[$i] = "^" . $this->playerlist[$i] . "$";
			else
				$playerref[$i] = ".*" . $this->playerlist[$i] . ".*";
			$playerqueries[] = &$playerref[$i];
		}

		//Creates as many LIKE clauses as there are player search strings
		$where = "WHERE (player RLIKE ?" .
			str_repeat(" OR player RLIKE ?", count($this->playerlist) - 1) . ")";

		//Basically do the same thing with teams, but only if a team was searched for
		$teamqueries = array();
		if(count($this->teamlist) > 0)
		{
			$teamref = array();
			for($i = 0; $i < count($this->teamlist); $i++)
			{
				if($this->exactteam)
					$teamref[$i] = "^" . $this->teamlist[$i] . "( .)?$";
				else
					$teamref[$i] = ".*" . $this->teamlist[$i] . ".*";
				$teamqueries[] = &$teamref[$i];
			}
			$where .= " AND (team RLIKE ?" .
				str_repeat(" OR team RLIKE ?", count($this->teamlist) - 1) . ")";
		}

		//The SELECT clause
		$select = "SELECT source, player, playerid, team, teamid, date, tournament, tournid, division, divisionid";

		//Prepare the query
		$stmt = $this->mysqli->prepare("$select FROM $this->playerdb $where " .
			"ORDER BY date DESC, tournament ASC, team ASC, player ASC");
		//There's one string for each query used
		$types = str_repeat('s', count($this->playerlist) + count($this->teamlist));

		//Bind_param doesn't really work with an unknown amount of queries, so we
		//have to hack together the command
		call_user_func_array(array(&$stmt, 'bind_param'),
			array_merge((array)$types, $playerqueries, $teamqueries));
		$stmt->execute();
		$stmt->bind_result($source, $player, $playerid, $team, $teamid, $date, $tname, $tournid, $phasename, $phaseid);
		$resulttable = array();

		//Populates the table with the query results
		while($stmt->fetch())
			$resulttable[] = array("source"		=> $source,
								   "player"		=> $player,
								   "playerid"	=> $playerid,
								   "team"		=> $team,
								   "teamid"		=> $teamid,
								   "date"		=> $date,
								   "tournament"	=> $tname,
								   "tournid"	=> $tournid,
								   "phasename"	=> $phasename,
								   "phaseid"	=> $phaseid);
		$stmt->close();
		return $resulttable;
	}

	//Calls the search and returns all the necessary data
	public function getdata()
	{
		$searchresults = $this->search();
		return array("css" => "big",
					 "title" => $this->title,
					 "headertext" => $this->headertext,
					 "playersearch" => $this->playersearch,
					 "teamsearch" => $this->teamsearch,
					 "exactplayer" => $this->exactplayer,
					 "exactteam" => $this->exactteam,
					 "results" => $searchresults);
	}
}
