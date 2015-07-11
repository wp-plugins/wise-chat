<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat command: /unban
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatUnbanCommand extends WiseChatAbstractCommand {
	
	public function execute() {
		$ip = isset($this->arguments[0]) ? $this->arguments[0] : null;
		
		if ($ip !== null) {
			$ban = $this->bansDAO->getBanByIp($ip);
			
			if ($ban !== null) {
				$this->bansDAO->deleteByIp($ban->ip);
				$this->addMessage("Ban on IP address ".$ban->ip." has been removed");
			} else {
				$this->addMessage('There is no ban for this IP address');
			}
		} else {
			$this->addMessage('Please specify the IP address');
		}
	}
}