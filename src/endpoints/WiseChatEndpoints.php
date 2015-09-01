<?php

define('WC_ROOT', dirname(__FILE__).'/..');

require_once(WC_ROOT.'/WiseChatOptions.php');
require_once(WC_ROOT.'/WiseChatSettings.php');
require_once(WC_ROOT.'/WiseChatInstaller.php');
require_once(WC_ROOT.'/WiseChatThemes.php');
require_once(WC_ROOT.'/WiseChatCrypt.php');

require_once(WC_ROOT.'/dao/WiseChatActionsDAO.php');
require_once(WC_ROOT.'/dao/WiseChatMessagesDAO.php');
require_once(WC_ROOT.'/dao/WiseChatUsersDAO.php');
require_once(WC_ROOT.'/dao/WiseChatChannelsDAO.php');
require_once(WC_ROOT.'/dao/WiseChatChannelUsersDAO.php');
require_once(WC_ROOT.'/dao/WiseChatBansDAO.php');
require_once(WC_ROOT.'/dao/WiseChatFiltersDAO.php');
require_once(WC_ROOT.'/dao/filters/WiseChatFilterChain.php');
require_once(WC_ROOT.'/messages/WiseChatImagesDownloader.php');
require_once(WC_ROOT.'/services/WiseChatService.php');
require_once(WC_ROOT.'/services/WiseChatUserService.php');
require_once(WC_ROOT.'/services/WiseChatBansService.php');
require_once(WC_ROOT.'/services/WiseChatMessagesService.php');
require_once(WC_ROOT.'/services/WiseChatAttachmentsService.php');
require_once(WC_ROOT.'/commands/WiseChatCommandsResolver.php');
require_once(WC_ROOT.'/rendering/WiseChatRenderer.php');
require_once(WC_ROOT.'/rendering/filters/WiseChatLinksPreFilter.php');
require_once(WC_ROOT.'/rendering/filters/WiseChatShortcodeConstructor.php');
require_once(WC_ROOT.'/rendering/WiseChatTemplater.php');

/**
 * Wise Chat endpoints class
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatEndpoints {
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
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
	private $bansDAO;
	
	/**
	* @var WiseChatActionsDAO
	*/
	private $actionsDAO;
	
	/**
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var WiseChatBansService
	*/
	private $bansService;
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;
	
	/**
	* @var WiseChatService
	*/
	private $service;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	private $arePostSlashesStripped = false;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->channelsDAO = new WiseChatChannelsDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->renderer = new WiseChatRenderer();
		$this->bansService = new WiseChatBansService();
		$this->messagesService = new WiseChatMessagesService();
		$this->userService = new WiseChatUserService();
		$this->service = new WiseChatService();
	}
	
	/**
	* Returns messages to render in chat window.
	*
	* @return null
	*/
	public function messagesEndpoint() {
		$this->accessCheck();
		$this->verifyCheckSum();
		
		$this->verifyGetParams(array('channel', 'lastId'));
		$lastId = intval($this->getGetParam('lastId', 0));
		$channel = $this->getGetParam('channel');

		$this->authorizationCheck($channel);
		
		$response = array();
		$response['nowTime'] = gmdate('c', time());
		$response['result'] = array();
	
		// get and render messages:
		$messages = $this->messagesDAO->getMessages($channel, $lastId > 0 ? $lastId : null);
		foreach ($messages as $message) {
			// ommit non-admin messages:
			if ($message->admin == 1 && !$this->usersDAO->isWpUserAdminLogged()) {
				continue;
			}
			
			$messageToJson = array();
			$messageToJson['text'] = $this->renderer->getRenderedMessage($message);
			$messageToJson['id'] = $message->id;
			
			$response['result'][] = $messageToJson;
		}
    
		echo json_encode($response);
		die();
	}
	
	/**
	* New message endpoint.
	*
	* @return null
	*/
	public function messageEndpoint() {
		$this->accessCheck();
		$this->verifyCheckSum();
    
		$channel = trim($this->getPostParam('channel'));
		$message = trim($this->getPostParam('message'));
		$attachments = $this->getPostParam('attachments');
		
		$this->authorizationCheck($channel);
		
		$response = array();
		if ($this->bansDAO->isIpBanned($_SERVER['REMOTE_ADDR'])) {
			$response['error'] = $this->options->getOption('message_error_3', 'You were banned from posting messages');
		} else {
			if ((strlen($message) > 0 || count($attachments) > 0) && strlen($channel) > 0) {
				$wiseChatCommandsResolver = new WiseChatCommandsResolver();
				$isCommandResolved = $wiseChatCommandsResolver->resolve($this->usersDAO->getUserName(), $channel, $message);
				if (!$isCommandResolved) {
					if ($this->options->isOptionEnabled('enable_images_uploader')) {
						$this->messagesDAO->addMessageWithAttachments($this->usersDAO->getUserName(), $channel, $message, $attachments);
					} else {
						$this->messagesDAO->addMessage($this->usersDAO->getUserName(), $channel, $message);
					}
				}
				$response['result'] = 'OK';
			} else {
				$response['error'] = 'Missing required fields';
			}
		}
		
		echo json_encode($response);
		die();
	}
	
	/**
	* Endpoint for message deletion.
	*
	* @return null
	*/
	public function messageDeleteEndpoint() {
		$this->adminAccessCheck();
		$this->verifyCheckSum();
		$this->verifyPostParams(array('channel', 'messageId'));

		$channel = trim($this->getPostParam('channel'));
		$messageId = trim($this->getPostParam('messageId'));
		
		$this->authorizationCheck($channel);
		
		$this->messagesDAO->deleteById($messageId);
		$this->actionsDAO->publishAction('deleteMessage', array('id' => $messageId, 'channel' => $channel));
		
		echo json_encode(array('result' => 'OK'));
		die();
	}
	
	/**
	* Endpoint for periodic (every 10-20 seconds) maintenance services like:
	* - getting the list of actions to execute on the client side
	* - getting the list of events to listen on the client side
	* - maintenance actions in messages, bans, users, etc.
	*
	* @return null
	*/
	public function maintenanceEndpoint() {
		$this->accessCheck();
		$this->verifyCheckSum();
		$this->verifyGetParams(array('channel', 'lastActionId'));
		
		$channel = $this->getGetParam('channel');
		$lastActionId = intval($this->getGetParam('lastActionId', 0));
		
		$this->authorizationCheck($channel);
		
		$response = array();
		
		// create channel if does not exist:
		if ($this->channelsDAO->getByName($channel) === null) {
			$this->channelsDAO->create($channel);
		}
		
		// periodic maintenance:
		$this->messagesService->periodicMaintenance($channel);
		$this->bansService->periodicMaintenance();
		$this->userService->periodicMaintenance($channel);
		
		//actions:
		$response['actions'] = $this->actionsDAO->getJsonReadyActions($lastActionId);
		
		// events:
		$response['events'] = array();
		if ($this->usersDAO->shouldTriggerEvent('usersList', $channel)) {
			if ($this->options->isOptionEnabled('show_users')) {
				$response['events'][] = array(
					'name' => 'refreshUsersList',
					'data' => $this->renderer->getRenderedUsersList($channel)
				);
			}
			
			if ($this->options->isOptionEnabled('show_users_counter')) {
				$response['events'][] = array(
					'name' => 'refreshUsersCounter',
					'data' => array(
						'total' => $this->channelUsersDAO->getAmountOfUsersInChannel($channel)
					)
				);
			}
		}
		
		echo json_encode($response);
		die();
	}
	
	/**
	* Endpoint for user's settings adjustments.
	*
	* @return null
	*/
	public function settingsEndpoint() {
		$this->accessCheck();
		$this->verifyCheckSum();
		$this->verifyPostParams(array('property', 'value'));
    
		$response = array();
		try {
			$property = $this->getPostParam('property');
			$value = $this->getPostParam('value');
			$channel = $this->getPostParam('channel');
			
			switch ($property) {
				case 'userName':
					$this->authorizationCheck($channel);
					$response['value'] = $this->userService->changeUserName($value, $channel);
					break;
				default:
					$this->userService->setUserPropertySetting($property, $value);
			}
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
		}	
		
		echo json_encode($response);
		die();
	}
	
	private function getPostParam($name, $default = null) {
		if (!$this->arePostSlashesStripped) {
			$_POST = stripslashes_deep($_POST);
			$this->arePostSlashesStripped = true;
		}
	
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}
	
	private function getGetParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}
	
	private function getParam($name, $default = null) {
		$getParam = $this->getGetParam($name);
		if ($getParam === null) {
			return $this->getPostParam($name, $default);
		}
		
		return $getParam;
	}
	
	private function verifyGetParams($params) {
		foreach ($params as $param) {
			if (strlen(trim($this->getGetParam($param))) === 0) {
				die('{ "error": "Required parameters are missing" }');
			}
		}
	}
	
	private function verifyPostParams($params) {
		foreach ($params as $param) {
			if (strlen(trim($this->getPostParam($param))) === 0) {
				die('{ "error": "Required parameters are missing" }');
			}
		}
	}
	
	private function accessCheck() {
		if ($this->options->getOption('access_mode') == 1 && !$this->usersDAO->isWpUserLogged()) {
			die('{ "error": "Access denied"}');
		}
		
		if (!$this->service->isChatOpen()) {
			die(sprintf('{ "error": "%s" }', $this->options->getEncodedOption('message_error_5', 'The chat is closed now')));
		}
	}
	
	private function adminAccessCheck() {
		if ($this->options->getOption('access_mode') == 1 && !$this->usersDAO->isWpUserLogged() || !$this->usersDAO->isWpUserAdminLogged()) {
			die('{ "error": "Access denied"}');
		}
	}
	
	private function authorizationCheck($channelName) {
		$channel = $this->channelsDAO->getByName($channelName);
		if ($channel !== null && strlen($channel->password) > 0 && !$this->usersDAO->isUserAuthorizedForChannel($channelName)) {
			die('{ "error": "Not authorized" }');
		}
	}
	
	private function verifyCheckSum() {
		$checksum = $this->getParam('checksum');
		
		if ($checksum !== null) {
			$decoded = unserialize(WiseChatCrypt::decrypt(base64_decode($checksum)));
			if (is_array($decoded)) {
				$this->options->replaceOptions($decoded);
			}
		}
	}
}