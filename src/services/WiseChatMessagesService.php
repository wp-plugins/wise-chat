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
	* @return null
	*/
	public function startUpMaintenance() {
		$this->autoDeleteOldMessages();
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @return null
	*/
	public function periodicMaintenance() {
		$this->autoDeleteOldMessages();
	}
	
	private function autoDeleteOldMessages() {
		$minutesThreshold = intval($this->options->getOption('auto_clean_after', 0));
		
		if ($minutesThreshold > 0) {
			$messages = $this->messagesDAO->getMessagesByTimeThreshold($minutesThreshold);
			if (is_array($messages) && count($messages) > 0) {
				$this->messagesDAO->deleteByTimeThreshold($minutesThreshold);
				
				$ids = array();
				foreach ($messages as $message) {
					$ids[] = $message->id;
					
					// remove related attachments:
					$attachementIds = WiseChatImagesPostFilter::getImageIds(htmlspecialchars($message->text, ENT_QUOTES, 'UTF-8'));
					foreach ($attachementIds as $attachementId) {
						wp_delete_attachment(intval($attachementId), true);
					}
				}
				
				$this->actionsDAO->publishAction('deleteMessages', array('ids' => $ids));
			}
		}
	}
}