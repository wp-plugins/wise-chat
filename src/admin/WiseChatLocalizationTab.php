<?php 

/**
 * Wise Chat admin localization settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatLocalizationTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array(
				'hint_message', 'Hint Message', 'stringFieldCallback', 'string',
				'A hint message displayed in the message input field'
			),
			array(
				'user_name_prefix', 'User Name Prefix', 'stringFieldCallback', 'string',
				'Anonymous user\'s name prefix'
			),
			array(
				'message_submit_button_caption', 'Submit Button Caption', 'stringFieldCallback', 'string',
				'Caption for message submit button'
			),
			array('message_save', '"Save" message', 'stringFieldCallback', 'string'),
			array('message_name', '"Name" message', 'stringFieldCallback', 'string'),
			array('message_customize', '"Customize" message', 'stringFieldCallback', 'string'),
			
			array('message_error_1', 'Message error #1', 'stringFieldCallback', 'string', 'Message: "Only letters, number, spaces, hyphens and underscores are allowed"'),
			array('message_error_2', 'Message error #2', 'stringFieldCallback', 'string', 'Message: "This name is already occupied"'),
			array('message_error_3', 'Message error #3', 'stringFieldCallback', 'string', 'Message: "You were banned from posting messages"'),
			array('message_error_4', 'Message error #4', 'stringFieldCallback', 'string', 'Message: "Only logged in users are allowed to enter the chat"'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'hint_message' => 'Enter message here',
			'user_name_prefix' => 'Anonymous',
			'message_submit_button_caption' => 'Send',
			'message_save' => 'Save',
			'message_name' => 'Name',
			'message_customize' => 'Customize',
			'message_error_1' => 'Only letters, number, spaces, hyphens and underscores are allowed',
			'message_error_2' => 'This name is already occupied',
			'message_error_3' => 'You were banned from posting messages',
			'message_error_4' => 'Only logged in users are allowed to enter the chat'
		);
	}
}