<?php 

/**
 * Wise Chat admin messages settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatMessagesTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('messages_limit', 'Messages Limit', 'stringFieldCallback', 'integer', 'The limit of messages loaded on start-up'),
			array('message_max_length', 'Message Max Length', 'stringFieldCallback', 'integer', 'Maximum length of a message'),
			array('filter_bad_words', 'Filter Bad Words', 'booleanFieldCallback', 'boolean', 'Uses its own dictionary to filter bad words'),
			array('admin_actions', 'Admin Actions', 'adminActionsCallback', 'void'),
			array('allow_post_links', 'Enable Links', 'booleanFieldCallback', 'boolean', 'Converts posted URLs to hyperlinks'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'messages_limit' => 30,
			'message_max_length' => 400,
			'filter_bad_words' => 1,
			'allow_post_links' => 0,
			'admin_actions' => null
		);
	}
	
	public function clearMessagesAction() {
		$this->messagesDAO->deleteAll();
		$this->addMessage('All messages have been deleted');
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearMessages");
		
		printf(
			'<a class="button-secondary" href="%s" title="Deletes all messages sent to any channel">Clear Messages</a>',
			wp_nonce_url($url)
		);
	}
}