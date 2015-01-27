<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat /bans command.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatBansCommand extends WiseChatAbstractCommand {
	public function execute() {
		$currentBans = $this->bansDAO->getAll();
		
		if (is_array($currentBans) && count($currentBans) > 0) {
			$bans = array();
			foreach ($currentBans as $ban) {
				$eta = $ban->time - time();
				if ($eta > 0) {
					$bans[] = $ban->ip.' ('.$eta.'s)';
				}
			}
			
			$this->addMessage('Currently banned IPs: '.(count($bans) > 0 ? implode(', ', $bans) : ' empty list'));
		} else {
			$this->addMessage('No bans has been added');
		}
	}
}