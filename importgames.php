<?php
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	//Gets table names and other database stuff. Actual table names are hidden
	//in case someone finds a security hole.
	require_once("dbnames.inc");

	//Fetches the number of tournament DB entries
	$dbstats = file_get_contents("http://hsquizbowl.org/db/tournaments/dbstats.php");
	preg_match("/max=(\d+)/", $dbstats, $statarray);
	$numtourneys = $statarray[1];
	//There is no easy way to tell how many tournaments are stored on the NAQT
	//databbase, so that number has to be entered manually
	$numnaqt = 12000;
	$startnum = 1000;
	$finishnum = $numnaqt;
	//Ensures that others cannot run the script
	require_once($_dbconfig); //connects to MySQL

	//Using new tables allows the database to still be used while this is happening
	$mysqli->query("DROP TABLE $_newteamdb, $_newplayerdb");
	$mysqli->query("CREATE TABLE $_newteamdb LIKE $_teamdb");
	$mysqli->query("CREATE TABLE $_newplayerdb LIKE $_playerdb");
	echo("Tables created.\n");

	//Prepare the SQL insertions
	$teamstmt = $mysqli->prepare("INSERT INTO $_newteamdb" . 
		"(source, team, teamid, date, tournament, tournid, division, divisionid) " .
		"VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
	$playerstmt = $mysqli->prepare("INSERT INTO $_newplayerdb" . 
		"(source, player, playerid, team, teamid, date, tournament, tournid, division, divisionid) " .
		"VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$teamstmt->bind_param("isssssss", $source, $teamname, $teamid, $tdate, $tname, $num, $phasename, $phaseid);
	$playerstmt->bind_param("isssssssss", $source, $pname, $pid, $teamname, $teamid, $tdate, $tname, $num, $phasename, $phaseid);

	//We'll start with Neg5 tournaments
	$source = 2;
	//Neg5 uses JSon for its data storage, so let's take advantage of it
	$neg5data = json_decode(file_get_contents("https://stats.neg5.org/api/t/byDateRange?startDate=1970-01-01T00:00:00.000Z&endDate=2037-12-31T23:59:00.000Z"));
	foreach($neg5data->result as $neg5tournament)
	{
		$num = $neg5tournament->id; // Poor variable name, I know
		$neg5url = "https://stats.neg5.org/neg5-api/tournaments/$num/";
		$neg5phases = json_decode(file_get_contents($neg5url . "phases"));
		$tname = $neg5tournament->name;
		echo("$tname\n");
		$tdate = date("Y-m-d", strtotime($neg5tournament->tournament_date));
		$neg5teamlist = json_decode(file_get_contents($neg5url . "teams"));
		$teamlist = [];

		foreach($neg5teamlist as $teaminfo)
		{
			$teamid = $teaminfo->id;
			$teamname = $teaminfo->name;
			$teamlist[$teamid] = $teamname; //We gotta save those for later

			//It seems in most cases we want the phases listed in reverse order
			foreach(array_reverse($neg5phases) as $curphase)
			{
				$phaseid = $curphase->id;
				$phasename = $curphase->name;
				$teamstmt->execute();
			}
			//Adding in one for the unincluded All Phases
			$phaseid = "";
			$phasename = "All Phases";
			$teamstmt->execute();
		}
		
		$neg5playerlist = json_decode(file_get_contents($neg5url . "players"));
		foreach($neg5playerlist as $playerinfo)
		{
			$pid = $playerinfo->id;
			$pname = $playerinfo->name;
			$teamid = $playerinfo->teamId;
			$teamname = $teamlist[$teamid];
			foreach(array_reverse($neg5phases) as $curphase)
			{
				$phaseid = $curphase->id;
				$phasename = $curphase->name;
				$playerstmt->execute();
			}	
			$phaseid = "";
			$phasename = "All Phases";
			$playerstmt->execute();
		}
	}


/*
	Parsing NAQT tournaments are disabled for the time being
	//We're now getting tournaments found on NAQT's database
	$source = 1;
	$phasename = "NAQT"; //NAQT results combine all phases into one page
	$phaseid = "NAQT";

	//For whatever reason, there are no NAQT tournaments with id less than 1000
	for($num = $startnum; $num < $finishnum; $num++)
	{
		//Gets the tournament page
		if(!$tpage = @file_get_contents("http://naqt.com/stats/tournament-teams.jsp?tournament_id=$num"))
			continue;
		
		//Gets the tournament name. It is followed by Team Standings in <h1> brackets.
		if (!preg_match("/<h1>(.*)<\/h1>/", $tpage, $namematch))
		{
			echo("$num: Tournament name not found\n");
			continue;
		}
		$tname = $mysqli->real_escape_string($namematch[1]);
		if ($tname == "Tournament Results")
		{
			echo("$num: Tournament does not exist\n");
			continue;
		}

		//Dates are in <strong> brackets. It has to be able to work with multi-day,
		//multi-month, and multi-year tournaments. The final word is always the months.
		if(!preg_match("/(\w+ \d+, \d{4})/", $tpage, $datematch))
		{
			echo("$num: Tournament has no results\n");
			continue;
		}
		echo($namematch[1] . " ($num)\n");
		$tdate = date("Y-m-d", strtotime($datematch[1]));

		//All team names in each stat report link to their detail page, so use that
		preg_match_all("/stats\/tournament\/team\.jsp\?team_id=([0-9]+?).*>(.*)<\/a/U", $tpage, $teammatch, PREG_SET_ORDER);
		
		//For each team we find,store all their data
		foreach($teammatch as $team)
		{
			$teamname = trim($team[2]);
			$teamid = $team[1];
			$teamstmt->execute();
		}

		//Now open the individual stats page
		$rpage = file_get_contents("http://naqt.com/stats/tournament/individuals.jsp?tournament_id=$num&playoffs=true");
			
		//Similarly, individuals are linked to their player detail page.
		//We have to get their team info as well, though.
		preg_match_all("/tournament\/player\.jsp\?team_member_id=([0-9]+)\">(.*?)<\/a" .
			".*?tournament\/team\.jsp\?team_id=([0-9]+).*?>(.*?)<\/a/s", $rpage, $playermatch, PREG_SET_ORDER);

		//Store the indiviual player details
		foreach($playermatch as $player)
		{
			$pname = trim($player[2]);
			$pid = $player[1];
			$teamname = trim($player[4]);
			$teamid = $player[3];
			$playerstmt->execute();
		}
	}
*/

	//Now to load in HSQB
	$source = 0;
	for($num = 1; $num < $numtourneys; $num++)
	{
		//Gets the tournament page
		$tpage = file_get_contents("http://hsquizbowl.org/db/tournaments/$num");
		
		//Gets the tournament name. It is in the second <H2> bracket on the page.
		//If no name is found, the tournament probably doesn't exist.
		if(preg_match_all("/<H2>(.*)<\/H2>/", $tpage, $namematch) == 1)
			continue;
		$tname = $mysqli->real_escape_string($namematch[1][1]);
		echo($namematch[1][1] . " ($num)\n");

		//Dates are in <H5> brackets. It has to be able to work with multi-day,
		//multi-month, and multi-year tournaments. The final capitalised word
		//is always the month, and the final groupings of numbers are the date and year.
		preg_match("/<H5>.*([A-Z][a-z]*) .*([0-9]{2}, [0-9]{4})<\/H5>/", $tpage, $datematch);
		$tdate = date("Y-m-d", strtotime($datematch[1] . " " . $datematch[2]));

		//Searches for links to stats
		preg_match_all("/stats\/(.*)\/\">(.*)</U", $tpage, $linkmatch, PREG_SET_ORDER);
		
		foreach($linkmatch as $link)
		{
			$phasename = trim($mysqli->real_escape_string($link[2]));
			$phaseid = trim($mysqli->real_escape_string($link[1]));
			
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
	echo("All tournaments inserted.\n");

	//List all tournaments that took place in the past week
	$mysqli->query("TRUNCATE TABLE $_newtourneydb");
	$mysqli->query("INSERT INTO $_newtourneydb (tournid, source, date, tournament, division, divisionid) " .
		"SELECT DISTINCT tournid, source, date, tournament, division, divisionid FROM $_newteamdb " .
		"WHERE date >= DATE_SUB(NOW(), INTERVAL 1 WEEK) AND date <= NOW()");
	echo("New tournaments entered.");

	//Once we're done, create a backup of the current tables and move the new ones into place.
	$mysqli->query("DROP TABLE $_teamdbbak, $_playerdbbak");
	$mysqli->query("RENAME TABLE $_teamdb TO $_teamdbbak, $_playerdb TO $_playerdbbak, $_newteamdb TO $_teamdb, $_newplayerdb TO $_playerdb");
	echo("Script finished.");
?>
