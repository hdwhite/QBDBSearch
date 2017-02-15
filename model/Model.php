<?php
//Abstract Model class
abstract class Model
{
	protected $playerdb, $teamdb, $mysqli, $title, $headertext;

	//All models have to be initialised
	abstract protected function __construct();

	//Initialises the MySQL connection
	public function init()
	{
		require_once("dbnames.inc");
		require_once($_dbconfig);
		$this->playerdb = $_playerdb;
		$this->teamdb = $_teamdb;
		$this->newtourneydb = $_newtourneydb;
		$this->mysqli = $mysqli;
	}

	//Used so the Controller can know what Model we're using
	abstract protected function gettype();

	//Almost invariably, the Controller will call this function
	//Used to store necessary values from the URL
	abstract protected function setparams($params);
}
?>
