/**
 * Wise Chat core JS
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatController(options) {
	this.lastId = options.lastId;
	this.ajaxMessagesRefreshTime = 2000;
	this.messagesEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_messages_endpoint';
	this.messageEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_message_endpoint';
	this.settingsEndpoint = options.siteURL + '/wp-admin/admin-ajax.php?action=wise_chat_settings_endpoint';
	this.container = jQuery('#' + options.chatId);
	this.messagesContainer = this.container.find('.wcMessages');
	this.messagesInput = this.container.find('.wcInput');
	this.currentUserName = this.container.find('.wcCurrentUserName');
	this.customizeButton = this.container.find('a.wcCustomizeButton');
	this.customizationsPanel = this.container.find('.wcCustomizationsPanel');
	this.userNameInput = this.container.find('.wcCustomizationsPanel input.wcUserName');
	this.userNameApproveButton = this.container.find('.wcCustomizationsPanel input.wcUserNameApprove');
	this.channel = options.channel;
	this.refresherInitialized = false;
	
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
				url: this.messagesEndpoint,
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
				this.showErrorMessage(response.error);
			}
		}
		catch (e) {
			this.showErrorMessage('Unknown error: ' + e.toString());
		}
		this.scrollMessages();
	};
	
	this.sendMessage = function(message, channel) {
		jQuery.ajax({
			type: "POST",
			url: this.messageEndpoint,
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
	
	this.onCustomizeButtonClick = function(e) {
		this.customizationsPanel.toggle();
	};
	
	this.onUserNameApproveButtonClick = function(e) {
		var userName = this.userNameInput.val().trim();
		if (userName.length > 0) {
			this.customizationsPanel.hide();
			
			jQuery.ajax({
				type: "POST",
				url: this.settingsEndpoint,
				data: {
					property: 'userName',
					value: userName
				}
			})
			.success(jQuery.proxy(this.onUserNameChanged, this));
		}
	};
	
	this.onUserNameChanged = function(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				this.showErrorMessage(response.error);
			} else {
				this.currentUserName.html(response.value + ':');
			}
		}
		catch (e) {
			this.showErrorMessage('Unknown error: ' + e.toString());
		}
		this.scrollMessages();
	};
	
	// init:
	this.initializeRefresher();
	this.messagesInput.keypress(jQuery.proxy(this.onInputKeyPress, this));
	this.customizeButton.click(jQuery.proxy(this.onCustomizeButtonClick, this));
	this.userNameApproveButton.click(jQuery.proxy(this.onUserNameApproveButtonClick, this));
	this.scrollMessages();
};