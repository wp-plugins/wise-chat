{% variable containerClasses %}
	wcContainer 
	{% if showMessageSubmitButton %} wcControlsButtonsIncluded {% endif showMessageSubmitButton %}
	{% if enableImagesUploader %} wcControlsButtonsIncluded {% endif enableImagesUploader %}
	{% if showUsers %} wcUsersListIncluded {% endif showUsers %}
{% endvariable containerClasses %}

<div id='{{ chatId }}' class='{{ containerClasses }}'>
	{% if showWindowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif showWindowTitle %}
	
	<div class='wcMessages'>{{ messages }}</div>
	
	{% if showUsers %}
		<div class='wcUsersList'>&nbsp;</div><br class='wcClear' />
	{% endif showUsers %}
	
	<div class="wcControls">
		{% if showUserName %}
			<span class='wcCurrentUserName'>{{ currentUserName }}:</span>
		{% endif showUserName %}
		
		{% if showMessageSubmitButton %}
			<input type='button' class='wcSubmitButton' value='{{ messageSubmitButtonCaption }}' />
		{% endif showMessageSubmitButton %}
		
		{% if enableImagesUploader %}
			<a href="javascript://" class="wcAddImageAttachment"><input type="file" accept="image/*;capture=camera" class="wcImageUploadFile" /></a>
		{% endif enableImagesUploader %}
		
		<div class='wcInputContainer'>
			{% if multilineSupport %}
				<textarea class='wcInput' maxlength='{{ messageMaxLength }}' placeholder='{{ hintMessage }}'></textarea>
			{% endif multilineSupport %}
			{% if !multilineSupport %}
				<input class='wcInput' type='text' maxlength='{{ messageMaxLength }}' placeholder='{{ hintMessage }}' />
			{% endif multilineSupport %}
		</div>
		
		{% if enableImagesUploader %}
			<div class="wcMessageAttachments" style="display: none;">
				<img class="wcImageUploadPreview" />
				<a href="javascript://" class="wcImageUploadClear"><img src='{{ baseDir }}/gfx/icons/x.png' class='wcIcon' /></a>
			</div>
		{% endif enableImagesUploader %}
		
		{% if showCustomizationsPanel %}
			<div class='wcCustomizations'>
				<a href='javascript://' class='wcCustomizeButton'>{{ messageCustomize }}</a>
				<div class='wcCustomizationsPanel' style='display:none;'>
					<label>{{ messageName }}: <input class='wcUserName' type='text' value='{{ currentUserName }}' required /></label>
					
					<input class='wcUserNameApprove' type='button' value='{{ messageSave }}' />
				</div>
			</div>
		{% endif showCustomizationsPanel %}
	</div>
</div>

{{ cssDefinitions }}

<script type='text/javascript'>
	jQuery(window).load(function() {  
		new WiseChatController({{ jsOptions }}); 
	}); 
</script>
