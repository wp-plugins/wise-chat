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
			array('theme', 'Theme', 'selectCallback', 'string', 'Current theme', WiseChatThemes::getAllThemes()),
			array('background_color', 'Background Color <br />(messages window)', 'colorFieldCallback', 'string', 'Background color of the messages window'),
			array('background_color_input', 'Background Color <br />(message input field)', 'colorFieldCallback', 'string', 'Background color of the message input field'),
			array('text_color', 'Text Color', 'colorFieldCallback', 'string', 'Text color of the messages, inputs and labels'),
			array('text_color_logged_user', 'Text Color<br />(logged in user)', 'colorFieldCallback', 'string', 'Color of the messages typed by a logged in user'),
			array('chat_width', 'Width', 'stringFieldCallback', 'string', 'Width of the chat, a raw number or a number with unit (px or %) is allowed, default: 100%'),
			array('chat_height', 'Height', 'stringFieldCallback', 'string', 'Height of the chat, a raw number or a number with unit (px or %) is allowed, default: 200px'),
			array('show_message_submit_button', 'Show Submit Button', 'booleanFieldCallback', 'boolean', 'Displays the submit button next to the message input field, might be useful on mobile devices'),
			array('show_user_name', 'Show User Name', 'booleanFieldCallback', 'boolean', 'Shows the name of the user, only for users that are not logged in'),
			array('link_wp_user_name', 'Link User Name', 'booleanFieldCallback', 'boolean', 'Converts user name in each message into a link. By default the link directs to the author page and only messages sent by a WordPress user are converted. You can link every user name by checking this option and using the template below.'),
			array('link_user_name_template', 'Link User Name Template', 'stringFieldCallback', 'string', 'The template of the URL used to construct a link from user name. You can use the following variables: id, username. Example: http://my.website.com/users/{username}/profile'),
			array('allow_change_user_name', 'Allow To Change User Name', 'booleanFieldCallback', 'boolean', 'Permits an anonymous user to change his/her name displayed on the chat'),
			array('emoticons_enabled', 'Show Emoticons', 'booleanFieldCallback', 'boolean', 'Shows emoticons'),
			array('multiline_support', 'Multiline Messages', 'booleanFieldCallback', 'boolean', 'Permits multiline messages. Submit button has to be enabled in order to send a message.'),
			array('show_users', 'Show Users List', 'booleanFieldCallback', 'boolean', 'Shows users currently visiting the channel'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'theme' => '',
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => '',
			'text_color_logged_user' => '',
			'chat_width' => '100%',
			'chat_height' => '200px',
			'show_user_name' => 0,
			'link_wp_user_name' => 0,
			'link_user_name_template' => '',
			'show_message_submit_button' => 0,
			'allow_change_user_name' => 0,
			'emoticons_enabled' => 1,
			'multiline_support' => 0,
			'show_users' => 0
		);
	}
}