<?php
//The Model used for searching for a team

//This runs very similarly to the PlayerModel, so for comments of the inner
//workings of this file, please refer to PlayerModel.php
class TeamModel extends Model
{
	private $teamlist, $teamsearch, $exactteam;
	public function __construct()
	{
		$this->init();
		$this->title = "Search results for ";
		$this->headertext = "Search Results";
	}
	public function gettype()
	{
		return "team";
	}
	public function setparams($params)
	{
		if (substr($params[0], 0, 3) == "%7E")
		{
			$this->exactteam = true;
			$teamstr = urldecode(substr($params[0], 3));
		}
		elseif ($params[0][0] == "~")
		{
			$this->exactteam = true;
			$teamstr = urldecode(substr($params[0], 1));
		}
		else
		{
			$this->exactteam = false;
			$teamstr = urldecode($params[0]);
		}
		$this->teamsearch = htmlentities($teamstr);
		$this->title .= $this->teamsearch;
		$teamstr = strtolower($teamstr);
		$teamstr = str_replace("*", "%", $teamstr);
		$this->teamlist = explode(" or ", $teamstr);
	}
	public function search()
	{
		if(max(array_map('strlen', $this->teamlist)) < 2) return array();
		$teamqueries = array();
		$teamref = array();
		for($i = 0; $i < count($this->teamlist); $i++)
		{
			if($this->exactteam)
				$teamref[$i] = "^" . $this->teamlist[$i] . "( .)?$";
			else
				$teamref[$i] = ".*" . $this->teamlist[$i] . ".*";
			$teamqueries[] = &$teamref[$i];
		}
		$where = "WHERE team RLIKE ?" .
			str_repeat(" OR team RLIKE ?", count($this->teamlist) - 1);
		$select = "SELECT naqt, team, teamid, date, tournament, tournid, division";
		$stmt = $this->mysqli->prepare("$select FROM $this->teamdb $where " .
			"ORDER BY date DESC, tournament ASC, team ASC");
		$types = str_repeat('s', count($this->teamlist));
		call_user_func_array(array(&$stmt, 'bind_param'),
			array_merge((array)$types, $teamqueries));
		$stmt->execute();
		$stmt->bind_result($naqt, $team, $teamid, $date, $tname, $tournid, $division);
		$resulttable = array();
		while($stmt->fetch())
			$resulttable[] = array("naqt"		=> $naqt,
								   "team"		=> $team,
								   "teamid"		=> $teamid,
								   "date"		=> $date,
								   "tournament"	=> $tname,
								   "tournid"	=> $tournid,
								   "division"	=> $division);
		$stmt->close();
		return $resulttable;
	}
	public function getdata()
	{
		$searchresults = $this->search();
		return array("css" => "big",
					 "title" => $this->title,
					 "headertext" => $this->headertext,
					 "teamsearch" => $this->teamsearch,
					 "exactteam" => $this->exactteam,
					 "results" => $searchresults);
	}
}
?>
