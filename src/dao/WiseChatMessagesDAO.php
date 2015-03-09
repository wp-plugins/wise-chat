<?php

/**
 * Wise Chat messages DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatMessagesDAO {
	const SYSTEM_PING_MESSAGE = '__user_ping';
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
	}
	
	/**
	* Publishes a message in the given channel of the chat.
	*
	* @param string $user Name of the user (an author of the message)
	* @param string $channel Name of the channel
	* @param string $message Content of the message
	* @param boolean $isAdmin Idicates whether to mark the message as admin-owned
	*
	* @return null
	*/
	public function addMessage($user, $channel, $message, $isAdmin = false) {
		global $wpdb;
		
		$message = trim($message);
		if (strlen($message) === 0) {
			return;
		}
		
		$badWordsFilter = $this->options->isOptionEnabled('filter_bad_words') && $isAdmin === false;
		$filteredMessage = $badWordsFilter ? WiseChatFilter::filter($message) : $message;
		
		// auto-ban feature:
		$autoBanEnabled = $this->options->isOptionEnabled('enable_autoban');
		if ($autoBanEnabled && $filteredMessage != $message) {
			$counter = $this->usersDAO->incrementAndGetAbusesCounter();
			$threshold = $this->options->getIntegerOption('autoban_threshold', 3);
			if ($counter >= $threshold && $threshold > 0) {
				$duration = $this->bansDAO->getDurationFromString('1d');
				$this->bansDAO->createAndSave($this->getRemoteAddress(), $duration);
				$this->usersDAO->clearAbusesCounter();
			}
		}
		
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		$filteredMessage = substr($filteredMessage, 0, $messageMaxLength);
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->insert($table,
			array(
				'time' => time(),
				'admin' => $isAdmin ? 1 : 0,
				'user' => $user,
				'text' => $filteredMessage,
				'channel' => $channel,
				'ip' => $this->getRemoteAddress()
			)
		);
	}
	
	/**
	* Publishes a ping message in the given channel. The messages is
	* used to determine presence of users among chat channels.
	*
	* @param string $user Name of the user (an author of the message)
	* @param string $channel Name of the channel
	*
	* @return null
	*/
	public function addPingMessage($user, $channel) {
		$this->addMessage($user, $channel, self::SYSTEM_PING_MESSAGE, false);
	}
	
	/**
	* Returns messages from the given channel.
	*
	* @param string $channel Name of the channel
	* @param integer $fromId Begin from specific message ID
	*
	* @return array
	*/
	public function getMessages($channel, $fromId = null) {
		global $wpdb;
		
		$limit = $this->options->getIntegerOption('messages_limit', 100);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "channel = '{$channel}'";
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		if ($fromId !== null) {
			$conditions[] = "id > ".intval($fromId);
		}
		if (!$this->usersDAO->isWpUserAdminLogged()) {
			$conditions[] = "admin = 0";
		}
		
		$sql = "SELECT * FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" ORDER BY id DESC ".
				" LIMIT {$limit};";
		
		$messages = $wpdb->get_results($sql);
		
		return array_reverse($messages, true);
	}
	
	/**
	* Returns array of various statistics for each channel.
	*
	* @return array Array of objects (fields: channel, messages, users, last_message)
	*/
	public function getChannelsSummary() {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		$sql = "SELECT channel, count(*) AS messages, max(time) AS last_message FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" GROUP BY channel ".
				" ORDER BY channel ASC ".
				" LIMIT 1000;";
		$mainSummary = $wpdb->get_results($sql);
		
		$usersSummary = $this->getCurrentUsersOfChannels();
		$usersSummaryMap = array();
		foreach ($usersSummary as $userDetails) {
			$usersSummaryMap[$userDetails->channel] = intval($userDetails->users);
		}
		
		foreach ($mainSummary as $mainDetails) {
			$mainDetails->users = array_key_exists($mainDetails->channel, $usersSummaryMap) ? $usersSummaryMap[$mainDetails->channel] : 0;
		}
		
		return $mainSummary;
	}
	
	/**
	* Returns array of channels and amount of users that use each channel. 
	*
	* @return array Array of objects (fields: channel, users)
	*/
	public function getCurrentUsersOfChannels() {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$timeFrame = time() - 60 * 2;
		$conditions[] = "time > {$timeFrame}";
		$sql = "SELECT channel, count(DISTINCT user) as users FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" GROUP BY channel ".
				" ORDER BY channel ASC ".
				" LIMIT 1000;";
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Returns last messages of the given user. Only messages from last 24h are taken into account.
	*
	* @param string $user Name of the user (an author of the message)
	* @param integer $totalMessages Total messages to return
	*
	* @return array
	*/
	public function getLastMessagesByUserName($userName, $totalMessages = 10) {
		global $wpdb;
		
		$timeFrame = time() - 60 * 60 * 24;
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		$conditions[] = "time > {$timeFrame}";
		$conditions[] = "user = '{$userName}'";
		$sql = "SELECT * FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" LIMIT {$totalMessages};";
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Returns last message of the given user.
	*
	* @param string $channel Name of the channel
	* @param string $user Name of the user (an author of the message)
	*
	* @return array
	*/
	public function getLastMessageByUserName($channel, $user) {
		global $wpdb;
		
		$user = addslashes($user);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		$conditions[] = "channel = \"{$channel}\"";
		$conditions[] = "user = \"$user\"";
		
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE ".implode(" AND ", $conditions)." ORDER BY id DESC LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
	
	/**
	* Deletes all messages (in all channels).
	*
	* @return null
	*/
	public function deleteAll() {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE 1 = 1;");
	}
	
	/**
	* Deletes all messages in specified channel.
	*
	* @param string $channel Name of the channel
	*
	* @return null
	*/
	public function deleteByChannel($channel) {
		global $wpdb;
		
		$channel = addslashes($channel);
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE channel = '$channel';");
	}
	
	private function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}