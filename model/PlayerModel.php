<?php 
//The Model used for displaying results of the player search
class PlayerModel extends Model
{
	private $playersearch, $teamsearch;

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
		//Decodes the URL into applicable text
		$playerstr = urldecode($params[0]);
		$teamstr = isset($params[1]) ? urldecode($params[1]) : "";

		//Appends the page title
		$this->title .= htmlentities($playerstr);

		//Stores the search strings as class variables
		$this->playersearch = $playerstr;
		$this->teamsearch = $teamstr;
	}

	//Searches the database and returns an array of matches
	protected function search()
	{
		//The clauses
		$select = "SELECT source, player, playerid, team, date, tournament, tournid, division, divisionid";
		$where = "WHERE MATCH(player) AGAINST(? IN BOOLEAN MODE)";
		
		$playersearch = $this->playersearch;
		if(isset($this->teamsearch) && strlen($this->teamsearch) > 1)
		{
			$where .= " AND MATCH(team) AGAINST(? IN BOOLEAN MODE)";
			$teamsearch = $this->teamsearch;
		}


		//Prepare the query
		$stmt = $this->mysqli->prepare("$select FROM $this->playerdb $where " .
			"ORDER BY date DESC, tournament ASC, team ASC, player ASC");
		if(isset($this->teamsearch) && strlen($this->teamsearch) > 1)
			$stmt->bind_param("ss", $playersearch, $teamsearch);
		else
			$stmt->bind_param("s", $playersearch);
		$stmt->execute();
		$stmt->bind_result($source, $player, $playerid, $team, $date, $tname, $tournid, $phasename, $phaseid);
		$resulttable = array();

		//Populates the table with the query results
		while($stmt->fetch())
			$resulttable[] = array("source"		=> $source,
								   "player"		=> $player,
								   "playerid"	=> $playerid,
								   "team"		=> $team,
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
					 "playersearch" => htmlentities($this->playersearch),
					 "teamsearch" => htmlentities($this->teamsearch),
					 "results" => $searchresults);
	}
}
