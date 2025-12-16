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
		//Gets the number of tourneys, teams, and players stored in the database
		$numtourneys = $this->mysqli->query("SELECT number FROM $this->statsdb WHERE statistic=\"tournaments\"")->fetch_assoc()['number'];
		$numteams = $this->mysqli->query("SELECT number FROM $this->statsdb WHERE statistic=\"teams\"")->fetch_assoc()['number'];
		$numplayers = $this->mysqli->query("SELECT number FROM $this->statsdb WHERE statistic=\"players\"")->fetch_assoc()['number'];

		$newtourneys = $this->mysqli->query("SELECT * FROM $this->newtourneydb ORDER BY date");
		while($newtourneytable[] = $newtourneys->fetch_assoc());
		array_pop($newtourneytable);

		return array("css" => "", "title" => $this->title, "headertext" => $this->headertext, "numplayers" => $numplayers, "numteams" => $numteams, "numtourneys" => $numtourneys, "newtourneys" => $newtourneytable);
	}
}
