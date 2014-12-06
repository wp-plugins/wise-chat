/**
 * Wise Chat core JS
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatController(options) {
	// fields:
	this.lastId = options.lastId;
	this.ajaxMessagesRefreshTime = 2000;
	this.ajaxMessagesBackend = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_get_messages';
	this.ajaxMessagesHandlerBackend = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_handle_message';
	this.container = jQuery('#' + options.chatId);
	this.messagesContainer = this.container.find('.wcMessages');
	this.messagesInput = this.container.find('.wcInput');
	this.channel = options.channel;
	this.refresherInitialized = false;
	
	// methods:
	this.scrollMessages = function() {
		this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
	};
	
	this.showMessage = function(message) {
		this.messagesContainer.append(message);
		this.messagesContainer.append('<br />');
	};
	
	this.showErrorMessage = function(message) {
		this.messagesContainer.append('<span class="wcErrorMessage">' + message + '</span>');
		this.messagesContainer.append('<br />');
	};
	
	this.initializeRefresher = function() {
		if (this.refresherInitialized == true) {
			return;
		}
		
		this.refresherInitialized = true;
		setInterval(jQuery.proxy(function() {
			jQuery.ajax({
				type: "GET",
				url: this.ajaxMessagesBackend,
				data: {
					channel: this.channel,
					lastId: this.lastId
				}
			})
			.success(jQuery.proxy(this.onNewMessagesArrived, this));
		}, this), this.ajaxMessagesRefreshTime);
	};
	
	this.onNewMessagesArrived = function(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.result) {
				for (var x = 0; x < response.result.length; x++) {
					var msg = response.result[x];
					this.showMessage(msg['text']);
					this.lastId = msg['id'];
				}
			}
			this.initializeRefresher();
			
			if (response.result.length > 0) {
				this.scrollMessages();
			}
		}
		catch (e) { 
			this.showErrorMessage('Corrupted messages received: ' + e.toString());
		}
	};
	
	this.onMessageSent = function(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				this.showErrorMessage('Error while sending message: ' + response.error);
			}
		}
		catch (e) {
			this.showErrorMessage('Unknown error while sending message: ' + e.toString());
		}
		this.scrollMessages();
	};
	
	this.sendMessage = function(message, channel) {
		jQuery.ajax({
			type: "POST",
			url: this.ajaxMessagesHandlerBackend,
			data: {
				channel: channel,
				message: message
			}
		})
		.success(jQuery.proxy(this.onMessageSent, this));
	};
	
	this.onInputKeyPress = function(e) {
		if (e.which == 13 && this.messagesInput.val().trim().length > 0) {
			this.sendMessage(this.messagesInput.val(), this.channel);
			this.messagesInput.val('');
		};
	};
	
	// init:
	this.initializeRefresher();
	this.messagesInput.keypress(jQuery.proxy(this.onInputKeyPress, this));
	this.scrollMessages();
};