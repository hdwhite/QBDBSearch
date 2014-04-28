<div class="entry">
	<form method="get" action="/qb/stats/search/">
		<h4>Find a team</h4>
		<p>Team name: <input type="text" name="team" id="team" size="15" value="<?=$teamsearch ?>"></p>
		<p><input type="submit" name="teamsearch" id="teamsearch" value="Search"></p>
	</form>
</div>
<div class="entry">
	<form method="get" action="/qb/stats/search/">
		<h4>Find a player</h4>
		<p>Player name: <input type="text" name="player" id="player" size="15" value="<?=$playersearch ?>"></p>
		<p>Team name (optional): <input type="text" name="team" id="team" size="15" value="<?=$teamsearch ?>"></p>
		<p><input type="submit" name="playersearch" id="playersearch" value="Search"></p>
	</form>
</div>
