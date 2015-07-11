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
	* @var WiseChatImagesDownloader
	*/
	private $imagesDownloader;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var string
	*/
	private $messageInjectionText;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->imagesDownloader = new WiseChatImagesDownloader();
		$this->messageInjectionText = null;
	}
	
	/**
	* Publishes a message in the given channel of the chat.
	*
	* @param string $user Name of the user (an author of the message)
	* @param string $channel Name of the channel
	* @param string $message Content of the message
	* @param boolean $isAdmin Idicates whether to mark the message as admin-owned
	*
	* @return object|null
	*/
	public function addMessage($user, $channel, $message, $isAdmin = false) {
		global $wpdb;
		
		$message = trim($message);
		if (strlen($message) === 0 && strlen($this->messageInjectionText) === 0) {
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
		
		// flood control feature:
		if ($this->options->isOptionEnabled('enable_flood_control')) {
			$floodControlThreshold = $this->options->getIntegerOption('flood_control_threshold', 200);
			$floodControlTimeFrame = $this->options->getIntegerOption('flood_control_time_frame', 1);
			if ($floodControlThreshold > 0 && $floodControlTimeFrame > 0) {
				$messagesAmount = $this->getMessagesCountByIpAndTimeThreshold($this->getRemoteAddress(), $floodControlTimeFrame);
				if ($messagesAmount > $floodControlThreshold) {
					$duration = $this->bansDAO->getDurationFromString('1d');
					$this->bansDAO->createAndSave($this->getRemoteAddress(), $duration);
				}
			}
		}
		
		// go through filters:
		$filterChain = new WiseChatFilterChain();
		$filteredMessage = $filterChain->filter($filteredMessage);
		
		// cut the message:
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		$filteredMessage = substr($filteredMessage, 0, $messageMaxLength);
		
		// convert images and links to shortcodes, download images (if enabled): 
		$detectImages = $this->options->isOptionEnabled('allow_post_images');
		$linksPreFilter = new WiseChatLinksPreFilter($this->imagesDownloader);
		$filteredMessage = $linksPreFilter->filter($filteredMessage, $channel, $detectImages);
		
		// join an additional text:
		if ($this->messageInjectionText !== null) {
			$filteredMessage .= ' '.$this->messageInjectionText;
			$this->messageInjectionText = null;
		}
		
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
		
		return $this->getMessageById($wpdb->insert_id);
	}
	
	/**
	* Publishes a message in the given channel of the chat with given attachments.
	*
	* @param string $user Name of the user (an author of the message)
	* @param string $channel Name of the channel
	* @param string $message Content of the message
	* @param array $attachments Array of attachements, only one image is supported
	*
	* @return object|null
	*/
	public function addMessageWithAttachments($user, $channel, $message, $attachments) {
		$this->joinAttachments($channel, $attachments);
		
		return $this->addMessage($user, $channel, $message);
	}
	
	/**
	* Saves attachments in Media Library and attaches them to the message.
	*
	* @param string $channel
	* @param array $attachments Array of attachements
	*
	* @return string The message
	*/
	private function joinAttachments($channel, $attachments) {
		if (!is_array($attachments) || count($attachments) === 0) {
			return;
		}
		
		if ($attachments[0]['type'] === 'image') {
			$imageData = $attachments[0]['data'];
			$imageData = substr($imageData, strpos($imageData, ",") + 1);
			$decodedData = base64_decode($imageData);
			
			$imagesDownloader = new WiseChatImagesDownloader();
			$image = $imagesDownloader->saveImage($decodedData, $channel);
			if (is_array($image)) {
				$this->messageInjectionText = ' '.WiseChatShortcodeConstructor::getImageShortcode($image['id'], $image['image'], $image['image-th'], '_');
			}
		}
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
		
		$order = $this->options->getEncodedOption('messages_order', '');
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
		
		return $order == 'descending' ? $messages : array_reverse($messages, true);
	}
	
	/**
	* Returns all messages older than given amount of minutes.
	*
	* @param integer $minutes
	*
	* @return array
	*/
	public function getMessagesByTimeThreshold($minutes) {
		global $wpdb;
		
		$limit = 1000;
		$threshold = time() - $minutes * 60;
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		$conditions[] = "time < {$threshold}";
		
		$sql = "SELECT * FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" LIMIT {$limit};";
		$messages = $wpdb->get_results($sql);
		
		return $messages;
	}
	
	/**
	* Returns amount of messages for given IP and time threshold in minutes.
	*
	* @param string $ipAddress
	* @param integer $timeThresholdInMinutes
	*
	* @return integer
	*/
	public function getMessagesCountByIpAndTimeThreshold($ipAddress, $timeThresholdInMinutes) {
		global $wpdb;
		
		$threshold = time() - $timeThresholdInMinutes * 60;
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "text != '".self::SYSTEM_PING_MESSAGE."'";
		$conditions[] = "ip = '".$ipAddress."'";
		$conditions[] = "time >= {$threshold}";
		
		$sql = "SELECT count(*) AS quantity FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions);
				
		$results = $wpdb->get_results($sql);
		
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}
		
		return 0;
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
		$conditions[] = "user != 'System'";
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
	* self::SYSTEM_PING_MESSAGE messages are also used to determine users presence.
	*
	* @return array Array of objects (fields: channel, users)
	*/
	public function getCurrentUsersOfChannels() {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$timeFrame = time() - 60 * 2;
		$conditions[] = "time > {$timeFrame}";
		$conditions[] = "user != 'System'";
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
	* Returns message by given ID.
	*
	* @param string $id ID of the message
	*
	* @return objec|null
	*/
	public function getMessageById($id) {
		global $wpdb;
		
		$id = intval($id);
		$table = WiseChatInstaller::getMessagesTable();
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE id = {$id} LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
	
	/**
	* Deletes all messages (in all channels). 
	* Related images (WordPress Media Library objects) are also deleted.
	*
	* @return null
	*/
	public function deleteAll() {
		global $wpdb;
		
		$attachements = $this->imagesDownloader->getAllImagesDownloaded();
		foreach ($attachements as $attachement) {
			wp_delete_attachment(intval($attachement->ID), true);
		}
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE 1 = 1;");
	}
	
	/**
	* Deletes all messages from specified channel.
	* Related images (WordPress Media Library objects) are also deleted.
	*
	* @param string $channel Name of the channel
	*
	* @return null
	*/
	public function deleteByChannel($channel) {
		global $wpdb;
		
		$attachements = $this->imagesDownloader->getAllImagesDownloadedForChannel($channel);
		foreach ($attachements as $attachement) {
			wp_delete_attachment(intval($attachement->ID), true);
		}
		
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE channel = '{$channel}';");
	}
	
	/**
	* Deletes a message by ID.
	* Related images (WordPress Media Library objects) are also deleted.
	*
	* @param integer $id
	*
	* @return null
	*/
	public function deleteById($id) {
		global $wpdb;
		
		$id = intval($id);
		$message = $this->getMessageById($id);
		if ($message !== null) {
			$attachementIds = WiseChatImagesPostFilter::getImageIds(htmlspecialchars($message->text, ENT_QUOTES, 'UTF-8'));
			foreach ($attachementIds as $attachementId) {
				wp_delete_attachment(intval($attachementId), true);
			}
		
			$table = WiseChatInstaller::getMessagesTable();
			$wpdb->get_results("DELETE FROM {$table} WHERE id = '$id';");
		}
	}
	
	/**
	* Deletes all messages older than given amount of minutes.
	*
	* @param integer $minutes
	*
	* @return null
	*/
	public function deleteByTimeThreshold($minutes) {
		global $wpdb;
		
		$threshold = time() - $minutes * 60;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE time < {$threshold};");
	}
	
	/**
	* Deletes all ping messages of given user.
	*
	* @param string $userName
	*
	* @return null
	*/
	public function deletePingMessagesByUserName($userName) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results(sprintf("DELETE FROM %s WHERE text = '%s' AND user = '%s';", $table, self::SYSTEM_PING_MESSAGE, $userName));
	}
	
	private function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}