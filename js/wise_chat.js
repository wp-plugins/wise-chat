/**
 * Wise Chat core controller.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatController(options) {
	var messagesHistory = new WiseChatMessagesHistory();
	var messages = new WiseChatMessages(options, messagesHistory);
	var settings = new WiseChatSettings(options, messages);
	
	messages.start();
};