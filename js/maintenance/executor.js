/**
 * Wise Chat maintenance services.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatMaintenanceExecutor(options, wiseChatMessages) {
	var REFRESH_TIMEOUT = 10000;
	var ENDPOINT_URL = options.apiEndpointBase + '?action=wise_chat_maintenance_endpoint';
	var lastActionId = options.lastActionId;
	var isInitialized = false;
	var request = null;
	var actionsIdsCache = {};
	
	function initialize() {
		if (isInitialized == true) {
			return;
		}
		isInitialized = true;
		performMaintenanceRequest();
		setInterval(performMaintenanceRequest, REFRESH_TIMEOUT);
	};
	
	function isRequestStillRunning() {
		return request !== null && request.readyState > 0 && request.readyState < 4;
	}
	
	function performMaintenanceRequest() {
		if (isRequestStillRunning()) {
			return;
		}
		
		request = jQuery.ajax({
			url: ENDPOINT_URL,
			data: {
				lastActionId: lastActionId, 
				channel: options.channel, 
				checksum: options.checksum
			}
		})
		.success(analyzeResponse)
		.error(function(jqXHR, textStatus, errorThrown) {
			wiseChatMessages.showErrorMessage('Maintenance server error occurred: ' + errorThrown);
		});
	};
	
	function analyzeResponse(data) {
		try {
			var maintenance = jQuery.parseJSON(data);
			
			if (typeof(maintenance.actions) !== 'undefined') {
				executeActions(maintenance.actions);
			}
			if (typeof(maintenance.events) !== 'undefined') {
				handleEvents(maintenance.events);
			}
			if (typeof(maintenance.error) !== 'undefined') {
				wiseChatMessages.showErrorMessage('Maintenance error occurred: ' + maintenance.error);
			}
		}
		catch (e) {
			wiseChatMessages.showErrorMessage('Maintenance corrupted data: ' + e.message);
		}
	};
	
	function executeActions(actions) {
		for (var x = 0; x < actions.length; x++) {
			var action = actions[x];
			var actionId = action.id;
			var commandName = action.command.name;
			var commandData = action.command.data;
			if (actionId > lastActionId) {
				lastActionId = actionId;
			}
			
			if (!actionsIdsCache[actionId]) {
				actionsIdsCache[actionId] = true;
				
				switch (commandName) {
					case 'deleteMessage':
						wiseChatMessages.hideMessage(commandData.id);
						break;
					case 'deleteMessages':
						deleteMessagesAction(commandData);
						break;
					case 'deleteAllMessagesFromChannel':
						if (commandData.channel == options.channel) {
							wiseChatMessages.hideAllMessages();
						}
						break;
					case 'deleteAllMessages':
						wiseChatMessages.hideAllMessages();
						break;
					case 'replaceUserNameInMessages':
						wiseChatMessages.replaceUserNameInMessages(commandData.renderedUserName, commandData.messagesIds);
						break;
				}
			}
		}
	};
	
	function handleEvents(events) {
		for (var x = 0; x < events.length; x++) {
			var event = events[x];
			var eventData = event.data;
			
			switch (event.name) {
				case 'refreshUsersList':
					wiseChatMessages.refreshUsersList(eventData);
					break;
				case 'refreshUsersCounter':
					wiseChatMessages.refreshUsersCounter(eventData);
					break;
			}
		}
	};
	
	function deleteMessagesAction(data) {
		for (var x = 0; x < data.ids.length; x++) {
			wiseChatMessages.hideMessage(data.ids[x]);
		}
	};
	
	// public API:
	this.start = initialize;
};