<?php 

/**
 * Wise Chat admin appearance settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatAppearanceTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('background_color', 'Background Color <br />(messages window)', 'backgroundColorCallback', 'string'),
			array('background_color_input', 'Background Color <br />(message input field)', 'backgroundColorInputCallback', 'string'),
			array('text_color', 'Text Color', 'textColorCallback', 'string'),
			array('text_color_logged_user', 'Text Color<br />(logged in user)', 'textColorLoggedUserCallback', 'string'),
			array('show_message_submit_button', 'Show Submit Button', 'showSubmitButtonCallback', 'boolean'),
			array('show_user_name', 'Show User Name', 'showUserNameCallback', 'boolean'),
			array('link_wp_user_name', 'Link WP User Name', 'linkWpUserNameCallback', 'boolean'),
			array('allow_change_user_name', 'Allow To Change User Name', 'allowChangeUserNameCallback', 'boolean'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => '',
			'text_color_logged_user' => '',
			'show_user_name' => 0,
			'link_wp_user_name' => 0,
			'show_message_submit_button' => 0,
			'allow_change_user_name' => 0,
		);
	}
	
	public function backgroundColorCallback()
	{
		printf(
			'<input type="text" id="background_color" name="'.WiseChatSettings::OPTIONS_NAME.'[background_color]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the messages window</p>',
			$this->options->getEncodedOption('background_color', '')
		);	
	}
	
	public function backgroundColorInputCallback()
	{
		printf(
			'<input type="text" id="background_color_input" name="'.WiseChatSettings::OPTIONS_NAME.'[background_color_input]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the message input field</p>',
			$this->options->getEncodedOption('background_color_input', '')
		);	
	}
	
	public function textColorCallback()
	{
		printf(
			'<input type="text" id="text_color" name="'.WiseChatSettings::OPTIONS_NAME.'[text_color]" value="%s" class="wc-color-picker" />
			<p class="description">Text color of the messages, inputs and labels</p>',
			$this->options->getEncodedOption('text_color', '')
		);	
	}
	
	public function textColorLoggedUserCallback()
	{
		printf(
			'<input type="text" id="text_color_logged_user" name="'.WiseChatSettings::OPTIONS_NAME.'[text_color_logged_user]" value="%s" class="wc-color-picker" />
			<p class="description">Color of the messages typed by a logged in user</p>',
			$this->options->getEncodedOption('text_color_logged_user', '')
		);	
	}
	
	public function showUserNameCallback()
	{
		printf(
			'<input type="checkbox" id="show_user_name" name="'.WiseChatSettings::OPTIONS_NAME.'[show_user_name]" value="1" %s />
			<p class="description">Shows the name of the user, only for users that are not logged in</p>',
			$this->options->isOptionEnabled('show_user_name') ? ' checked="1" ' : ''
		);	
	}
	
	public function linkWpUserNameCallback()
	{
		printf(
			'<input type="checkbox" id="link_wp_user_name" name="'.WiseChatSettings::OPTIONS_NAME.'[link_wp_user_name]" value="1" %s />
			<p class="description">Link user name to the author page in each message, only for messages typed by WordPress user</p>',
			$this->options->isOptionEnabled('link_wp_user_name') ? ' checked="1" ' : ''
		);	
	}
	
	public function showSubmitButtonCallback()
	{
		printf(
			'<input type="checkbox" id="show_message_submit_button" name="'.WiseChatSettings::OPTIONS_NAME.'[show_message_submit_button]" value="1" %s />
			<p class="description">Displays the submit button next to the message input field, might be useful on mobile devices</p>',
			$this->options->isOptionEnabled('show_message_submit_button') ? ' checked="1" ' : ''
		);	
	}
	
	public function allowChangeUserNameCallback()
	{
		printf(
			'<input type="checkbox" id="allow_change_user_name" name="'.WiseChatSettings::OPTIONS_NAME.'[allow_change_user_name]" value="1" %s />
			<p class="description">Permits an anonymous user to change his/her name displayed on the chat</p>',
			$this->options->isOptionEnabled('allow_change_user_name') ? ' checked="1" ' : ''
		);	
	}
}