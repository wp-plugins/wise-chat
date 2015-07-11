<?php

define('WC_ROOT', dirname(__FILE__).'/..');

require_once(WC_ROOT.'/WiseChatOptions.php');
require_once(WC_ROOT.'/WiseChatSettings.php');
require_once(WC_ROOT.'/WiseChatInstaller.php');
require_once(WC_ROOT.'/WiseChatThemes.php');

require_once(WC_ROOT.'/dao/WiseChatActionsDAO.php');
require_once(WC_ROOT.'/dao/WiseChatMessagesDAO.php');
require_once(WC_ROOT.'/dao/WiseChatUsersDAO.php');
require_once(WC_ROOT.'/dao/WiseChatBansDAO.php');
require_once(WC_ROOT.'/dao/WiseChatFiltersDAO.php');
require_once(WC_ROOT.'/dao/filters/WiseChatFilterChain.php');
require_once(WC_ROOT.'/messages/WiseChatImagesDownloader.php');
require_once(WC_ROOT.'/services/WiseChatService.php');
require_once(WC_ROOT.'/services/WiseChatUserService.php');
require_once(WC_ROOT.'/services/WiseChatBansService.php');
require_once(WC_ROOT.'/services/WiseChatMessagesService.php');
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
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
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
		$this->usersDAO = new WiseChatUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->renderer = new WiseChatRenderer();
		$this->bansService = new WiseChatBansService();
		$this->messagesService = new WiseChatMessagesService();
		$this->userService = new WiseChatUserService();
		$this->service = new WiseChatService();
		
		$this->userService->initializeCookie();
	}
	
	/**
	* Returns messages to render in chat window.
	*
	* @return null
	*/
	public function messagesEndpoint() {
		$this->accessCheck();
		
		$lastId = intval($this->getGetParam('lastId', 0));
		$channel = $this->getGetParam('channel');
		
		$response = array();
		$response['result'] = array();
		if (strlen($channel) > 0) {
			// add ping message to the channel:
			if ($this->usersDAO->shouldTriggerEvent('ping', $channel)) {
				$this->messagesDAO->addPingMessage($this->usersDAO->getUserName(), $channel);
			}
		
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
			
			$response['actions'] = array();
			if ($this->usersDAO->shouldTriggerEvent('usersList', $channel)) {
				// users list:
				if ($this->options->isOptionEnabled('show_users')) {
					$users = $this->usersDAO->getCurrentUsersOfChannel($channel);
					foreach ($users as $user) {
						$name = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
						if ($this->usersDAO->getUserName() == $user->name) {
							$name = sprintf('<span class="wcCurrentUser">%s</span>', $name);
						}
						
						$user->name = $name;
					}
					$response['actions']['refreshUsersList'] = array(
						'data' => $users
					);
				}
				
				// users counter:
				if ($this->options->isOptionEnabled('show_users_counter')) {
					$response['actions']['refreshUsersCounter'] = array(
						'data' => array(
							'total' => $this->usersDAO->getAmountOfCurrentUsersOfChannel($channel)
						)
					);
				}
			}
		}
    
		// maintenance:
		$this->messagesService->periodicMaintenance();
		$this->bansService->periodicMaintenance();
    
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
    
		$response = array();
		$channel = trim($this->getPostParam('channel'));
		$message = trim($this->getPostParam('message'));
		$attachments = $this->getPostParam('attachments');
		
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
    
		$response = array();
		$channel = trim($this->getPostParam('channel'));
		$messageId = trim($this->getPostParam('messageId'));
		
		if (strlen($messageId) > 0 && strlen($channel) > 0) {
			$this->messagesDAO->deleteById($messageId);
			$this->actionsDAO->publishAction('deleteMessage', array('id' => $messageId, 'channel' => $channel));
			$response['result'] = 'OK';
		} else {
			$response['error'] = 'Missing required fields';
		}
		
		echo json_encode($response);
		die();
	}
	
	/**
	* Endpoint for actions delivery.
	*
	* @return null
	*/
	public function actionsEndpoint() {
		$this->accessCheck();
		$lastId = intval($this->getGetParam('lastId', 0));
		
		echo json_encode($this->actionsDAO->getJsonReadyActions($lastId));
		die();
	}
	
	/**
	* Endpoint for user's settings adjustments.
	*
	* @return null
	*/
	public function settingsEndpoint() {
		$this->accessCheck();
    
		$response = array();
		try {
			$property = $this->getPostParam('property');
			$value = $this->getPostParam('value');
			$channel = $this->getPostParam('channel');
			switch ($property) {
				case 'userName':
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
	
	private function accessCheck() {
		if ($this->options->isOptionEnabled('restrict_to_wp_users') && !$this->usersDAO->isWpUserLogged()) {
			die('{ "error": "Access denied"}');
		}
		
		if (!$this->service->isChatOpen()) {
			die(sprintf('{ "error": "%s" }', $this->options->getEncodedOption('message_error_5', 'The chat is closed now')));
		}
	}
	
	private function adminAccessCheck() {
		if ($this->options->isOptionEnabled('restrict_to_wp_users') && !$this->usersDAO->isWpUserLogged() || !$this->usersDAO->isWpUserAdminLogged()) {
			die('{ "error": "Access denied"}');
		}
	}
}