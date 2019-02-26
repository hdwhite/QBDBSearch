<?php
//The Model used when performing a search
class SearchModel extends Model
{
	public function __construct()
	{
		$this->init();
	}
	public function gettype()
	{
		return "search";
	}
	public function setparams($params)
	{
	}

	//Parses the search query into an array of parameters, which will be used
	//as a redirect target
	public function getquery()
	{
		$queryarray = array();
		if(isset($_GET['teamsearch']))
		{
			$queryarray[0] = "team";
			$queryarray[1] = urlencode($_GET['team']);
			$columns = "team";
			$values = "\"$queryarray[1]\"";
			if(isset($_GET['exactteam']))
				$queryarray[1] = '~' . $queryarray[1];
		}
		else if(isset($_GET['playersearch']))
		{
			$queryarray[0] = "player";
			$queryarray[1] = urlencode($_GET['player']);
			if(isset($_GET['team']))
			{
				$queryarray[2] = urlencode($_GET['team']);
				$columns = "team, player";
				$values = "\"$queryarray[2]\", \"$queryarray[1]\"";
				if(isset($_GET['exactteam']))
					$queryarray[2] = '~' . $queryarray[2];
			}
			else
			{
				$columns = "player";
				$values = "\"$queryarray[1]\"";
			}
			if(isset($_GET['exactplayer']))
				$queryarray[1] = '~' . $queryarray[1];
		}
		$stmt = $this->mysqli->prepare("INSERT INTO " . $this->logdb . " ($columns) VALUES($values)");
		$stmt->execute();
		return $queryarray;
	}
}
?>
