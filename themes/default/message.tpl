{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}">
	{% if showAdminActions %}
		<a href="javascript://" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"><img src='{{ baseDir }}/gfx/icons/x.png' class='wcIcon' /></a>
	{% endif showAdminActions %}
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}"></span> 
	
	<span class="wcMessageUser">
		{{ renderedUserName }}: 
	</span>
	<span class="wcMessageContent">
		{{ messageContent }}
	</span>
</div>