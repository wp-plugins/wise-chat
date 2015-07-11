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
	const ABUSES_COUNTER_SESSION_KEY = 'wise_chat_ban_detector_counter';
	const CURRENT_USERS_TESTING_TIMEFRAME = 80;
	
	/**
	* @var array Events thresholds in minutes
	*/
	private $eventTimeThresholds = array(
		'usersList' => 20,
		'ping' => 40,
		'default' => 120
	);
	
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
		global $wpdb;
		static $wpUsersCache = array();
		
		if (array_key_exists($displayName, $wpUsersCache)) {
			return $wpUsersCache[$displayName];
		}

		$userRow = $wpdb->get_row($wpdb->prepare(
			"SELECT `ID` FROM {$wpdb->users} WHERE `display_name` = %s", $displayName
		));
		if ($userRow === null) {
			$wpUsersCache[$displayName] = null;
		} else {
			$userObject = null;
			$args = array(
				'search' => $userRow->ID,
				'search_columns' => array('ID')
			);
			$users = new WP_User_Query($args);
			if (count($users->results) > 0) {
				$userObject = $users->results[0];
			}
			$wpUsersCache[$displayName] = $userObject;
		}
		
		return $wpUsersCache[$displayName];
	}
	
	/**
	* Retuns WP user by user_login field. Result is cached in static field.
	*
	* @param string $userLogin
	*
	* @return WP_User|null
	*/
	public function getWpUserByLogin($userLogin) {
		global $wpdb;
		static $wpUsersCache = array();
		
		if (array_key_exists($userLogin, $wpUsersCache)) {
			return $wpUsersCache[$userLogin];
		}
		
		$userObject = null;
		$args = array(
			'search' => $userLogin,
			'search_columns' => array('user_login')
		);
		$users = new WP_User_Query($args);
		if (count($users->results) > 0) {
			$userObject = $users->results[0];
		}
		$wpUsersCache[$userLogin] = $userObject;
		
		return $wpUsersCache[$userLogin];
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
		$this->startSession();
		
		$sessionKey = self::EVENT_TIME_SESSION_KEY.md5($eventGroup).'_'.md5($eventId);
		if (!array_key_exists($sessionKey, $_SESSION)) {
			$_SESSION[$sessionKey] = time();
			return true;
		}
		$diff = time() - $_SESSION[$sessionKey];
		if ($diff > $this->getEventTimeThreshold($eventGroup)) {
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
		$this->startSession();
		
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
		$this->startSession();
		
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
		$this->startSession();
		
		$_SESSION[self::ABUSES_COUNTER_SESSION_KEY] = 0;
	}
	
	/**
	* Returns users of given channel from the last CURRENT_USERS_TESTING_TIMEFRAME seconds.
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
		$conditions[] = "text = '".WiseChatMessagesDAO::SYSTEM_PING_MESSAGE."'";
		$sql = "SELECT DISTINCT user AS name FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" ORDER BY user ASC ".
				" LIMIT 1000;";
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Returns the amount of users of given channel from the last CURRENT_USERS_TESTING_TIMEFRAME seconds.
	*
	* @param string $channel Channel
	*
	* @return integer
	*/
	public function getAmountOfCurrentUsersOfChannel($channel) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$timeFrame = time() - self::CURRENT_USERS_TESTING_TIMEFRAME;
		$conditions[] = "time > {$timeFrame}";
		$conditions[] = "channel = '{$channel}'";
		$conditions[] = "text = '".WiseChatMessagesDAO::SYSTEM_PING_MESSAGE."'";
		$sql = "SELECT count(DISTINCT user) AS quantity FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions);
				
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}
		
		return 0;
	}
	
	/**
	* Determines whether the given user has any ping messages in the channel.
	*
	* @param string $channel Name of the channel
	* @param string $userName Name of the user
	*
	* @return boolean
	*/
	public function hasUserPingMessages($channel, $userName) {
		global $wpdb;
		
		$userName = addslashes($userName);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$timeFrame = time() - self::CURRENT_USERS_TESTING_TIMEFRAME;
		$conditions[] = "time > {$timeFrame}";
		$conditions[] = "channel = '{$channel}'";
		$conditions[] = "user = '{$userName}'";
		$conditions[] = "text = '".WiseChatMessagesDAO::SYSTEM_PING_MESSAGE."'";
		$sql = "SELECT count(DISTINCT user) AS quantity FROM {$table} WHERE ".implode(" AND ", $conditions);
				
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity > 0;
		}
		
		return false;
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
		$this->startSession();
		
		$_SESSION[self::USER_AUTO_NAME_SESSION_KEY] = $userName;
	}
	
	private function startSession() {
		if (!isset($_SESSION)) {
			session_start();
		}
	}
	
	/**
	* Returns time threshold for given event group.
	*
	* @param string $eventGroup Name of the event group
	*
	* @return integer
	*/
	private function getEventTimeThreshold($eventGroup) {
		if (array_key_exists($eventGroup, $this->eventTimeThresholds)) {
			return $this->eventTimeThresholds[$eventGroup];
		} else {
			return $this->eventTimeThresholds['default'];
		}
	}
}