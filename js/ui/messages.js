/**
 * Wise Chat messages sending and displaying. 
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatMessages(options, messagesHistory, messageAttachments, dateFormatter) {
	var MESSAGES_REFRESH_TIMEOUT = 2000;
	
	var lastId = options.lastId;
	var idsCache = {};
	var channel = options.channel;
	var refresherInitialized = false;
	
	var messagesEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_messages_endpoint';
	var messageEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_message_endpoint';
	var messageDeleteEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_delete_message_endpoint';
	
	var container = jQuery('#' + options.chatId);
	var messagesContainer = container.find('.wcMessages');
	var usersListContainer = container.find('.wcUsersList');
	var messagesInput = container.find('.wcInput');
	var isMessageMultiline = messagesInput.is("textarea");
	var submitButton = container.find('.wcSubmitButton');
	var currentRequest = null;
	
	function scrollMessages() {
		setTimeout(function() { messagesContainer.scrollTop(messagesContainer[0].scrollHeight); }, 200);
	};
	
	function showMessage(message) {
		var parsedMessage = jQuery(message);
		messagesContainer.append(parsedMessage);
		convertUTCMessagesTime(parsedMessage);
	};
	
	function hideMessage(messageId) {
		container.find('div[data-id="' + messageId + '"]').remove();
	}
	
	function hideAllMessages() {
		container.find('div.wcMessage').remove();
	}
	
	function showErrorMessage(message) {
		messagesContainer.append('<div class="wcMessage wcErrorMessage">' + message + '</div>');
	};
	
	function setBusyState() {
		submitButton.attr('disabled', '1');
		submitButton.attr('readonly', '1');
		messagesInput.attr('placeholder', options.messages.message_sending);
		messagesInput.attr('readonly', '1');
	};
	
	function setIdleState() {
		submitButton.attr('disabled', null);
		submitButton.attr('readonly', null);
		messagesInput.attr('placeholder', options.messages.hint_message);
		messagesInput.attr('readonly', null);
	};
	
	function initializeRefresher() {
		if (refresherInitialized == true) {
			return;
		}
		refresherInitialized = true;
		setInterval(checkNewMessages, MESSAGES_REFRESH_TIMEOUT);
	};
	
	function checkNewMessages() {
		if (currentRequest !== null && currentRequest.readyState > 0 && currentRequest.readyState < 4) {
			return;
		}
		
		currentRequest = jQuery.ajax({
			type: "GET",
			url: messagesEndpoint,
			data: {
				channel: channel,
				lastId: lastId
			}
		}).success(onNewMessagesArrived);
	};
	
	function onNewMessagesArrived(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.result) {
				for (var x = 0; x < response.result.length; x++) {
					var msg = response.result[x];
					var messageId = msg['id'];
					if (messageId > lastId) {
						lastId = messageId;
					}
					if (!idsCache[messageId]) {
						showMessage(msg['text']);
						idsCache[messageId] = true;
					}
				}
			}
			if (response.actions) {
				for (var actionName in response.actions) {
					if (actionName == 'refreshUsersList') {
						refreshUsersList(response.actions['refreshUsersList'].data);
					}
				}
			}
			initializeRefresher();
			
			if (response.result.length > 0) {
				scrollMessages();
			}
		}
		catch (e) {
			showErrorMessage('Server error: ' + e.toString());
		}
	};
	
	function onMessageSent(result) {
		setIdleState();
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				showErrorMessage(response.error);
			}
		}
		catch (e) {
			showErrorMessage('Unknown error occurred: ' + e.toString());
		}
		scrollMessages();
	};
	
	function sendMessageRequest(message, channel, attachments) {
		setBusyState();
		jQuery.ajax({
			type: "POST",
			url: messageEndpoint,
			data: {
				attachments: attachments,
				channel: channel,
				message: message
			}
		})
		.success(onMessageSent)
		.error(function(jqXHR, textStatus, errorThrown) {
			setIdleState();
			showErrorMessage('Server error occurred: ' + errorThrown);
			scrollMessages();
		});
	};
	
	function sendMessage() {
		var message = messagesInput.val().replace(/^\s+|\s+$/g, '');
		var attachments = messageAttachments.getAttachments();
		messageAttachments.clearAttachments();
		
		if (message.length > 0 || attachments.length > 0) {
			sendMessageRequest(message, channel, attachments);
			
			messagesInput.val('');
			messagesInput.focus();
			
			if (!isMessageMultiline && message.length > 0) {
				messagesHistory.resetPointer();
				if (messagesHistory.getPreviousMessage() != message) {
					messagesHistory.addMessage(message);
				}
				messagesHistory.resetPointer();
			}
		}
	};
	
	function onInputKeyPress(e) {
		if (!isMessageMultiline && e.which == 13) {
			sendMessage();
		}
	};
	
	function onInputKeyDown(e) {
		if (!isMessageMultiline) {
			var keyCode = e.which;
			var messageCandidate = null;
			
			if (keyCode == 38) {
				messageCandidate = messagesHistory.getPreviousMessage();
			} else if (keyCode == 40) {
				messageCandidate = messagesHistory.getNextMessage();
			}
			if (messageCandidate !== null) {
				messagesInput.val(messageCandidate);
			}
		}
	};
	
	function convertUTCMessagesTime(container) {
		container.find('.wcMessageTime').each(function(index, element) {
			element = jQuery(element);
			if (element.html().length === 0) {
				var date = dateFormatter.parseISODate(element.data('utc'));
				var dateFormatStr = 'Y-m-d';
				if (dateFormatter.formatDate(new Date(), dateFormatStr) == dateFormatter.formatDate(date, dateFormatStr)) {
					element.html(dateFormatter.formatDate(date, 'H:i'));
				} else {
					element.html(dateFormatter.formatDate(date, dateFormatStr + ' H:i'));
				}
			}
		});
	}
	
	function refreshUsersList(data) {
		var users = [];
		for (var x = 0; x < data.length; x++) {
			users.push(data[x].name);
		}
		usersListContainer.html(users.join('<br />'));
	}
	
	function onWindowResize() {
		if (container.width() < 300) {
			container.addClass('wcWidth300');
		} else {
			container.removeClass('wcWidth300');
		}
	}
	
	function onMessageDelete() {
		if (!confirm('Are you sure you want to delete this message?')) {
			return;
		}
		
		var deleteButton = jQuery(this);
		var messageId = deleteButton.data('id');
		jQuery.ajax({
			type: "POST",
			url: messageDeleteEndpoint,
			data: {
				channel: channel,
				messageId: messageId
			}
		})
		.success(function() {
			jQuery(deleteButton).parent().remove();
		})
		.error(function(jqXHR, textStatus, errorThrown) {
			showErrorMessage('Server error occurred: ' + errorThrown);
			scrollMessages();
		});
	}
	
	function attachEventListeners() {
		container.on('click', 'a.wcMessageDeleteButton', onMessageDelete);
	}
	
	// DOM events:
	messagesInput.keypress(onInputKeyPress);
	messagesInput.keydown(onInputKeyDown);
	submitButton.click(sendMessage);
	jQuery(window).resize(onWindowResize);
	
	// public API:
	this.start = function() {
		initializeRefresher();
		scrollMessages();
		convertUTCMessagesTime(container);
		onWindowResize();
		attachEventListeners();
	};
	
	this.scrollMessages = scrollMessages;
	this.showMessage = showMessage;
	this.showErrorMessage = showErrorMessage;
	this.hideMessage = hideMessage;
	this.hideAllMessages = hideAllMessages;
};