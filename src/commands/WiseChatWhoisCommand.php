<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat command: /whois [userName]
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatWhoisCommand extends WiseChatAbstractCommand {

	public function execute() {
		$user = isset($this->arguments[0]) ? $this->arguments[0] : null;
		
		if ($user !== null) {
			$messageUser = $this->messagesDAO->getLastMessageByUserName($this->channel, $user);
			
			if ($messageUser !== null) {
				$details = sprintf("User: %s, IP: %s", $user, $messageUser->ip);
				
				$this->addMessage($details);
			} else {
				$this->addMessage('User was not found');
			}
		} else {
			$this->addMessage('Please specify the user');
		}
	}
}