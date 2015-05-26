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
			array('messages_limit', 'Messages Limit', 'stringFieldCallback', 'integer', 'Maximal quantity of messages loaded on start-up'),
			array('message_max_length', 'Message Max Length', 'stringFieldCallback', 'integer', 'Maximum length of a message'),
			array('allow_post_links', 'Enable Links', 'booleanFieldCallback', 'boolean', 'Converts posted URLs to hyperlinks'),
			array('allow_post_images', 'Enable Images', 'booleanFieldCallback', 'boolean', 'Downloads posted images (links pointing to images) into Media Library and displays them'),
			array('enable_images_uploader', 'Enable Images Uploader', 'booleanFieldCallback', 'boolean', 'Enables uploading of pictures either from local storage or from the camera (on a mobile device). <br />In order to see uploaded picture "Enable Images" option has to be enabled'),
			array('enable_message_actions', 'Enable Admin Actions', 'booleanFieldCallback', 'boolean', 'Displays removal button next to each message'),
			array('enable_twitter_hashtags', 'Enable Twitter Hashtags', 'booleanFieldCallback', 'boolean', 'Detects Twitter hashtags and converts them to links'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'messages_limit' => 30,
			'message_max_length' => 400,
			'allow_post_links' => 0,
			'allow_post_images' => 0,
			'enable_images_uploader' => 0,
			'enable_message_actions' => 0,
			'enable_twitter_hashtags' => 0
		);
	}
}