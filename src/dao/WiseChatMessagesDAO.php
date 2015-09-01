<?php

/**
 * Wise Chat messages DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatMessagesDAO {

	/**
	* @var WiseChatChannelsDAO
	*/
	private $channelsDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;
	
	/**
	* @var WiseChatImagesDownloader
	*/
	private $imagesDownloader;
	
	/**
	* @var WiseChatAttachmentsService
	*/
	private $attachmentsService;
	
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
		$this->channelsDAO = new WiseChatChannelsDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->imagesDownloader = new WiseChatImagesDownloader();
		$this->attachmentsService = new WiseChatAttachmentsService();
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
		
		$channelUser = $this->channelUsersDAO->getByUserAndChannel($user, $channel);
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->insert($table,
			array(
				'time' => time(),
				'admin' => $isAdmin ? 1 : 0,
				'user' => $user,
				'channel_user_id' => $channelUser !== null ? $channelUser->id : null,
				'text' => $filteredMessage,
				'channel' => $channel,
				'ip' => $this->getRemoteAddress()
			)
		);
		
		$messageId = $wpdb->insert_id;
		
		// mark attachments from links pre filter:
		$createdAttachments = $linksPreFilter->getCreatedAttachments();
		if (count($createdAttachments) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($createdAttachments, $channel, $messageId);
		}
		
		return $this->getMessageById($messageId);
	}
	
	/**
	* Publishes a message with given attachments in the given channel.
	*
	* @param string $user Name of the user (an author of the message)
	* @param string $channel Name of the channel
	* @param string $message Content of the message
	* @param array $attachments Array of attachments, only one image is supported
	*
	* @return object|null Added message
	*/
	public function addMessageWithAttachments($user, $channel, $message, $attachments) {
		$attachmentIds = $this->joinAttachments($channel, $attachments);
		$addedMessage = $this->addMessage($user, $channel, $message);
		$this->attachmentsService->markAttachmentsWithDetails($attachmentIds, $channel, $addedMessage->id);
		
		return $addedMessage;
	}
	
	/**
	* Saves attachments in Media Library and attaches them to the message.
	*
	* @param string $channel
	* @param array $attachments Array of attachments
	*
	* @return array IDs of created attachments
	*/
	private function joinAttachments($channel, $attachments) {
		$attachmentIds = array();
		if (!is_array($attachments) || count($attachments) === 0) {
			return $attachmentIds;
		}
		
		$firstAttachment = $attachments[0];
		$data = $firstAttachment['data'];
		$data = substr($data, strpos($data, ",") + 1);
		$decodedData = base64_decode($data);
		
		if ($firstAttachment['type'] === 'image') {
			$imagesDownloader = new WiseChatImagesDownloader();
			$image = $imagesDownloader->saveImage($decodedData, $channel);
			if (is_array($image)) {
				$this->messageInjectionText = ' '.WiseChatShortcodeConstructor::getImageShortcode($image['id'], $image['image'], $image['image-th'], '_');
				$attachmentIds[] = $image['id'];
			}
		}
		
		if ($firstAttachment['type'] === 'file') {
			$fileName = $firstAttachment['name'];
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel);
			if (is_array($file)) {
				$this->messageInjectionText = ' '.WiseChatShortcodeConstructor::getAttachmentShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds[] = $file['id'];
			}
		}
		
		return $attachmentIds;
	}
	
	/**
	* Updates users in all messages belonging to the given channel user IDs.
	*
	* @param string $user
	* @param array $channelUsersIds
	*
	* @return null
	*/
	public function updateUserByChanellUsersIds($user, $channelUsersIds) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
				
		if (is_array($channelUsersIds) && count($channelUsersIds) > 0) {
			$sql = sprintf('UPDATE %s SET user = "%s" WHERE channel_user_id IN (%s); ', $table, $user, implode(", ", $channelUsersIds));
			$wpdb->get_results($sql);
		}
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
	* Returns messages belonging to the given channel user IDs.
	*
	* @param array $channelUsersIds
	*
	* @return array
	*/
	public function getMessagesByChanellUsersIds($channelUsersIds) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		if (is_array($channelUsersIds) && count($channelUsersIds) > 0) {
			$sql = sprintf('SELECT * FROM %s WHERE channel_user_id IN (%s); ', $table, implode(", ", $channelUsersIds));
			
			return $wpdb->get_results($sql);
		}
		
		return array();
	}
	
	/**
	* Returns all messages older than given amount of minutes.
	*
	* @param integer $minutes
	* @param string $channel Given channel
	*
	* @return array
	*/
	public function getMessagesByTimeThresholdAndChannel($minutes, $channel) {
		global $wpdb;
		
		$limit = 1000;
		$threshold = time() - $minutes * 60;
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "time < {$threshold}";
		$conditions[] = "channel = '{$channel}'";
		
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
		$conditions[] = "ip = '".$ipAddress."'";
		$conditions[] = "time >= {$threshold}";
		
		$sql = "SELECT count(*) AS quantity FROM {$table} WHERE ".implode(" AND ", $conditions);
		$results = $wpdb->get_results($sql);
		
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}
		
		return 0;
	}
	
	/**
	* Returns amount of messages for given channel.
	*
	* @param string $channel
	*
	* @return integer
	*/
	public function getAmountByChannel($channel) {
		global $wpdb;
		
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "channel = '".$channel."'";
		$conditions[] = "admin = 0";
		
		$sql = "SELECT count(*) AS quantity FROM {$table} WHERE ".implode(" AND ", $conditions);
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
		$conditions[] = "user != 'System'";
		$sql = "SELECT channel, count(*) AS messages, max(time) AS last_message FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" GROUP BY channel ".
				" ORDER BY channel ASC ".
				" LIMIT 1000;";
		$mainSummary = $wpdb->get_results($sql);
		
		$usersSummary = $this->channelUsersDAO->getUsersOfChannels();
		$usersSummaryMap = array();
		foreach ($usersSummary as $userDetails) {
			$usersSummaryMap[$userDetails->channel] = intval($userDetails->users);
		}
		
		$mainSummaryMap = array();
		foreach ($mainSummary as $mainDetails) {
			$mainDetails->users = array_key_exists($mainDetails->channel, $usersSummaryMap) ? $usersSummaryMap[$mainDetails->channel] : 0;
			$mainSummaryMap[$mainDetails->channel] = $mainDetails;
		}
		
		$channels = $this->channelsDAO->getAll();
		$fullSummary = array();
		foreach ($channels as $channel) {
			if (array_key_exists($channel->name, $mainSummaryMap)) {
				$channelPrepared = $mainSummaryMap[$channel->name];
				$channelPrepared->secured = strlen($channel->password) > 0;
				$fullSummary[] = $channelPrepared;
			} else {
				$fullSummary[] = (object) array(
					'channel' => $channel->name,
					'messages' => 0,
					'users' => array_key_exists($channel->name, $usersSummaryMap) ? $usersSummaryMap[$channel->name] : 0,
					'last_message' => null,
					'secured' => strlen($channel->password) > 0
				);
			}
		}
		
		return $fullSummary;
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
	* Deletes all messages (in all channels). Related attachments are also deleted.
	*
	* @return null
	*/
	public function deleteAll() {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE 1 = 1;");
		
		$this->attachmentsService->deleteAllAttachments();
	}
	
	/**
	* Deletes all messages from specified channel. Related attachments are also deleted.
	*
	* @param string $channel Name of the channel
	*
	* @return null
	*/
	public function deleteByChannel($channel) {
		global $wpdb;
		
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->get_results(sprintf("DELETE FROM %s WHERE channel = '%s';", $table, addslashes($channel)));
		
		$this->attachmentsService->deleteAttachmentsByChannel($channel);
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
			$this->attachmentsService->deleteAttachmentsByMessageIds(array($id));
		
			$table = WiseChatInstaller::getMessagesTable();
			$wpdb->get_results(sprintf("DELETE FROM %s WHERE id = '%s';", $table, $id));
		}
	}
	
	/**
	* Deletes all messages older than given amount of minutes.
	*
	* @param integer $minutes
	* @param string $channel Name of the channel
	*
	* @return array IDs of deleted messages
	*/
	public function deleteByTimeThresholdAndChannel($minutes, $channel) {
		global $wpdb;
		$table = WiseChatInstaller::getMessagesTable();
		$messagesIds = array();
		
		$threshold = time() - $minutes * 60;
		
		$conditions = array();
		$conditions[] = "time < {$threshold}";
		$conditions[] = "channel = '{$channel}'";
		$deletionCandidates = $wpdb->get_results("SELECT * FROM {$table} WHERE ".implode(" AND ", $conditions));
		
		if (is_array($deletionCandidates) && count($deletionCandidates) > 0) {
			foreach ($deletionCandidates as $message) {
				$messagesIds[] = $message->id;
			}
			$this->attachmentsService->deleteAttachmentsByMessageIds($messagesIds);
			
			$wpdb->get_results("DELETE FROM {$table} WHERE ".implode(" AND ", $conditions));
		}
		
		return $messagesIds;
	}
	
	private function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}