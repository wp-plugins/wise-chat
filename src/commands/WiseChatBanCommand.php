<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat command: /ban [userName] [duration]
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatBanCommand extends WiseChatAbstractCommand {

	public function execute() {
		$user = isset($this->arguments[0]) ? $this->arguments[0] : null;
		
		if ($user !== null) {
			$messageUser = $this->messagesDAO->getLastMessageByUserName($this->channel, $user);
			
			if ($messageUser !== null) {
				$duration = $this->bansDAO->getDurationFromString($this->arguments[1]);
				
				if ($this->bansDAO->createAndSave($messageUser->ip, $duration)) {
					$this->addMessage("IP ".$messageUser->ip." has been banned, time: {$duration} seconds");
				} else {
					$this->addMessage("IP ".$messageUser->ip." is already banned");
				}
			} else {
				$this->addMessage('User was not found');
			}
		} else {
			$this->addMessage('Please specify the user');
		}
	}
}