<div id="content">
	<div class="entry">
		<h4>How to use</h4>
		<p>This site searches the <a href="http://hsquizbowl.org/db">Quizbowl Resource Database</a> for all stat reports containing a given team or player. Searches are made "as is", and no effort is made to search for common alternate names (such as VT for Virginia Tech). The site is indexed on a more-or-less weekly basis, so recently-posted results may not show up right away. To search for one of multiple different terms, separate the commands with an "OR" (e.g. "VT or Virginia Tech" should show all results for said school).</p><br>
		<p>Currently, there are <?=number_format($numplayers) ?> individual player stats from <?=number_format($numteams) ?> teams in <?=number_format($numtourneys) ?> tournaments indexed on this site.</p>
	</div>
	<?php require("content/form.php"); ?>
	<div class="entry">
		<h4>New tournaments added</h4>
		<table border="1" class="sortable">
			<tr><th>Date</th><th>Tournament</th><th>Phase</th></tr>
			<?php
				$oldtourn = "";
				$lasttourney = 0;
				foreach($newtourneys as $cur)
				{
					//If it's the same tournament as before, don't add a
					//new row. Instead append the phase to the current row
					if($oldtourn != $cur['tournament'])
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

						//Where we're linking to depends on where the
						//tournament info is stored

						if($cur['source'] == 0)
							$rowtext3 = "'><a href='http://hsquizbowl.org/db/tournaments/" . $cur['tournid'];
						elseif($cur['source'] == 1)
							$rowtext3 = "'><a href='http://naqt.com/stats/tournament-teams.jsp?tournament_id=" . $cur['tournid'];
						else
							$rowtext3 = "'><a href='https://stats.neg5.org/t/" . $cur['tournid'] . "/a/team-standings";
						$rowtext3 = $rowtext3 . "'>" . stripslashes($cur['tournament']) . "</a></td>\n";
						$rowtext3 = $rowtext3 . "<td>";
						$oldtourn = $cur['tournament'];
						$lasttourney = $cur['tournid'];
					}
					if($cur['tournament'] == 2)
						$rowtext3 = "'><a href='https://stats.neg5.org/t/" . $cur['tournid'] . "/a?phase=" . $cur['divisionid'];
					elseif($cur['source'] == 1)
						$rowtext3 = $rowtext3 . " <a href='http://naqt.com/stats/tournament-teams.jsp?tournament_id=" . $cur['tournid'];
					else
						$rowtext3 = $rowtext3 . " <a href='http://hsquizbowl.org/db/tournaments/" .
							$cur['tournid'] . "/stats/" . $cur['divisionid'] . "/teamdetail";
					$rowtext3 = $rowtext3 . "'>" . ucfirst(urldecode(str_replace("_", " ", $cur['division']))) . "</a>";
				}
				$rowtext3 = $rowtext3 . "</td></tr>";
				echo($rowtext1 . $rowspan . $rowtext2 . $rowspan . $rowtext3);
			?>
		</table>
	</div>
</div>
