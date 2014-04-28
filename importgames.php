<?php
	//Gets table names and other database stuff. Actual table names are hidden
	//in case someone finds a security hole.
	require_once("dbnames.inc");

	//Fetches the number of tournament DB entries
	$dbstats = file_get_contents("http://hsquizbowl.org/db/tournaments/dbstats.php");
	preg_match("/max=(\d+)/", $dbstats, $statarray);
	$numtourneys = $statarray[1];

	//Ensures that others cannot run the script
	session_start();
	if(!isset($_SESSION['id']) || $_SESSION['id'] != 2)
	{
		header("Location: http://hdwhite.org/login.php");
		exit;
	}
	
	require_once($_dbconfig); //connects to MySQL

	//Using new tables allows the database to still be used while this is happening
	$mysqli->query("CREATE TABLE $_newteamdb LIKE $_teamdb");
	$mysqli->query("CREATE TABLE $_newplayerdb LIKE $_playerdb");
	echo("Tables created.<br>");

	for($num = 1; $num < $numtourneys; $num++)
	{
		//Gets the tournament page
		$tpage = file_get_contents("http://hsquizbowl.org/db/tournaments/$num");
		
		//Gets the tournament name. It is in the second <H2> bracket on the page.
		preg_match_all("/<H2>(.*)<\/H2>/", $tpage, $namematch);
		$tname = $mysqli->real_escape_string($namematch[1][1]);
		echo($namematch[1][1] . " ($num)<br>");

		//Dates are in <H5> brackets. It has to be able to work with multi-day,
		//multi-month, and multi-year tournaments. The final capitalised word
		//is always the month, and the final groupings of numbers are the date and year.
		preg_match("/<H5>.*([A-Z][a-z]*) .*([0-9]{2}, [0-9]{4})<\/H5>/", $tpage, $datematch);
		$tdate = date("Y-m-d", strtotime($datematch[1] . " " . $datematch[2]));

		//Searches for links to stats
		preg_match_all("/stats\/(.*)\/\">(.*)</", $tpage, $linkmatch, PREG_SET_ORDER);
		
		//Prepare the SQL insertions!
		$teamstmt = $mysqli->prepare("INSERT INTO $_newteamdb" . 
			"(team, teamid, date, tournament, tournid, division) " .
			"VALUES(?, ?, ?, ?, ?, ?)");
		$playerstmt = $mysqli->prepare("INSERT INTO $_newplayerdb" . 
			"(player, playerid, team, teamid, date, tournament, tournid, division) " .
			"VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
		$teamstmt->bind_param("sissis", $teamname, $teamid, $tdate, $tname, $num, $dname);
		$playerstmt->bind_param("sisissis", $pname, $pid, $teamname, $teamid, $tdate, $tname, $num, $dname);

		foreach($linkmatch as $link)
		{
			$dname = trim($mysqli->real_escape_string($link[1]));
			
			//Now open each stat report
			$rpage = file_get_contents("http://hsquizbowl.org/db/tournaments/$num/stats/" . $link[1]);
			
			//All team names in each stat report link to their detail page, so use that
			preg_match_all("/teamdetail\/#t([0-9]*)>(.*)<\/A/", $rpage, $teammatch, PREG_SET_ORDER);
			
			//For each team we find,store all their data
			foreach($teammatch as $team)
			{
				$teamname = trim($team[2]);
				$teamid = $team[1];
				$teamstmt->execute();
			}

			//Now open the individual stats page
			$rpage = file_get_contents("http://hsquizbowl.org/db/tournaments/$num/stats/" . $link[1] . "/individuals");
			
			//Similarly, individuals are linked to their player detail page.
			//We have to get their team info as well, though.
			preg_match_all("/playerdetail\/#p([0-9]*)_([0-9]*)>(.*)<\/A.*\n.*LEFT>(.*)<\/td/", $rpage, $playermatch, PREG_SET_ORDER);

			//Store the indiviual player details
			foreach($playermatch as $player)
			{
				$pname = trim($player[3]);
				$pid = $player[1];
				$teamname = trim($player[4]);
				$teamid = $player[2];
				$playerstmt->execute();
			}
		}
	}
	$teamstmt->close();
	$playerstmt->close();

	//Once we're done, create a backup of the current tables and move the new ones into place.
	$mysqli->query("DROP TABLE $_teamdbbak, $_playerdbbak");
	$mysqli->query("RENAME TABLE $_teamdb TO $_teamdbbak, $_playerdb TO $_playerdbbak, $_newteamdb TO $_teamdb, $_newplayerdb TO $_playerdb");
?>
