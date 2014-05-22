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
		}
		else if(isset($_GET['playersearch']))
		{
			$queryarray[0] = "player";
			$queryarray[1] = urlencode($_GET['player']);
			if(isset($_GET['team']))
				$queryarray[2] = urlencode($_GET['team']);
		}
		return $queryarray;
	}
}
?>
