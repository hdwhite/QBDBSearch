<div id="content">
	<?php require("content/form.php"); ?>
	<div class="entry">
		<table border="1" class="sortable">
			<tr><th>Date</th><th>Tournament</th><th>Team</th><th>Phase</th></tr>
			<?php
				$oldtourn = "";
				$oldteam = "";
				$lasttourney = 0;
				foreach($results as $cur)
				{
					//$cur['team'] = str_replace(" B", " 🅱\u{FE0F}", $cur['team']);
					//If it's the same team and tournament as before, don't add a
					//new row. Instead append the phase to the current row
					if($oldtourn != $cur['tournament'] || $oldteam != $cur['team'])
					{
						//If the tournament is the same but not the team, add a
						//new row, but keep the tournament name in one tall cell
						if($cur['tournid'] === $lasttourney)
						{
							$rowspan++;
							$rowtext3 = $rowtext3 . "</td></tr>\n";
							$rowtext3 = $rowtext3 . "<tr>";
						}
						else
						{
							//Deals with an edge case
							if($lasttourney !== 0)
							{
								$rowtext3 = $rowtext3 . "</td></tr>";
								echo($rowtext1 . $rowspan . $rowtext2 . $rowspan . $rowtext3);
							}
							$rowspan = 1;
							$rowtext1 = "<tr><td rowspan='";
							$rowtext2 = "'>" . date("n/j/Y", strtotime($cur['date'])) . "</td>\n";
							$rowtext2 = $rowtext2 . "<td rowspan='";

							//Where we're linking to depends on where the
							//tournament info is stored
							if($cur['source'] == 2)
								$rowtext3 = "'><a href='https://stats.neg5.org/t/" . $cur['tournid'] . "/a/team-standings'>";
							elseif($cur['source'] == 1)
								$rowtext3 = "'><a href='http://naqt.com/stats/tournament/standings.jsp?tournament_id=" . $cur['tournid'] . "'>";
							else
								$rowtext3 = "'><a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "'>";
							$rowtext3 = $rowtext3 . stripslashes($cur['tournament']) . "</a></td>\n";
						}
						$rowtext3 = $rowtext3 . "<td class='nowrap'>" . $cur['team'] . "</td>\n";
						$rowtext3 = $rowtext3 . "<td>";
						$oldtourn = $cur['tournament'];
						$oldteam = $cur['team'];
						$lasttourney = $cur['tournid'];
					}
					if($cur['source'] == 2)
						$rowtext3 = $rowtext3 . " <a href='https://stats.neg5.org/t/" . $cur['tournid'] . "/a/team-full?phase=" . $cur['phaseid'] . "#team_" . $cur['teamid'];
					elseif($cur['source'] == 1)
						$rowtext3 = $rowtext3 . " <a href='http://naqt.com/stats/tournament/team.jsp?team_id=" . $cur['teamid'];
					else
						$rowtext3 = $rowtext3 . " <a href='http://hsquizbowl.org/db/tournaments/" .
							$cur['tournid'] . "/stats/" . $cur['phaseid'];
					$rowtext3 = $rowtext3 . "'>" . ucfirst(urldecode(str_replace("_", " ", $cur['phasename']))) . "</a>";
				}
				$rowtext3 = $rowtext3 . "</td></tr>";
				echo($rowtext1 . $rowspan . $rowtext2 . $rowspan . $rowtext3);
			?>
		</table>
	</div>
</div>
