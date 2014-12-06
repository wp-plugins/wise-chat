<?php

require_once "WiseChatAbstractCommand.php";

/**
 * Wise Chat /ban command.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatBanCommand extends WiseChatAbstractCommand {

	public function execute() {
		$user = isset($this->arguments[0]) ? $this->arguments[0] : null;
		
		if ($user !== null) {
			$messageUser = $this->getMessageUser($user);
			
			if ($messageUser !== null) {
				$duration = 60 * 60;
				if (isset($this->arguments[1])) {
					$durationCustom = $this->arguments[1];
					if (preg_match('/\d+m/', $durationCustom)) {
						$duration = intval($durationCustom) * 60;
					}
					if (preg_match('/\d+h/', $durationCustom)) {
						$duration = intval($durationCustom) * 60 * 60;
					}
					if (preg_match('/\d+d/', $durationCustom)) {
						$duration = intval($durationCustom) * 60 * 60 * 24;
					}
					
					if ($duration === 0) {
						$duration = 60 * 60;
					}
				}
				if ($this->addBan($messageUser->ip, $duration)) {
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
	
	protected function getMessageUser($user) {
		global $wpdb;
		
		$user = addslashes($user);
		$table = WiseChatInstaller::getMessagesTable();
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE channel = \"{$this->channel}\" AND user = \"$user\" ORDER BY id DESC LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
	
	private function addBan($ip, $duration) {
		global $wpdb;
		
		$table = WiseChatInstaller::getBansTable();
		$currentBan = $wpdb->get_results("SELECT * FROM {$table} WHERE ip = \"{$ip}\";");
		
		if (is_array($currentBan) && count($currentBan) > 0) {
			return false;
		} else {
			$wpdb->insert($table,
				array(
					'created' => time(),
					'time' => time() + $duration,
					'ip' => $ip
				)
			);
			
			return true;
		}
	}
}