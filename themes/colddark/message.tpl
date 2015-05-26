{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}">
	<span class="wcMessageUser">
		{{ renderedUserName }}
	</span>
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}"></span>
	
	<br class='wcClear' />
	<span class="wcMessageContent">
		{{ messageContent }}
		
		{% if showAdminActions %}
			<a href="javascript://" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"><img src='{{ baseDir }}/gfx/icons/x.png' class='wcIcon' /></a>
			<br class='wcClear' />
		{% endif showAdminActions %}
	</span>
</div>