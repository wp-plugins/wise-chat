<?php 

/**
 * Wise Chat admin channels settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatChannelsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('channels', 'Channels', 'channelsChallback', 'void'),
			array('admin_actions', 'Actions', 'adminActionsCallback', 'void'),
			array('auto_clean_after', 'Auto-clean Messages', 'stringFieldCallback', 'string', 'Deletes messages older than given amount of minutes. Empty field means no messages will be deleted.'),
			array('channel_users_limit', 'Users Limit', 'stringFieldCallback', 'string', 'Maximum users allowed to enter a channel. Empty fields means there is no limit to the amount of chat participants.'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'channels' => null,
			'admin_actions' => null,
			'auto_clean_after' => null,
			'channel_users_limit' => null
		);
	}
	
	public function clearChannelAction() {
		$channel = $_GET['channel'];
		
		$ban = $this->messagesDAO->deleteByChannel($channel);
		$this->actionsDAO->publishAction('deleteAllMessagesFromChannel', array('channel' => $channel));
		$this->addMessage('All messages from the channel have been deleted');
	}
	
	public function backupChannelAction() {
		$channel = $_GET['channel'];
		$channelStripped = preg_replace("/[^[:alnum:][:space:]]/ui", '', $channel);
		$filename = "WiseChatChannelBackup-{$channelStripped}.csv";
		
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
		
		$messages = $this->messagesDAO->getMessages($channel);
		
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array('ID', 'Time', 'User', 'Message', 'IP'));
		foreach ($messages as $message) {
			$messageArray = array(
				$message->id, date("Y-m-d H:i:s", $message->time), $message->user, $message->text, $message->ip
			);
			fputcsv($df, $messageArray);
		}
		fclose($df);
		
		echo ob_get_clean();
		
		die();
	}
	
	public function clearAllChannelsAction() {
		$this->messagesDAO->deleteAll();
		$this->actionsDAO->publishAction('deleteAllMessages', array());
		$this->addMessage('All messages have been deleted');
	}
	
	public function channelsChallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		
		$summary = $this->messagesDAO->getChannelsSummary();
		
		$html = "<table class='wp-list-table widefat'>";
		if (count($summary) == 0) {
			$html .= '<tr><td>No channels created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Name</th><th>Total messages</th><th>Current users</th><th>Last message (UTC)</th><th>Actions</th></tr></thead>';
		}
		
		foreach ($summary as $key => $channel) {
			$clearURL = $url.'&wc_action=clearChannel&channel='.urlencode($channel->channel);
			$clearLink = "<a href='{$clearURL}' title='Deletes all messages from the channel' onclick='return confirm(\"Are you sure?\")'>Clear</a>";
			
			$backupURL = $url.'&wc_action=backupChannel&channel='.urlencode($channel->channel);
			$backupLink = "<a href='{$backupURL}' title='Export messages to CSV file'>Backup</a>";
			
			$actions = array($clearLink, $backupLink);
			
			$classes = $key % 2 == 0 ? 'alternate' : '';
			
			$html .= sprintf(
				'<tr class="%s"><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', 
				$classes, $channel->channel, $channel->messages, $channel->users, date('Y-m-d H:i:s', $channel->last_message), implode(' | ', $actions)
			);
		}
		$html .= "</table><p class='description'>Notice: channels without messages are not included here. Users counter accuracy: 120 s.</p>";
		print($html);
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearAllChannels");
		
		printf(
			'<a class="button-secondary" href="%s" title="Deletes all messages sent to any channel" onclick="return confirm(\'Are you sure? All messages will be lost.\')">Clear All Messages</a>',
			wp_nonce_url($url)
		);
	}
}