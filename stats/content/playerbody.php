<div id="content">
	<?php require("stats/content/form.php"); ?>
	<div class="entry">
		<table border="1" class="sortable">
			<tr><th>Date</th><th>Tournament</th><th>Team</th><th>Player</th><th>Phase</th></tr>
			<?php
				//The tournament and player name of the previous row
				$oldtourn = "";
				$oldplayer = "";
				foreach($results as $cur)
				{
					//If the tournament and the player are both the same as before,
					//don't add a new row and instead add a link to the next phase
					//in the same row.
					if($oldtourn != $cur['tournament'] || $oldteam != $cur['team'] || $oldplayer != $cur['player'])
					{
						echo("</td></tr>\n");
						echo("<tr><td>" . date("n/j/Y", strtotime($cur['date'])) . "</td>\n");
						echo("<td><a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "'>" . $cur['tournament'] . "</a></td>\n");
						echo("<td>" . $cur['team'] . "</td>\n");
						echo("<td class='nowrap'>" . $cur['player'] . "</td>\n");
						echo("<td>");
						$oldtourn = $cur['tournament'];
						$oldteam = $cur['team'];
						$oldplayer = $cur['player'];
					}
					echo(" <a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "/stats/" . $cur['division'] . "/playerdetail/#p" . $cur['playerid'] . "_" . $cur['teamid'] . "'>" . ucfirst(urldecode(str_replace("_", " ", $cur['division']))) . "</a>");
				}
			?>
		</table>
	</div>
</div>
