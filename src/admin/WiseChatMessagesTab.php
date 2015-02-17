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
			array('messages_limit', 'Messages Limit', 'messagesLimitCallback', 'integer'),
			array('message_max_length', 'Message Max Length', 'messageMaxLengthCallback', 'integer'),
			array('filter_bad_words', 'Filter Bad Words', 'filterBadWordsCallback', 'boolean'),
			array('admin_actions', 'Admin Actions', 'adminActionsCallback', 'void'),
			array('allow_post_links', 'Enable Links', 'enableLinksCallback', 'boolean'),
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
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE 1 = 1;");
		$this->addMessage('All messages have been deleted');
	}
	
	public function messageMaxLengthCallback()
	{
		printf(
			'<input type="text" id="message_max_length" name="'.WiseChatSettings::OPTIONS_NAME.'[message_max_length]" value="%s" />
			<p class="description">Maximum length of a message</p>',
			$this->options->getEncodedOption('message_max_length', '')
		);
	}
	
	public function messagesLimitCallback()
	{
		printf(
			'<input type="text" id="messages_limit" name="'.WiseChatSettings::OPTIONS_NAME.'[messages_limit]" value="%s" />
			<p class="description">The limit of messages loaded on start-up</p>',
			$this->options->getEncodedOption('messages_limit', '')
		);
	}
	
	public function filterBadWordsCallback()
	{
		printf(
			'<input type="checkbox" id="filter_bad_words" name="'.WiseChatSettings::OPTIONS_NAME.'[filter_bad_words]" value="1" %s />
			<p class="description">Uses its own dictionary to filter bad words</p>',
			$this->options->isOptionEnabled('filter_bad_words') ? ' checked="1" ' : ''
		);	
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearMessages");
		
		printf(
			'<a class="button-secondary" href="%s" title="Deletes all messages sent to any channel">Clear Messages</a>',
			wp_nonce_url($url)
		);
	}
	
	public function enableLinksCallback() {
		printf(
			'<input type="checkbox" id="allow_post_links" name="'.WiseChatSettings::OPTIONS_NAME.'[allow_post_links]" value="1" %s />
			<p class="description">Converts posted URLs to hyperlinks</p>',
			$this->options->isOptionEnabled('allow_post_links') ? ' checked="1" ' : ''
		);	
	}
}