/**
 * Wise Chat messages sending and displaying. 
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatMessages(options, messagesHistory) {
	var MESSAGES_REFRESH_TIMEOUT = 2000;
	
	var lastId = options.lastId;
	var idsCache = {};
	var channel = options.channel;
	var refresherInitialized = false;
	
	var messagesEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_messages_endpoint';
	var messageEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_message_endpoint';
	
	var container = jQuery('#' + options.chatId);
	var messagesContainer = container.find('.wcMessages');
	var messagesInput = container.find('.wcInput');
	var submitButton = container.find('.wcSubmitButton');
	
	var currentRequest = null;
	
	function scrollMessages() {
		messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
	};
	
	function showMessage(message) {
		var parsedMessage = jQuery(message);
		messagesContainer.append(parsedMessage);
		convertUTCMessagesTime(parsedMessage);
	};
	
	function showErrorMessage(message) {
		messagesContainer.append('<div class="wcMessage wcErrorMessage">' + message + '</div>');
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
	
	function sendMessageRequest(message, channel) {
		jQuery.ajax({
			type: "POST",
			url: messageEndpoint,
			data: {
				channel: channel,
				message: message
			}
		})
		.success(onMessageSent)
		.error(function(jqXHR, textStatus, errorThrown) {
			showErrorMessage('Server error occurred: ' + errorThrown);
			scrollMessages();
		});
	};
	
	function sendMessage() {
		var message = messagesInput.val().replace(/^\s+|\s+$/g, '');
		if (message.length > 0) {
			sendMessageRequest(message, channel);
			messagesInput.val('');
			
			messagesHistory.resetPointer();
			if (messagesHistory.getPreviousMessage() != message) {
				messagesHistory.addMessage(message);
			}
			messagesHistory.resetPointer();
		}
	};
	
	function onInputKeyPress(e) {
		if (e.which == 13) {
			sendMessage();
		}
	};
	
	function onInputKeyDown(e) {
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
	};
	
	function convertUTCMessagesTime(container) {
		container.find('.wcMessageTime').each(function(index, element) {
			element = jQuery(element);
			if (element.html().length === 0) {
				var dateString = element.data('utc');
				var date = convertDateFromISO(dateString);
				var dateFormatStr = 'Y-m-d';
				if (formatDate(new Date(), dateFormatStr) == formatDate(date, dateFormatStr)) {
					element.html(formatDate(date, 'H:i'));
				} else {
					element.html(formatDate(date, dateFormatStr + ' H:i'));
				}
			}
		});
	}
	
	function formatDate(date, format) {
		function makeLeadZero(number) {
			if (number < 10) {
				return '0' + number;
			}
			return number;
		}
		
		
		
		format = format.replace(/Y/, date.getFullYear());
		format = format.replace(/m/, makeLeadZero(date.getMonth() + 1));
		format = format.replace(/d/, makeLeadZero(date.getDate()));
		format = format.replace(/H/, makeLeadZero(date.getHours()));
		format = format.replace(/i/, makeLeadZero(date.getMinutes()));
		
		return format;
	}
	
	function convertDateFromISO(s) {
		s = s.split(/\D/);
		return new Date(Date.UTC(s[0], --s[1]||'', s[2]||'', s[3]||'', s[4]||'', s[5]||'', s[6]||''))
	}
	
	// DOM events:
	messagesInput.keypress(onInputKeyPress);
	messagesInput.keydown(onInputKeyDown);
	submitButton.click(sendMessage);
	
	// public API:
	this.start = function() {
		initializeRefresher();
		scrollMessages();
		convertUTCMessagesTime(container);
	};
	
	//alert(new Date('2015-02-17T22:49:40'));
	
	//alert(formatDate(convertDateFromISO('2015-02-17T22:49:40+00:00'), 'Y-m-d'));
	
	this.scrollMessages = scrollMessages;
	this.showMessage = showMessage;
	this.showErrorMessage = showErrorMessage;
};