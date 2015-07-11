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
	var muteSoundCheckbox = container.find('.wcCustomizationsPanel input.wcMuteSound');
	
	/**
	 * Saves given property on the server side using AJAX call.
	 * 
	 * @param {String} propertyName
	 * @param {String} propertyValue
	 * @param {Function} successCallback
	 * @param {Function} errorCallback
	 */
	function saveProperty(propertyName, propertyValue, successCallback, errorCallback) {
		jQuery.ajax({
			type: "POST",
			url: settingsEndpoint,
			data: {
				property: propertyName,
				value: propertyValue,
				channel: options.channel
			}
		})
		.success(function(result) {
			onPropertySaveRequestSuccess(result, successCallback);
		})
		.error(function(jqXHR, textStatus, errorThrown) {
			onPropertySaveRequestError(errorThrown, errorCallback);
		});
	}
	
	/**
	 * Processes AJAX success response. 
	 * 
	 * @param {String} result
	 * @param {Function callback
	 */
	function onPropertySaveRequestSuccess(result, callback) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				messages.showErrorMessage(response.error);
			} else {
				if (typeof(callback) != 'undefined') {
					callback.apply(this, [response]);
				}
			}
		}
		catch (e) {
			showServerError(e.toString());
		}
	}
	
	/**
	 * Processes AJAX error response. 
	 * 
	 * @param {String} result
	 * @param {Function callback
	 */
	function onPropertySaveRequestError(errorThrown, callback) {
		showServerError(errorThrown);
		if (typeof(callback) != 'undefined') {
			callback.apply(this, [errorThrown]);
		}
	}
	
	/**
	 * Displays server error. It indicates a serious server-side problem.
	 * 
	 * @param {String} errorMessage
	 */
	function showServerError(errorMessage) {
		messages.showErrorMessage('Server error: ' + errorMessage);
	};
	
	function onUserNameApproveButtonClick(e) {
		var userNameInputElement = userNameInput[0];
		if (typeof (userNameInputElement.checkValidity) == 'function') {
			userNameInputElement.checkValidity();
		}
		
		var userName = userNameInput.val().replace(/^\s+|\s+$/g, '');
		if (userName.length > 0) {
			saveProperty('userName', userName, function(response) {
				currentUserName.html(response.value + ':');
				customizationsPanel.fadeOut();
			});
		}
	};
	
	function onMuteSoundCheckboxChange(e) {
		saveProperty('muteSounds', muteSoundCheckbox.is(':checked'), function(response) {
			options.userSettings.muteSounds = muteSoundCheckbox.is(':checked');
			customizationsPanel.fadeOut();
		});
	}
	
	// DOM events:
	customizeButton.click(function(e) {
		customizationsPanel.toggle();
	});
	userNameApproveButton.click(onUserNameApproveButtonClick);
	muteSoundCheckbox.change(onMuteSoundCheckboxChange);
};