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
			$channelUser = $this->channelUsersDAO->getByUserAndChannel($user, $this->channel);
			
			if ($channelUser !== null) {
				$duration = $this->bansDAO->getDurationFromString($this->arguments[1]);
				
				if ($this->bansDAO->createAndSave($channelUser->ip, $duration)) {
					$this->addMessage("IP ".$channelUser->ip." has been banned, time: {$duration} seconds");
				} else {
					$this->addMessage("IP ".$channelUser->ip." is already banned");
				}
			} else {
				$this->addMessage('User was not found');
			}
		} else {
			$this->addMessage('Please specify the user');
		}
	}
}