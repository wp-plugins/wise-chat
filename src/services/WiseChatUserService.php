<?php

/**
 * Wise Chat services regarding users.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatUserService {
	const USER_SETTINGS_COOKIE_NAME = 'wcUserSettings';
	const USERS_ACTIVITY_TIME_FRAME = 80;
	const USERS_PRESENCE_TIME_FRAME = 86400;
	
	/**
	* @var WiseChatActionsDAO
	*/
	private $actionsDAO;

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var array
	*/
	private $defaultSettings = array(
		'muteSounds' => false,
	);
	
	/**
	* @var array
	*/
	private $settingsTypes = array(
		'muteSounds' => 'boolean'
	);
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
		$this->messagesDAO = new WiseChatMessagesDAO();
	}
	
	/**
	* Maintenance actions performetd on init phase.
	*
	* @return null
	*/
	public function initMaintenance() {
		if (!$this->isUserCookieAvailable()) {
			$this->setUserCookie('{}');
		}
	}
	
	/**
	* Maintenance actions performetd at start-up.
	*
	* @param string $channel
	*
	* @return null
	*/
	public function startUpMaintenance($channel) {
		$this->usersDAO->resetEventTracker('usersList', $channel);
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @param string $channel Given channel
	*
	* @return null
	*/
	public function periodicMaintenance($channel) {
		if ($this->usersDAO->shouldTriggerEvent('ping', $channel)) {
			$this->markPresenceInChannel($channel);
		}
		
		$this->refreshChannelUsersData();
	}
	
	/**
	* Refreshes channel users data.
	*
	* @return null
	*/
	public function refreshChannelUsersData() {
		$this->channelUsersDAO->deleteOlderByLastActivityTime(self::USERS_PRESENCE_TIME_FRAME);
		$this->channelUsersDAO->updateActiveForOlderByLastActivityTime(false, self::USERS_ACTIVITY_TIME_FRAME);
	}
	
	/**
	* If the user has logged in replace anonymous user name with WordPress user name.
	* Method preserves originally generated user name in case the user logs out.
	*
	* @return null
	*/
	public function switchUser() {
		$currentWPUser = $this->usersDAO->getCurrentWpUser();
		$currentUserName = $this->usersDAO->getUserName();
		
		if ($currentWPUser !== null) {
			$displayName = $currentWPUser->display_name;
			if (strlen($displayName) > 0 && $currentUserName != $displayName) {
				if ($this->usersDAO->getOriginalUserName() === null) {
					$this->usersDAO->setOriginalUserName($currentUserName);
				}
				
				$this->usersDAO->setUserName($displayName);
				$this->refreshNewUserName($this->usersDAO->getOriginalUserName(), $displayName);
			}
		} else {
			if ($this->usersDAO->getOriginalUserName() !== null && $currentUserName != $this->usersDAO->getOriginalUserName()) {
				$this->usersDAO->setUserName($this->usersDAO->getOriginalUserName());
				$this->refreshNewUserName($currentUserName, $this->usersDAO->getOriginalUserName());
			}
		}
	}
	
	/**
	* Sets a new name for current user.
	*
	* @param string $userName A new name to set
	* @param string $channel Name of the current channel
	*
	* @return string New name
	* @throws Exception If an error occurres
	*/
	public function changeUserName($userName, $channel) {
		if (!$this->options->isOptionEnabled('allow_change_user_name')) {
			throw new Exception('Unsupported operation');
		}
		
		$userName = trim($userName);
		if (strlen($userName) == 0) {
			throw new Exception('User name cannot be empty');
		}
		
		if (!preg_match('/^[a-zA-Z0-9\-_ ]+$/', $userName)) {
			throw new Exception($this->options->getOption('message_error_1', 'Only letters, number, spaces, hyphens and underscores are allowed'));
		}
		
		$occupiedException = new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		
		if ($this->usersDAO->getWpUserByDisplayName($userName) !== null) {
			throw $occupiedException;
		}
		
		if ($this->usersDAO->getWpUserByLogin($userName) !== null) {
			throw $occupiedException;
		}
		
		$prefix = $this->options->getOption('user_name_prefix', 'Anonymous');
		if (preg_match("/^{$prefix}/", $userName) || in_array($userName, array('System'))) {
			throw $occupiedException;
		}
		
		$oldUserName = $this->usersDAO->getUserName();
		$this->usersDAO->setUserName($userName);
		$newUserName = $this->usersDAO->getUserName();
		
		if ($this->channelUsersDAO->isUserNameOccupied($newUserName, session_id())) {
			$this->usersDAO->setUserName($oldUserName);
			throw $occupiedException;
		} else {
			$this->refreshNewUserName($oldUserName, $newUserName, $channel);
			$this->usersDAO->setOriginalUserName($newUserName);
			
			return $newUserName;
		}
	}
	
	/**
	* Sets propertyName-propertyValue pair in the user's settings.
	*
	* @param string $propertyName
	* @param string $propertyValue
	*
	* @throws Exception If an error occurred
	*/
	public function setUserPropertySetting($propertyName, $propertyValue) {
		if (!in_array($propertyName, array_keys($this->defaultSettings))) {
			throw new Exception('Unsupported property');
		}
		
		if ($this->isUserCookieAvailable()) {
			$settings = $this->getUserCookieSettings();
			if (is_array($settings)) {
				$propertyType = $this->settingsTypes[$propertyName];
				if ($propertyType == 'boolean') {
					$propertyValue = $propertyValue == 'true';
				}
				$settings[$propertyName] = $propertyValue;
				$this->setUserCookie(json_encode($settings));
			}
		}
	}
	
	/**
	* Returns all user settings.
	*
	* @return array
	*/
	public function getUserSettings() {
		if ($this->isUserCookieAvailable()) {
			$cookieValue = stripslashes_deep($_COOKIE[self::USER_SETTINGS_COOKIE_NAME]);
			return array_merge($this->defaultSettings, json_decode($cookieValue, true));
		} else {
			return array();
		}
	}
	
	/**
	* Marks presence of the current user in the given channel.
	*
	* @param string $channel
	*
	* @return null
	*/
	private function markPresenceInChannel($channel) {
		$user = $this->usersDAO->getUserName();
		if ($this->channelUsersDAO->getByUserAndChannel($user, $channel) === null) {
			$this->channelUsersDAO->create($user, $channel, session_id(), $this->getRemoteAddress());
		} else {
			$this->channelUsersDAO->updateLastActivityTimeAndActive($user, $channel, time(), true);
		}
	}
	
	/**
	* Refreshes new username.
	*
	* @param string $oldUserName
	* @param string $newUserName
	* @param string $channel
	*
	* @return null
	*/
	private function refreshNewUserName($oldUserName, $newUserName, $channel = null) {
		$this->channelUsersDAO->updateUser($oldUserName, $newUserName);
		$this->usersDAO->resetEventTracker('usersList', $channel);
		$channelUsers = $this->channelUsersDAO->getAllBySessionId(session_id());
		$channelUsersIds = array();
		foreach ($channelUsers as $channelUser) {
			$channelUsersIds[] = $channelUser->id;
		}
		if (count($channelUsersIds) > 0) {
			$this->messagesDAO->updateUserByChanellUsersIds($newUserName, $channelUsersIds);
			
			$renderer = new WiseChatRenderer();
			$messages = $this->messagesDAO->getMessagesByChanellUsersIds($channelUsersIds);
			if (count($messages) > 0) {
				$messagesIds = array();
				$renderedUserName = null;
				foreach ($messages as $message) {
					$messagesIds[] = $message->id;
					if ($renderedUserName === null) {
						$renderedUserName = $renderer->getRenderedUserName($message);
					}
				}
				
				$this->actionsDAO->publishAction('replaceUserNameInMessages', array('renderedUserName' => $renderedUserName, 'messagesIds' => $messagesIds));
			}
		}
	}
	
	/**
	* Returns all settings from the cookie.
	*
	* @return array
	*/
	private function getUserCookieSettings() {
		if ($this->isUserCookieAvailable()) {
			$cookieValue = stripslashes_deep($_COOKIE[self::USER_SETTINGS_COOKIE_NAME]);
			return json_decode($cookieValue, true);
		} else {
			return array();
		}
	}
	
	private function setUserCookie($value) {
		setcookie(self::USER_SETTINGS_COOKIE_NAME, $value, strtotime('+60 days'), '/');
	}
	
	private function isUserCookieAvailable() {
		return array_key_exists(self::USER_SETTINGS_COOKIE_NAME, $_COOKIE);
	}
	
	private function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}