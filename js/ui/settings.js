/**
 * Wise Chat user settings.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatSettings(options, wiseChatMessages) {
	var settingsEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_settings_endpoint';
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
		var userName = userNameInput.val().replace(/^\s+|\s+$/g, '');
		if (userName.length > 0) {
			customizationsPanel.hide();
			
			jQuery.ajax({
				type: "POST",
				url: settingsEndpoint,
				data: {
					property: 'userName',
					value: userName
				}
			})
			.success(onUserNameChanged);
		}
	};
	
	function onUserNameChanged(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				wiseChatMessages.showErrorMessage(response.error);
			} else {
				currentUserName.html(response.value + ':');
			}
		}
		catch (e) {
			wiseChatMessages.showErrorMessage('Server error: ' + e.toString());
		}
		wiseChatMessages.scrollMessages();
	};
	
	customizeButton.click(onCustomizeButtonClick);
	userNameApproveButton.click(onUserNameApproveButtonClick);
};