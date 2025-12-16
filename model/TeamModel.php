<?php
//The Model used for searching for a team

//This runs very similarly to the PlayerModel, so for comments of the inner
//workings of this file, please refer to PlayerModel.php
class TeamModel extends Model
{
	private $teamsearch;
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
		$teamstr = urldecode($params[0]);
		$this->title .= htmlentities($teamstr);
		$this->teamsearch = $teamstr;
	}
	public function search()
	{
		$select = "SELECT source, team, teamid, date, tournament, tournid, division, divisionid";
		$where = "WHERE MATCH(team) AGAINST(? IN BOOLEAN MODE)";
		$teamsearch = $this->teamsearch;
		$stmt = $this->mysqli->prepare("$select FROM $this->teamdb $where " .
			"ORDER BY date DESC, tournament ASC, team ASC");
		$stmt->bind_param("s", $teamsearch);
		$stmt->execute();
		$stmt->bind_result($source, $team, $teamid, $date, $tname, $tournid, $phasename, $phaseid);
		$resulttable = array();
		while($stmt->fetch())
			$resulttable[] = array("source"		=> $source,
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
	public function getdata()
	{
		$searchresults = $this->search();
		return array("css" => "big",
					 "title" => $this->title,
					 "headertext" => $this->headertext,
					 "teamsearch" => htmlentities($this->teamsearch),
					 "results" => $searchresults);
	}
}
?>
