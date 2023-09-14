<?php
	ini_set('display_errors', 1);
	ini_set('memory_limit', '384M');
	error_reporting(E_ALL);
	require_once("dbnames.inc");
	require_once($_dbconfig);
//	$mysqli->query("DROP TABLE $_newteamdb, $_newplayerdb");
//	$mysqli->query("CREATE TABLE $_newteamdb LIKE $_teamdb");
//	$mysqli->query("CREATE TABLE $_newplayerdb LIKE $_playerdb");
//	$mysqli->query("INSERT $_newteamdb SELECT * FROM $_teamdb");
//	$mysqli->query("INSERT $_newplayerdb SELECT * FROM $_playerdb");
//	echo("Tables created.\n");

	$teamstmt = $mysqli->prepare("INSERT INTO $_newteamdb" . 
		"(source, team, teamid, date, tournament, tournid, division, divisionid) " .
		"VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
	$playerstmt = $mysqli->prepare("INSERT INTO $_newplayerdb" . 
		"(source, player, playerid, team, teamid, date, tournament, tournid, division, divisionid) " .
		"VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$teamstmt->bind_param("isssssss", $source, $teamname, $teamid, $tdate, $tname, $num, $phasename, $phaseid);
	$playerstmt->bind_param("isssssssss", $source, $pname, $pid, $teamname, $teamid, $tdate, $tname, $num, $phasename, $phaseid);

	$source = 1;
	for($year = 2014; $year < 2020; $year++)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer BxT7RClXaQKolTyc6I52DaNoADuQ3G92"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "https://www.naqt.com/api/stats/AvailableResults?start=$year-01-01&end=" . ($year+1) . "-01-01");
		$yeardata = json_decode(curl_exec($ch), true);
		sleep(1);
		echo("Retreived data from $year\n");
		foreach($yeardata as $tournament)
		{
			$num = $tournament['tournament_id'];
			$tname = $tournament['name'];
			$tdate = $tournament['end'];
			$standingsurl = $tournament['results_url'];
			$updated = $tournament['results_updated'];
			echo("$num $tname\n");
			foreach($tournament['divisions'] as $division)
			{
				$phaseid = $division['division_id'];
				$phasename = $division['name'];
				$level = $division['primary_audience'];
				curl_setopt($ch, CURLOPT_URL, "https://www.naqt.com/api/stats/TournamentResults?tournament_id=$num&division_id=$phaseid");
				$ddata = json_decode(curl_exec($ch), true);
				sleep(1);
				foreach($ddata['objects'] as $index => $schooldata)
				{
					if ($index == 0)
						continue;
					foreach($schooldata['teams'] as $teamdata)
					{
						$teamid = $teamdata['team_id'];
						$teamname = $teamdata['name'];
						$teamstmt->execute();
						foreach($teamdata['players'] as $playerdata)
						{
							$pid = $playerdata['team_member_id'];
							$pname = $playerdata['name'];
							$playerstmt->execute();
						}
					}
				}
			}
		}
		curl_close($ch);
	}
	$teamstmt->close();
	$playerstmt->close();
	echo("All tournaments inserted.");
?>
