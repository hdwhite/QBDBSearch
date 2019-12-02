<html>
	<head>
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/analytics.php"); ?>
		<meta charset='utf-8'>
		<STYLE TYPE="text/css">
			@import url("/harry.css");
			<?php
			if($css == "big")
				echo("@import url(\"/harrybig.css\");");
			?>
		</STYLE>
		<title><?=$title ?></title>
	</head>
	<body>
		<div id="container">
			<div id="header">
				<h2><?=$headertext ?></h2>
				<?php include("header.php"); ?>
			</div>
