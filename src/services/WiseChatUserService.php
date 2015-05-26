<?php

/**
 * Wise Chat services regarding users.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatUserService {

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->messagesDAO = new WiseChatMessagesDAO();
	}
	
	/**
	* Sets a new name for current user.
	*
	* @param string $userName A new name to set
	*
	* @return string New name
	* @throws Exception If an error occurre
	*/
	public function changeUserName($userName) {
		if (!$this->options->isOptionEnabled('allow_change_user_name')) {
			throw new Exception('Unsupported operation');
		}
		
		$userName = trim($userName);
		if (strlen($userName) == 0) {
			throw new Exception('User name cannot be empty');
		}
		
		if (!preg_match('/^[a-zA-Z0-9\-_ ]+$/', $userName)) {
			throw new Exception($this->options->getOption('message_error_1', 'Only letters, number, spaces, hyphens and underscores are allowed'));
		}
		
		$wpUser = $this->usersDAO->getWpUserByDisplayName($userName);
		if ($wpUser !== null) {
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		}
		
		$oldUserName = $this->usersDAO->getUserName();
		$this->usersDAO->setUserName($userName);
		$newUserName = $this->usersDAO->getUserName();
		
		$messages = $this->messagesDAO->getLastMessagesByUserName($newUserName);
		if (count($messages) > 0) {
			$this->usersDAO->setUserName($oldUserName);
			throw new Exception($this->options->getOption('message_error_2', 'This name is already occupied'));
		} else {
			return $newUserName;
		}
	}	
}