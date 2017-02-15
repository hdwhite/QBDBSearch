<?php
//The Model used for the index page. It's mostly static, so not much needs to be done
class IndexModel extends Model
{
	//Initialises
	public function __construct()
	{
		$this->init();
		$this->title = "Quizbowl Resource Database team and player search";
		$this->headertext = "Quizbowl TDB Search";
	}
	public function gettype()
	{
		return "index";
	}
	//The index page doesn't care about any additional parameters, so this
	//function can be left blank
	public function setparams($params)
	{
	}
	//Retreives necessary data about the Index page
	public function getdata()
	{
		//Gets the number of tournaments and teams stored in the database
		$temp = $this->mysqli->query(
			"SELECT COUNT(DISTINCT tournid) AS numtourneys, " .
			"COUNT(DISTINCT team, tournid) AS numteams " .
			"FROM $this->teamdb")
			->fetch_assoc();
		$numtourneys = $temp['numtourneys'];
		$numteams = $temp['numteams'];

		//Gets the number of players stored in the database
		$numplayers = $this->mysqli->query(
			"SELECT COUNT(DISTINCT player, team, tournid) AS numplayers FROM $this->playerdb")
			->fetch_assoc()['numplayers'];

		$newtourneys = $this->mysqli->query("SELECT * FROM $this->newtourneydb ORDER BY date");
		while($newtourneytable[] = $newtourneys->fetch_assoc());
		array_pop($newtourneytable);

		return array("css" => "", "title" => $this->title, "headertext" => $this->headertext, "numplayers" => $numplayers, "numteams" => $numteams, "numtourneys" => $numtourneys, "newtourneys" => $newtourneytable);
	}
}
