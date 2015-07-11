<?php 

/**
 * Wise Chat admin bans settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatBansTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('bans', 'Current Bans', 'bansCallback', 'void'),
			array('ban_add', 'New Ban', 'banAddCallback', 'void'),
			
			array('_section', 'Automatic Bans'),
			array('enable_autoban', 'Enable Automatic Bans', 'booleanFieldCallback', 'boolean', 'The feature detects how many bad words are being used by an user and when the threshold is reached the user is banned from posting messages for 1 day'),
			array('autoban_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages containing bad words could be posted by an user before the user gets automatically banned'),
			
			array('_section', 'Flood Control'),
			array('enable_flood_control', 'Enable Flood Control', 'booleanFieldCallback', 'boolean', 'Enables automatic bans for users that post messages too often.'),
			array('flood_control_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages (in given time frame) could be posted by an user before the user gets automatically banned for 1 day'),
			array('flood_control_time_frame', 'Time Frame', 'stringFieldCallback', 'integer', 'Time frame in minutes for flood control'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'bans' => null,
			'ban_add' => null,
			'enable_autoban' => 0,
			'autoban_threshold' => '3',
			'enable_flood_control' => 0,
			'flood_control_threshold' => 200,
			'flood_control_time_frame' => 1,
		);
	}
	
	public function getParentFields() {
		return array(
			'autoban_threshold' => 'enable_autoban',
			'flood_control_threshold' => 'enable_flood_control',
			'flood_control_time_frame' => 'enable_flood_control'
		);
	}
	
	public function deleteBanAction() {
		$ip = $_GET['ip'];
		$ban = $this->bansDAO->getBanByIp($ip);
		if ($ban !== null) {
			$this->bansDAO->deleteByIp($ip);
			$this->addMessage('Ban has been deleted');
		}
	}
	
	public function addBanAction() {
		$newBanIP = $_GET['newBanIP'];
		$newBanDuration = $_GET['newBanDuration'];
		
		$ban = $this->bansDAO->getBanByIp($newBanIP);
		if ($ban !== null) {
			$this->addErrorMessage('This IP is already banned');
			return;
		}
		
		if (strlen($newBanIP) > 0) {
			$duration = $this->bansDAO->getDurationFromString($newBanDuration);
			
			$this->bansDAO->createAndSave($newBanIP, $duration);
			$this->addMessage("Ban has been added");
		}
	}
	
	public function bansCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$bans = $this->bansDAO->getAll();
		
		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($bans) == 0) {
			$html .= '<small>No bans were added yet</small>';
		}
		foreach ($bans as $ban) {
			$deleteURL = $url.'&wc_action=deleteBan&ip='.urlencode($ban->ip);
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
			$html .= sprintf("[%s] %s left | %s", $ban->ip, $this->getTimeSummary($ban->time - time()), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}
	
	public function banAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addBan");
		
		printf(
			'<input type="text" value="" placeholder="IP address to ban" id="newBanIP" name="newBanIP" />'.
			'<input type="text" value="" placeholder="Duration, e.g. 4m, 2d, 16h" id="newBanDuration" name="newBanDuration" />'.
			'<a class="button-secondary" href="%s" title="Adds a new ban for given IP address and duration" onclick="%s">Add Ban</a>',
			wp_nonce_url($url),
			'this.href += \'&newBanIP=\' + jQuery(\'#newBanIP\').val() + \'&newBanDuration=\' + jQuery(\'#newBanDuration\').val();'
		);
	}
	
	private function getTimeSummary($seconds) {
		$dateFirst = new DateTime("@0");
		$dateSecond = new DateTime("@$seconds");
		
		return $dateFirst->diff($dateSecond)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}