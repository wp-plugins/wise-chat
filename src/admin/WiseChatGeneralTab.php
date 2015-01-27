<?php 

/**
 * Wise Chat admin general settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatGeneralTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('messages_limit', 'Messages Limit', 'messagesLimitCallback'),
			array('hint_message', 'Hint Message', 'hintMessageCallback'),
			array('message_max_length', 'Message Max Length', 'messageMaxLengthCallback'),
			array('user_name_prefix', 'User Name Prefix', 'userNamePrefixCallback'),
			array('filter_bad_words', 'Filter Bad Words', 'filterBadWordsCallback'),
			array('allow_change_user_name', 'Allow To Change User Name', 'allowChangeUserNameCallback'),
			array('admin_actions', 'Admin Actions', 'adminActionsCallback'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'messages_limit' => 30,
			'hint_message' => 'Enter message here',
			'message_max_length' => 400,
			'user_name_prefix' => 'Anonymous',
			'filter_bad_words' => 1,
			'allow_change_user_name' => 0,
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
			isset( $this->options['message_max_length'] ) ? esc_attr( $this->options['message_max_length']) : ''
		);
	}
	
	public function messagesLimitCallback()
	{
		printf(
			'<input type="text" id="messages_limit" name="'.WiseChatSettings::OPTIONS_NAME.'[messages_limit]" value="%s" />
			<p class="description">The limit of messages loaded on start-up</p>',
			isset( $this->options['messages_limit'] ) ? esc_attr( $this->options['messages_limit']) : ''
		);
	}

	public function hintMessageCallback()
	{
		printf(
			'<input type="text" id="hint_message" name="'.WiseChatSettings::OPTIONS_NAME.'[hint_message]" value="%s" />
			<p class="description">A hint message displayed in the input field</p>',
			isset( $this->options['hint_message'] ) ? esc_attr( $this->options['hint_message']) : ''
		);
	}
	
	public function userNamePrefixCallback()
	{
		printf(
			'<input type="text" id="user_name_prefix" name="'.WiseChatSettings::OPTIONS_NAME.'[user_name_prefix]" value="%s" />
			<p class="description">User\'s name prefix</p>',
			isset( $this->options['user_name_prefix'] ) ? esc_attr( $this->options['user_name_prefix']) : ''
		);
	}
	
	public function filterBadWordsCallback()
	{
		printf(
			'<input type="checkbox" id="filter_bad_words" name="'.WiseChatSettings::OPTIONS_NAME.'[filter_bad_words]" value="1" %s />
			<p class="description">Uses its own dictionary to filter bad words</p>',
			$this->options['filter_bad_words'] == '1' ? ' checked="1" ' : ''
		);	
	}
	
	public function allowChangeUserNameCallback()
	{
		printf(
			'<input type="checkbox" id="allow_change_user_name" name="'.WiseChatSettings::OPTIONS_NAME.'[allow_change_user_name]" value="1" %s />
			<p class="description">Permits an anonymous user to change his/her name displayed on the chat</p>',
			$this->options['allow_change_user_name'] == '1' ? ' checked="1" ' : ''
		);	
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearMessages");
		
		printf(
			'<a class="button-secondary" href="%s" title="Clears all messages sent to any channel">Clear Messages</a>',
			wp_nonce_url($url)
		);
	}
	
	public function sanitizeOptionValue($input) {
		$new_input = array();
		if (isset($input['messages_limit'])) {
			$new_input['messages_limit'] = absint($input['messages_limit']);
		}
		
		if (isset($input['filter_bad_words']) && $input['filter_bad_words'] == '1') {
			$new_input['filter_bad_words'] = 1;
		} else {
			$new_input['filter_bad_words'] = 0;
		}
		
		if (isset($input['allow_change_user_name']) && $input['allow_change_user_name'] == '1') {
			$new_input['allow_change_user_name'] = 1;
		} else {
			$new_input['allow_change_user_name'] = 0;
		}
		
		if (isset($input['message_max_length'])) {
			$new_input['message_max_length'] = absint($input['message_max_length']);
		}

		if (isset($input['hint_message'])) {
			$new_input['hint_message'] = sanitize_text_field($input['hint_message']);
		}
		
		if (isset($input['user_name_prefix'])) {
			$new_input['user_name_prefix'] = sanitize_text_field($input['user_name_prefix']);
		}

		return $new_input;
	}
}