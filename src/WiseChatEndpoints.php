<?php

require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatBansDAO.php');

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
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var array
	*/
	private $options;
	
	public function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->renderer = new WiseChatRenderer();
	}
	
	/**
	* Common endpoint for adjusting settings.
	*
	* @return null
	*/
	public function settingsEndpoint() {
		$_POST = stripslashes_deep($_POST);
    
		$response = array();
		$property = $this->getPostParam('property');
		if ($property == 'userName') {
			$response = $this->changeUserName($this->getPostParam('value'));
		}
		
		echo json_encode($response);
		die();
	}
	
	/**
	* Returns messages to render in chat window.
	*
	* @return null
	*/
	public function messagesEndpoint() {
		$lastId = intval($this->getGetParam('lastId', 0));
		$channel = $this->getGetParam('channel');
		
		$response = array();
		$response['result'] = array();
		if (strlen($channel) > 0) {
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
		}
    
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
		$_POST = stripslashes_deep($_POST);
    
		$response = array();
		$channel = trim($this->getPostParam('channel'));
		$message = trim($this->getPostParam('message'));
		
		$ban = $this->bansDAO->getBanByIp($_SERVER['REMOTE_ADDR']);
		if ($ban != null) {
			$response['error'] = 'You were banned from posting messages';
		} else {
			if (strlen($message) > 0 && strlen($channel) > 0) {
				$wiseChatCommandsResolver = new WiseChatCommandsResolver();
				$isCommandResolved = $wiseChatCommandsResolver->resolve($this->usersDAO->getUserName(), $channel, $message);
				if (!$isCommandResolved) {
					$this->messagesDAO->addMessage($this->usersDAO->getUserName(), $channel, $message, false);
				}
				$response['result'] = 'OK';
			} else {
				$response['error'] = 'Missing required fields';
			}
		}
		
		echo json_encode($response);
		die();
	}
	
	private function changeUserName($userName) {
		$response = array();
		$allowChangeUserName = isset($this->options['allow_change_user_name']) && $this->options['allow_change_user_name'] == '1';
		if (!$allowChangeUserName) {
			$response['error'] = 'Unsupported operation';
			return $response;
		}
		
		$userName = trim($userName);
		if (strlen($userName) == 0) {
			$response['error'] = 'User name cannot be empty';
			return $response;
		}
		
		if (!preg_match('/^[a-zA-Z0-9\-_ ]+$/', $userName)) {
			$response['error'] = 'Only letters, number, spaces, hyphens and underscores are allowed';
			return $response;
		}
		
		$wpUser = $this->usersDAO->getWpUserByDisplayName($userName);
		if ($wpUser !== null) {
			$response['error'] = 'This name is already occupied';
			return $response;
		}
		
		$oldUserName = $this->usersDAO->getUserName();
		$this->usersDAO->setUserName($userName);
		$newUserName = $this->usersDAO->getUserName();
		
		$messages = $this->messagesDAO->getLastMessagesByUserName($newUserName);
		if (count($messages) > 0) {
			$this->usersDAO->setUserName($oldUserName);
			$response['error'] = 'This name is already occupied';
		} else {
			$response['value'] = $newUserName;
			$response['result'] = 'OK';
		}
		
		return $response;
	}
	
	private function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}
	
	private function getGetParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}
}