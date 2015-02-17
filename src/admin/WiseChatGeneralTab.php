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
			array(
				'restrict_to_wp_users', 'Only For Logged In Users', 'booleanFieldCallback', 'boolean',
				'Denies access for anonymous users, only logged in WP users are allowed to see the chat'
			),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'restrict_to_wp_users' => 0
		);
	}
}