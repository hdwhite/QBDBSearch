<div id="content">
	<?php require("stats/content/form.php"); ?>
	<div class="entry">
		<table border="1" class="sortable">
			<tr><th>Date</th><th>Tournament</th><th>Team</th><th>Phase</th></tr>
			<?php
				//Tournament name, team name, and tournament id of the previous row
				$oldtourn = "";
				$oldteam = "";
				$lasttourney = 0;
				foreach($results as $cur)
				{
					//If it's the same team and tournament as before, don't add a
					//new row. Instead append the phase to the current row
					if($oldtourn != $cur['tournament'] || $oldteam != $cur['team'])
					{
						//If the tournament is the same but not the team, add a
						//new row, but keep the tournament name in one tall cell
						if($cur['tournid'] == $lasttourney)
						{
							$rowspan++;
							$rowtext3 = $rowtext3 . "</td></tr>\n";
							$rowtext3 = $rowtext3 . "<tr>";
						}
						else
						{
							//Deals with an edge case
							if($lasttourney > 0)
							{
								$rowtext3 = $rowtext3 . "</td></tr>";
								echo($rowtext1 . $rowspan . $rowtext2 . $rowspan . $rowtext3);
							}
							$rowspan = 1;
							$rowtext1 = "<tr><td rowspan='";
							$rowtext2 = "'>" . date("n/j/Y", strtotime($cur['date'])) . "</td>\n";
							$rowtext2 = $rowtext2 . "<td rowspan='";
							$rowtext3 = "'><a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "'>" . $cur['tournament'] . "</a></td>\n";
						}
						$rowtext3 = $rowtext3 . "<td class='nowrap'>" . $cur['team'] . "</td>\n";
						$rowtext3 = $rowtext3 . "<td>";
						$oldtourn = $cur['tournament'];
						$oldteam = $cur['team'];
						$lasttourney = $cur['tournid'];
					}
					$rowtext3 = $rowtext3 . " <a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'] . "/stats/" . $cur['division'] . "/teamdetail/#t" . $cur['teamid'] . "'>" . ucfirst(urldecode(str_replace("_", " ", $cur['division']))) . "</a>";
				}
				$rowtext3 = $rowtext3 . "</td></tr>";
				echo($rowtext1 . $rowspan . $rowtext2 . $rowspan . $rowtext3);
			?>
		</table>
	</div>
</div>
