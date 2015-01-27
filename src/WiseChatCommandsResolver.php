<?php

/**
 * Wise Chat commands resolver.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatCommandsResolver {
	const SYSTEM_USER_NAME = 'System';
	
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	public function __construct() {
		$this->messagesDAO = new WiseChatMessagesDAO();
	}

	/**
	* Checks whether given message is an admin command and executes it if so.
	*
	* @param string $user Name of the user (author of the message)
	* @param string $channel Name of the channel
	* @param string $message Content of the possible command
	* @param boolean $isAdmin Idicates whether mark the message as admin-owned
	*
	* @return boolean True if the message is processed and is not needed to be displayed
	*/
	public function resolve($user, $channel, $message) {
		if ($this->isPossiblyCommand($message) && $this->isAdminLoggedIn()) {
			// print typed command as admin message:
			$this->messagesDAO->addMessage($user, $channel, $message, true);
		
			// execute command:
			$resolver = $this->getCommandResolver($channel, $message);
			if ($resolver !== null) {
				$resolver->execute();
			} else {
				$this->messagesDAO->addMessage(self::SYSTEM_USER_NAME, $channel, 'Command not found', true);
			}
		
			return true;
		}
		
		return false;
	}
	
	/**
	* Checks given message and returns command resolver.
	*
	* @param string $channel Name of the channel
	* @param string $message Content of the possible command
	*
	* @return WiseChatAbstractCommand
	*/
	private function getCommandResolver($channel, $message) {
		$commandClassName = $this->getCommandClassNameFromMessage($message);
		$commandFile = $this->getCommandClassFileByClassName($commandClassName);
		
		if (file_exists($commandFile)) {
			require_once($commandFile);
			
			$tokens = $this->getTokenizedMessage($message);
			array_shift($tokens);
			
			return new $commandClassName($channel, $tokens);
		}
		
		return null;
	}
	
	private function isPossiblyCommand($message) {
		return strlen($message) > 0 && strpos($message, '/') === 0;
	}
	
	private function isAdminLoggedIn() {
		return current_user_can('manage_options');
	}
	
	private function getTokenizedMessage($message) {
		return preg_split('/\s+/', trim(trim($message), '/'));
	}
	
	private function getCommandClassNameFromMessage($message) {
		$tokens = $this->getTokenizedMessage($message);
		$commandName = str_replace('/', '', ucfirst($tokens[0]));
		
		return "WiseChat{$commandName}Command";
	}
	
	private function getCommandClassFileByClassName($className) {
		return dirname(__FILE__)."/commands/{$className}.php";
	}
}