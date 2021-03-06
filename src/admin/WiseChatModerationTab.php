<?php 

/**
 * Wise Chat admin moderation settings tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatModerationTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Moderation Settings'),
			array('enable_message_actions', 'Enable Admin Actions', 'booleanFieldCallback', 'boolean', 'Displays ban and removal buttons next to each message. The buttons are visible only for roles defined below'),
			array(
				'permission_delete_message_role', 'Delete Message Permission', 'selectCallback', 'string', 
				'An user role that is allowed to delete posted messages.<br /> Alternatively you can assign "wise_chat_delete_message" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_ban_user_role', 'Ban User Permission', 'selectCallback', 'string',
				'An user role that is allowed to ban users.<br /> Alternatively you can assign "wise_chat_ban_user" capability to any custom role.', self::getRoles()
			),
			array('moderation_ban_duration', 'Ban Duration', 'stringFieldCallback', 'integer', 'Duration of the ban (in minutes) created by clicking on Ban button next a message. Empty field sets the value to 1440 minutes (1 day)'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'enable_message_actions' => 0,
			'permission_delete_message_role' => 'administrator',
			'permission_ban_user_role' => 'administrator',
			'moderation_ban_duration' => 1440,
		);
	}
	
	public function getParentFields() {
		return array(
			'permission_delete_message_role' => 'enable_message_actions',
			'permission_ban_user_role' => 'enable_message_actions',
			'moderation_ban_duration' => 'enable_message_actions'
		);
	}
	
	public function getRoles() {
		$editableRoles = array_reverse(get_editable_roles());
		$rolesOptions = array();

		foreach ($editableRoles as $role => $details) {
			$name = translate_user_role($details['name']);
			$rolesOptions[esc_attr($role)] = $name;
		}
	
		return $rolesOptions;
	}

}