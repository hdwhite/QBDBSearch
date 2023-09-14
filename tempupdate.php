<?php
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	require_once("dbnames.inc");
	require_once($_dbconfig); //connects to MySQL
	$mysqli->query("DROP TABLE $_teamdbbak, $_playerdbbak");
	$mysqli->query("RENAME TABLE $_teamdb TO $_teamdbbak, $_playerdb TO $_playerdbbak, $_newteamdb TO $_teamdb, $_newplayerdb TO $_playerdb");
?>
