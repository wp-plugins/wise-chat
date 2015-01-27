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
	
	/**
	* @var array Wise Chat options
	*/
	private $options;
	
	public function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
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
	* Sets name for the current user of the chat.
	*
	* @return null
	*/
	public function setUserName($userName) {
		$this->startSession();
		
		$badWordsFilter = $this->options['filter_bad_words'] == '1';
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
	* Retuns WP user by display_name field.
	*
	* @param string $displayName
	*
	* @return WP_User|null
	*/
	public function getWpUserByDisplayName($displayName) {
		$args = array(
			'search' => $displayName,
			'search_fields' => array('display_name')
		);
		$users = new WP_User_Query($args);
		if (count($users->results) > 0) {
			return $users->results[0];
		}
		
		return null;
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
			
			$this->setUserName($this->options['user_name_prefix'].get_option(self::LAST_NAME_ID_OPTION));
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