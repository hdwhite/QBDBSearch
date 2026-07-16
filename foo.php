<?php
	$secret = "7AEWUEEuUcRiGeQhrMnXGsHMJcrLeLQJKoThHLp7BVEjYYq8qjoDQMkRWXKo";
	$options = ['http' => ['header' => "X-PACE-Scraper: $secret\r\n"]];
	$context = stream_context_create($options);
	$dbstats = file_get_contents("http://hsquizbowl.org/db/tournaments/dbstats.php", false, $context);
	if($dbstats === false)
		echo("Failed");
	else
		echo($dbstats);
?>
