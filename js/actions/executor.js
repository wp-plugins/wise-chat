/**
 * Wise Chat actions executor. The class is responsible for executing actions requested by the server.
 * The actions may be global or dedicated for specific user.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatActionsExecutor(options, wiseChatMessages) {
	var REFRESH_TIMEOUT = 10000;
	var lastId = options.lastActionId;
	var endpoint = options.apiEndpointBase + '?action=wise_chat_actions_endpoint';
	var isInitialized = false;
	var request = null;
	var idsCache = {};
	
	function initialize() {
		if (isInitialized == true) {
			return;
		}
		isInitialized = true;
		setInterval(checkNewActions, REFRESH_TIMEOUT);
	};
	
	function isRequestStillRunning() {
		return request !== null && request.readyState > 0 && request.readyState < 4;
	}
	
	function checkNewActions() {
		if (isRequestStillRunning()) {
			return;
		}
		
		request = jQuery.ajax({
			url: endpoint,
			data: { lastId: lastId }
		}).success(executeActions);
	};
	
	function executeActions(actionsRaw) {
		try {
			var actions = jQuery.parseJSON(actionsRaw);
			if (actions) {
				for (var x = 0; x < actions.length; x++) {
					var action = actions[x];
					var actionId = action.id;
					if (actionId > lastId) {
						lastId = actionId;
					}
					
					if (!idsCache[actionId]) {
						idsCache[actionId] = true;
						
						switch (action.command.name) {
							case 'deleteMessage':
								deleteMessageAction(action.command.data);
								break;
							case 'deleteAllMessagesFromChannel':
								deleteAllMessagesFromChannelAction(action.command.data);
								break;
							case 'deleteAllMessages':
								deleteAllMessagesAction(action.command.data);
								break;
						}
					}
				}
			}
			
			initialize();
		}
		catch (e) { }
	};
	
	function deleteMessageAction(data) {
		wiseChatMessages.hideMessage(data.id);
	}
	
	function deleteAllMessagesFromChannelAction(data) {
		wiseChatMessages.hideAllMessages();
	}
	
	function deleteAllMessagesAction(data) {
		wiseChatMessages.hideAllMessages();
	}
	
	// public API:
	this.start = function() {
		initialize();
	};
};