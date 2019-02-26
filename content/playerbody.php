<div id="content">
	<?php require("content/form.php"); ?>
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
						echo("<td><a href='");

						//Link to a different page depending on where the
						//tournament is being hosted
						if($cur['naqt'])
							echo("http://naqt.com/stats/tournament/standings.jsp?tournament_id=" . $cur['tournid']);
						else
							echo("http://hsquizbowl.org/db/tournaments/" . $cur['tournid']);
						echo("'>" . stripslashes($cur['tournament']) . "</a></td>\n");
						echo("<td>" . $cur['team'] . "</td>\n");
						echo("<td class='nowrap'>" . $cur['player'] . "</td>\n");
						echo("<td>");
						$oldtourn = $cur['tournament'];
						$oldteam = $cur['team'];
						$oldplayer = $cur['player'];
					}
					echo(" <a href='");
					if($cur['naqt'])
						echo("http://naqt.com/stats/tournament/team.jsp?team_id=" . $cur['teamid']);
					else
						echo("http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "/stats/" .
							$cur['division'] . "/teamdetail/#t" . $cur['teamid']);
					echo ("'>" . ucfirst(urldecode(str_replace("_", " ", $cur['division']))) . "</a>");
				}
			?>
		</table>
	</div>
</div>
