<?php

require_once('WiseChatAbstractCommand.php');

/**
 * Wise Chat commands resolver.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatCommandsResolver {
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	public function __construct() {
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->usersDAO = new WiseChatUsersDAO();
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
		if ($this->isPotentialCommand($message) && $this->usersDAO->isWpUserAdminLogged()) {
			// print typed command (visible only for admins):
			$this->messagesDAO->addMessage($user, $channel, $message, true);
		
			// execute command:
			$resolver = $this->getCommandResolver($channel, $message);
			if ($resolver !== null) {
				$resolver->execute();
			} else {
				$this->messagesDAO->addMessage(WiseChatAbstractCommand::SYSTEM_USER_NAME, $channel, 'Command not found', true);
			}
		
			return true;
		}
		
		return false;
	}
	
	/**
	* Tokenizes command and returns command resolver.
	*
	* @param string $channel Name of the channel
	* @param string $command The command
	*
	* @return WiseChatAbstractCommand
	*/
	private function getCommandResolver($channel, $command) {
		$commandClassName = $this->getClassNameFromCommand($command);
		$commandFile = $this->getCommandClassFileByClassName($commandClassName);
		
		if (file_exists($commandFile)) {
			require_once($commandFile);
			
			$tokens = $this->getTokenizedCommand($command);
			array_shift($tokens);
			
			return new $commandClassName($channel, $tokens);
		}
		
		return null;
	}
	
	/**
	* Checks whether a text can be recognized as a command.
	*
	* @param string $text The potential command
	*
	* @return boolean
	*/
	private function isPotentialCommand($text) {
		return strlen($text) > 0 && strpos($text, '/') === 0;
	}
	
	private function getTokenizedCommand($command) {
		return preg_split('/\s+/', trim(trim($command), '/'));
	}
	
	private function getClassNameFromCommand($command) {
		$tokens = $this->getTokenizedCommand($command);
		$commandName = str_replace('/', '', ucfirst($tokens[0]));
		
		return "WiseChat{$commandName}Command";
	}
	
	private function getCommandClassFileByClassName($className) {
		return dirname(__FILE__)."/{$className}.php";
	}
}