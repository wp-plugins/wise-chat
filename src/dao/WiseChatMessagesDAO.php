<?php

/**
 * Wise Chat messages DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatMessagesDAO {
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var array Wise Chat options
	*/
	private $options;
	
	public function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
		$this->usersDAO = new WiseChatUsersDAO();
	}
	
	/**
	* Adds a new message in the given channel of the chat.
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
		
		$maxLength = intval($this->options['message_max_length']);
		$badWordsFilter = $this->options['filter_bad_words'] == '1' && $isAdmin === false;
		
		$filteredMessage = $badWordsFilter ? WiseChatFilter::filter($message) : $message;
		$filteredMessage = substr($filteredMessage, 0, $maxLength);
		
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
	* Returns messages from given channel.
	*
	* @param string $channel Name of the channel
	* @param integer $fromId Begin from specific message ID
	*
	* @return array
	*/
	public function getMessages($channel, $fromId = null) {
		global $wpdb;
		
		$limit = intval($this->options['messages_limit']);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "channel = '{$channel}'";
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
	* Returns last (24 h) messages of the given user.
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
		$sql = "SELECT * FROM {$table} ".
				" WHERE time > {$timeFrame} AND user = '{$userName}'".
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
		$table = WiseChatInstaller::getMessagesTable();
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE channel = \"{$channel}\" AND user = \"$user\" ORDER BY id DESC LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
	
	private function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}