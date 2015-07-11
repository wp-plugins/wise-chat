<?php

/**
 * Wise Chat services regarding bans.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatBansService {

	/**
	* @var WiseChatBansDAO
	*/
	private $bansDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->bansDAO = new WiseChatBansDAO();
	}
	
	/**
	* Maintenance actions performed at start-up.
	*
	* @return null
	*/
	public function startUpMaintenance() {
		$this->bansDAO->deleteOldBans();
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @return null
	*/
	public function periodicMaintenance() {
		$this->bansDAO->deleteOldBans();
	}
}