<div class="entry">
	<form method="get" action="/qb/stats/search/">
		<h4>Find a team</h4>
		<p>Team name: <input type="text" name="team" id="team" size="15" value="<?=isset($teamsearch) ? $teamsearch : "" ?>"></p>
		<p><input type="submit" name="teamsearch" id="teamsearch" value="Search"></p>
	</form>
</div>
<div class="entry">
	<form method="get" action="/qb/stats/search/">
		<h4>Find a player</h4>
		<p>Player name: <input type="text" name="player" id="player" size="15" value="<?=isset($playersearch) ? $playersearch : "" ?>"></p>
		<p>Team name (optional): <input type="text" name="team" id="team" size="15" value="<?=isset($teamsearch) ? $teamsearch : "" ?>"></p>
		<p><input type="submit" name="playersearch" id="playersearch" value="Search"></p>
	</form>
</div>
<div class="entry">
	<h4><a href="javascript:toggle('modifiers')">Search Modifiers</a></h4>
	<div id="modifiers" style="display:none"><ul>
	<li><b>Matt</b> - Searches for the name "Matt"</li>
	<li><b>Matt*</b> - Searches for "Matt", "Matthew", etc.</li>
	<li><b>Matt William</b> - Searches for someone with "Matt" and "William" in their name</li>
	<li><b>?Matt ?William</b> - Searches for "Matt" or "William"</li>
	<li><b>"Matt William"</b> - Searches for the exact string "Matt William"</li>
	<li><b>Matt -William</b> - Searches for Matt but excluding anything containing William</li>
	<li><b>Matt W</b> - Will ignore the 'W' since search strings have a 2-character minimum</li>
	<li><b>"Matt W"</b> - Will search for "Matt W"</li>
	<ul></div>
</div>
