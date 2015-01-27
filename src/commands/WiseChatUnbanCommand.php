<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat /unban command.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatUnbanCommand extends WiseChatAbstractCommand {
	
	public function execute() {
		$ip = isset($this->arguments[0]) ? $this->arguments[0] : null;
		
		if ($ip !== null) {
			$ban = $this->bansDAO->getBanByIp($ip);
			
			if ($ban !== null) {
				$this->bansDAO->deleteByIp($ban->ip);
				$this->addMessage("Ban on IP ".$ban->ip." has been removed");
			} else {
				$this->addMessage('IP was not found');
			}
		} else {
			$this->addMessage('Please specify the IP');
		}
	}
}