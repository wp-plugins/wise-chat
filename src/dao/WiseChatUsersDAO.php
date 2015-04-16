<?php

/**
 * Wise Chat users DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatUsersDAO {
	const LAST_NAME_ID_OPTION = 'wise_chat_last_name_id';
	const USER_NAME_SESSION_KEY = 'wise_chat_user_name';
	const USER_AUTO_NAME_SESSION_KEY = 'wise_chat_user_name_auto';
	const EVENT_TIME_SESSION_KEY = 'wise_chat_activity_time';
	const EVENT_TIME_THRESHOLD = 120;
	const CURRENT_USERS_TESTING_TIMEFRAME = 180;
	const ABUSES_COUNTER_SESSION_KEY = 'wise_chat_ban_detector_counter';
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Detects whether a WordPress admin is logged in.
	*
	* @return boolean
	*/
	public function isWpUserAdminLogged() {
		return current_user_can('manage_options');
	}
	
	/**
	* Retuns whether there is an user logged in.
	*
	* @return boolean
	*/
	public function isWpUserLogged() {
		if (is_user_logged_in()) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Sets name for the current user of the chat.
	*
	* @return null
	*/
	public function setUserName($userName) {
		$this->startSession();
		
		$badWordsFilter = $this->options->isOptionEnabled('filter_bad_words');
		$filteredUserName = $badWordsFilter ? WiseChatFilter::filter($userName) : $userName;
		
		$_SESSION[self::USER_NAME_SESSION_KEY] = $filteredUserName;
		unset($_SESSION[self::USER_AUTO_NAME_SESSION_KEY]);
	}
	
	/**
	* Retuns name of the current user of the chat.
	*
	* @return string|null
	*/
	public function getUserName() {
		$this->startSession();
		
		if (array_key_exists(self::USER_NAME_SESSION_KEY, $_SESSION)) {
			return $_SESSION[self::USER_NAME_SESSION_KEY];
		} else {
			return null;
		}
	}
	
	/**
	* Retuns WP user by display_name field. Result is cached in static field.
	*
	* @param string $displayName
	*
	* @return WP_User|null
	*/
	public function getWpUserByDisplayName($displayName) {
		static $wpUsersCache = array();
		
		if (array_key_exists($displayName, $wpUsersCache)) {
			return $wpUsersCache[$displayName];
		}
		
		$userObject = null;
		$args = array(
			'search' => $displayName,
			'search_fields' => array('display_name')
		);
		$users = new WP_User_Query($args);
		if (count($users->results) > 0) {
			$userObject = $users->results[0];
		}
		$wpUsersCache[$displayName] = $userObject;
		
		return $userObject;
	}
	
	/**
	* Retuns current WP user or null if nobody is logged in.
	*
	* @return WP_User|null
	*/
	public function getCurrentWpUser() {
		if (is_user_logged_in()) {
			return wp_get_current_user();
		}
		
		return null;
	}
	
	/**
	* Generates a new user name based on the user_name_prefix setting.
	* User name is stored in session.
	*
	* @return null
	*/
	public function generateUserName() {
		$this->startSession();

		if ($this->getUserName() === null) {
			$lastNameId = intval(get_option(self::LAST_NAME_ID_OPTION, 1)) + 1;
			update_option(self::LAST_NAME_ID_OPTION, $lastNameId);
			
			$this->setUserName($this->options->getOption('user_name_prefix', 'Anonymous').get_option(self::LAST_NAME_ID_OPTION));
		}
	}
	
	/**
	* Replaces generated user name with WordPress user name (if the user is logged in).
	* Method preserves originally generated user name in session.
	*
	* @return null
	*/
	public function generateLoggedUserName() {
		$currentUser = $this->getCurrentWpUser();
		
		if ($currentUser !== null) {
			$displayName = $currentUser->display_name;
			if (strlen($displayName) > 0) {
				if ($this->getOriginalUserName() === null) {
					$this->setOriginalUserName($this->getUserName());
				}
				$this->setUserName($displayName);
			}
		} else {
			if ($this->getOriginalUserName() !== null) {
				$this->setUserName($this->getOriginalUserName());
			}
		}
	}
	
	/**
	* Determines whether the time for an event identified by given group and id has elapsed. 
	*
	* @param string $eventGroup Event group
	* @param string $eventId Event id
	*
	* @return boolean
	*/
	public function shouldTriggerEvent($eventGroup, $eventId) {
		$sessionKey = self::EVENT_TIME_SESSION_KEY.md5($eventGroup).'_'.md5($eventId);
		if (!array_key_exists($sessionKey, $_SESSION)) {
			$_SESSION[$sessionKey] = time();
			return true;
		}
		$diff = time() - $_SESSION[$sessionKey];
		if ($diff > self::EVENT_TIME_THRESHOLD) {
			$_SESSION[$sessionKey] = time();
			return true;
		}
		
		return false;
	}
	
	/**
	* Resets tracking of the given event.
	*
	* @param string $eventGroup Event group
	* @param string $eventId Event id
	*
	* @return null
	*/
	public function resetEventTracker($eventGroup, $eventId) {
		$sessionKey = self::EVENT_TIME_SESSION_KEY.md5($eventGroup).'_'.md5($eventId);
		if (array_key_exists($sessionKey, $_SESSION)) {
			unset($_SESSION[$sessionKey]);
		}
	}
	
	/**
	* Increments and returns abuses detector counter.
	* The counter is stored in user's session.
	*
	* @return integer
	*/
	public function incrementAndGetAbusesCounter() {
		$key = self::ABUSES_COUNTER_SESSION_KEY;
		if (!array_key_exists($key, $_SESSION)) {
			$_SESSION[$key] = 1;
		} else {
			$_SESSION[$key] += 1;
		}
		
		return $_SESSION[$key];
	}
	
	/**
	* Clears abuses detector counter. The counter is stored in user's session.
	*
	* @return null
	*/
	public function clearAbusesCounter() {
		$_SESSION[self::ABUSES_COUNTER_SESSION_KEY] = 0;
	}
	
	/**
	* Returns users from given channel from last CURRENT_USERS_TESTING_TIMEFRAME seconds.
	*
	* @param string $channel Channel
	*
	* @return array
	*/
	public function getCurrentUsersOfChannel($channel) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$timeFrame = time() - self::CURRENT_USERS_TESTING_TIMEFRAME;
		$conditions[] = "time > {$timeFrame}";
		$conditions[] = "channel = '{$channel}'";
		$conditions[] = "user != 'System'";
		$sql = "SELECT DISTINCT user AS name FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" ORDER BY user ASC ".
				" LIMIT 1000;";
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Retuns an originally generated name of the user.
	*
	* @return string|null
	*/
	private function getOriginalUserName() {
		$this->startSession();
		
		if (array_key_exists(self::USER_AUTO_NAME_SESSION_KEY, $_SESSION)) {
			return $_SESSION[self::USER_AUTO_NAME_SESSION_KEY];
		} else {
			return null;
		}
	}
	
	/**
	* Sets an originally generated name of the user.
	*
	* @return null
	*/
	private function setOriginalUserName($userName) {
		$_SESSION[self::USER_AUTO_NAME_SESSION_KEY] = $userName;
	}
	
	private function startSession() {
		if (!isset($_SESSION)) {
			session_start();
		}
	}
}