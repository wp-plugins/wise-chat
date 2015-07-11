<?php

/**
 * Wise Chat services regarding users.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatUserService {
	const USER_SETTINGS_COOKIE_NAME = 'wcUserSettings';

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
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
		$this->messagesDAO = new WiseChatMessagesDAO();
	}
	
	/**
	* Initializes a cookie for storing user settings.
	*/
	public function initializeCookie() {
		if (!$this->isUserCookieAvailable()) {
			$this->setUserCookie('{}');
		}
	}
	
	/**
	* Sets a new name for current user.
	*
	* @param string $userName A new name to set
	* @param string $channel Name of the current channel
	*
	* @return string New name
	* @throws Exception If an error occurre
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
		
		$wpUser = $this->usersDAO->getWpUserByDisplayName($userName);
		if ($wpUser !== null) {
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		}
		
		$wpUser = $this->usersDAO->getWpUserByLogin($userName);
		if ($wpUser !== null) {
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		}
		
		if (in_array($userName, array('System'))) {
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		}
		
		$oldUserName = $this->usersDAO->getUserName();
		$this->usersDAO->setUserName($userName);
		$newUserName = $this->usersDAO->getUserName();
		
		$messages = $this->messagesDAO->getLastMessagesByUserName($newUserName);
		if (count($messages) > 0) {
			$this->usersDAO->setUserName($oldUserName);
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		} else {
			$this->messagesDAO->deletePingMessagesByUserName($oldUserName);
			$this->messagesDAO->addPingMessage($newUserName, $channel);
			$this->usersDAO->resetEventTracker('usersList', $channel);
			
			return $newUserName;
		}
	}
	
	/**
	* Sets propertyName-propertyValue pair in the user's settings cookie.
	*
	* @param string $propertyName
	* @param string $propertyValue
	*
	* @throws Exception If an error occurre
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
	* Returns all settings from the cookie.
	*
	* @return array
	*/
	public function getUserCookieSettings() {
		if ($this->isUserCookieAvailable()) {
			$cookieValue = stripslashes_deep($_COOKIE[self::USER_SETTINGS_COOKIE_NAME]);
			return json_decode($cookieValue, true);
		} else {
			return array();
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
	
	private function setUserCookie($value) {
		setcookie(self::USER_SETTINGS_COOKIE_NAME, $value, strtotime('+60 days'), '/');
	}
	
	private function isUserCookieAvailable() {
		return array_key_exists(self::USER_SETTINGS_COOKIE_NAME, $_COOKIE);
	}
}