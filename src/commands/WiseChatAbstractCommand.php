<?php

/**
 * Wise Chat abstract command.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatAbstractCommand {
	const SYSTEM_USER_NAME = 'System';

	/**
	* @var string
	*/
	protected $channel;
	
	/**
	* @var string
	*/
	protected $arguments;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	protected $messagesDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	protected $usersDAO;
	
	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;
	
	public function __construct($channel, $arguments) {
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->arguments = $arguments;
		$this->channel = $channel;
	}
	
	protected function addMessage($message) {
		$this->messagesDAO->addMessage(self::SYSTEM_USER_NAME, $this->channel, $message, true);
	}
}