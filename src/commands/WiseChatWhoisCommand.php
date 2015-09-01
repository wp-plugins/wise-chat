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
			$channelUser = $this->channelUsersDAO->getByUserAndChannel($user, $this->channel);
			if ($channelUser !== null) {
				$details = sprintf("User: %s, IP: %s", $user, $channelUser->ip);
				
				$this->addMessage($details);
			} else {
				$this->addMessage('User was not found');
			}
		} else {
			$this->addMessage('Please specify the user');
		}
	}
}