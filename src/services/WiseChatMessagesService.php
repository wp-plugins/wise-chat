<?php

/**
 * Wise Chat services regarding messages.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatMessagesService {

	/**
	* @var WiseChatActionsDAO
	*/
	private $actionsDAO;

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
	}
	
	/**
	* Maintenance actions performed at start-up.
	*
	* @param string $channel Given channel
	*
	* @return null
	*/
	public function startUpMaintenance($channel) {
		$this->autoDeleteOldMessages($channel);
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @param string $channel Given channel
	*
	* @return null
	*/
	public function periodicMaintenance($channel) {
		$this->autoDeleteOldMessages($channel);
	}
	
	private function autoDeleteOldMessages($channel) {
		$minutesThreshold = intval($this->options->getOption('auto_clean_after', 0));
		
		if ($minutesThreshold > 0) {
			$ids = $this->messagesDAO->deleteByTimeThresholdAndChannel($minutesThreshold, $channel);
			if (count($ids) > 0) {
				$this->actionsDAO->publishAction('deleteMessages', array('ids' => $ids));
			}
		}
	}
}