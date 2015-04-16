<?php

require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatBansDAO.php');
require_once(dirname(__FILE__).'/messages/WiseChatImagesDownloader.php');
require_once(dirname(__FILE__).'/services/WiseChatUserService.php');

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
	}
	
	/**
	* Returns messages to render in chat window.
	*
	* @return null
	*/
	public function messagesEndpoint() {
		if ($this->isChatDisabledForAnonymous()) {
			die('{}');
		}
		
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
			
			// additional data:
			if ($this->options->isOptionEnabled('show_users') && $this->usersDAO->shouldTriggerEvent('usersList', $channel)) {
				$users = $this->usersDAO->getCurrentUsersOfChannel($channel);
				foreach ($users as $user) {
					$name = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
					if ($this->usersDAO->getUserName() == $user->name) {
						$name = sprintf('<span class="wcCurrentUser">%s</span>', $name);
					}
					
					$user->name = $name;
				}
				$response['actions'] = array();
				$response['actions']['refreshUsersList'] = array(
					'data' => $users
				);
			}
		}
    
		// maintenance:
		$this->bansDAO->deleteOldBans();
    
		echo json_encode($response);
		die();
	}
	
	/**
	* New message endpoint.
	*
	* @return null
	*/
	public function messageEndpoint() {
		if ($this->isChatDisabledForAnonymous()) {
			die('{}');
		}
    
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
		if ($this->isChatDisabledForAnonymous() || !$this->usersDAO->isWpUserAdminLogged()) {
			die('{}');
		}
    
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
		if ($this->isChatDisabledForAnonymous()) {
			die('[]');
		}
		$lastId = intval($this->getGetParam('lastId', 0));
		
		echo json_encode($this->actionsDAO->getJsonReadyActions($lastId));
		die();
	}
	
	/**
	* Endpoint for settings adjustments.
	*
	* @return null
	*/
	public function settingsEndpoint() {
		if ($this->isChatDisabledForAnonymous()) {
			die('{}');
		}
    
		$response = array();
		try {
			switch ($this->getPostParam('property')) {
				case 'userName':
					$userService = new WiseChatUserService();
					$response['value'] = $userService->changeUserName($this->getPostParam('value'));
					break;
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
	
	private function isChatDisabledForAnonymous() {
		return $this->options->isOptionEnabled('restrict_to_wp_users') && !$this->usersDAO->isWpUserLogged();
	}
}