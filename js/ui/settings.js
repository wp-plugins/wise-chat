/**
 * Wise Chat user's settings support.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatSettings(options, messages) {
	var settingsEndpoint = options.apiEndpointBase + '?action=wise_chat_settings_endpoint';
	var container = jQuery('#' + options.chatId);
	var currentUserName = container.find('.wcCurrentUserName');
	var customizeButton = container.find('a.wcCustomizeButton');
	var customizationsPanel = container.find('.wcCustomizationsPanel');
	var userNameInput = container.find('.wcCustomizationsPanel input.wcUserName');
	var userNameApproveButton = container.find('.wcCustomizationsPanel input.wcUserNameApprove');
	
	function onCustomizeButtonClick(e) {
		customizationsPanel.toggle();
	};
	
	function onUserNameApproveButtonClick(e) {
		var userNameInputElement = userNameInput[0];
		if (typeof userNameInputElement.checkValidity == 'function') {
			userNameInputElement.checkValidity();
		}
		
		var userName = userNameInput.val().replace(/^\s+|\s+$/g, '');
		if (userName.length > 0) {
			jQuery.ajax({
				type: "POST",
				url: settingsEndpoint,
				data: {
					property: 'userName',
					value: userName
				}
			})
			.success(onUserNameChanged)
			.error(function(jqXHR, textStatus, errorThrown) {
				messages.showErrorMessage('Server error occurred: ' + errorThrown);
				messages.scrollMessages();
			});
		}
	};
	
	function onUserNameChanged(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				messages.showErrorMessage(response.error);
			} else {
				currentUserName.html(response.value + ':');
				customizationsPanel.hide();
			}
		}
		catch (e) {
			messages.showErrorMessage('Server error: ' + e.toString());
		}
		messages.scrollMessages();
	};
	
	customizeButton.click(onCustomizeButtonClick);
	userNameApproveButton.click(onUserNameApproveButtonClick);
};