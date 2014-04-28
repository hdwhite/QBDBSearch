<div id="content">
	<div class="entry">
		<h4>How to use</h4>
		<p>This site searches the <a href="http://hsquizbowl.org/db">Quizbowl Resource Database</a> for all stat reports containing a given team or player. Searches are made "as is", and no effort is made to search for common alternate names (such as VT for Virginia Tech). The site is indexed on a more-or-less weekly basis, so recently-posted results may not show up right away. To search for one of multiple different terms, separate the commands with an "OR" (e.g. "VT or Virginia Tech" should show all results for said school).</p><br>
		<p>Currently, there are <?=number_format($numplayers) ?> individual player stats from <?=number_format($numteams) ?> teams in <?=number_format($numtourneys) ?> tournaments indexed on this site.</p>
	</div>
	<?php require("stats/content/form.php"); ?>
</div>
