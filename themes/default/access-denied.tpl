<link rel='stylesheet' href='{{ themeStyles }}' type='text/css' media='all' />

<div class='wcContainer'>
	{% if showWindowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif showWindowTitle %}
	
	<div class="wcWindowContent">
		<div class='wcError {{ cssClass }}'>{{ errorMessage }}</div>
	</div>
</div>