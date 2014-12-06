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
			$ban = $this->getBanByIp($ip);
			
			if ($ban !== null) {
				
				$this->removeBan($ban->ip);
				$this->addMessage("IP ".$ban->ip." has been unbanned");
			} else {
				$this->addMessage('IP was not found');
			}
		} else {
			$this->addMessage('Please specify the IP');
		}
	}
	
	protected function getBanByIp($ip) {
		global $wpdb;
		
		$ip = addslashes($ip);
		$table = WiseChatInstaller::getBansTable();
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE ip = \"{$ip}\" LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
	
	private function removeBan($ip) {
		global $wpdb;
		
		$table = WiseChatInstaller::getBansTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE ip = '{$ip}'");
	}
}