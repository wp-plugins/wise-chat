<?php 

/**
 * Wise Chat admin advanced settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatAdvancedTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array(
				'ajax_engine', 'AJAX Engine', 'selectCallback', 'string', 
				"Engine for AJAX requests made by the chat. <br />The Default engine is the most compatible but it has an average performance. The Lightweight AJAX engine has much lower response time and consumes less CPU, however, it could be unstable in future versions of WordPress.", 
				WiseChatAdvancedTab::getAllEngines()
			),
			array(
				'messages_refresh_time', 'Refresh Time', 'selectCallback', 'string', 
				"Determines how often the chat should look for new messages. Lower value means higher CPU usage and more HTTP requests.", 
				WiseChatAdvancedTab::getRefreshTimes()
			)
		);
	}
	
	public function getDefaultValues() {
		return array(
			'ajax_engine' => '',
			'messages_refresh_time' => 3000
		);
	}
	
	public static function getAllEngines() {
		return array(
			'' => 'Default',
			'lightweight' => 'Lightweight AJAX'
		);
	}
	
	public static function getRefreshTimes() {
		return array(
			1000 => '1s',
			2000 => '2s',
			3000 => '3s',
			4000 => '4s',
			5000 => '5s',
			10000 => '10s',
			15000 => '15s',
			20000 => '20s',
			30000 => '30s',
			60000 => '60s',
			120000 => '120s',
		);
	}
}