<?php

/**
 * Wise Chat main services class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatService {

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = new WiseChatUsersDAO();
	}
	
	/**
	* Determines whether the chat is open according to the settings.
	*
	* @return boolean
	*/
	public function isChatOpen() {
		if ($this->options->isOptionEnabled('enable_opening_control', false)) {
			$chatOpeningDays = $this->options->getOption('opening_days');
			if (is_array($chatOpeningDays) && !in_array(date('l'), $chatOpeningDays)) {
				return false;
			}
			
			$chatOpeningHours = $this->options->getOption('opening_hours');
			$openingHour = $chatOpeningHours['opening'];
			$openingMode = $chatOpeningHours['openingMode'];
			$startHourDate = null;
			if ($openingMode != '24h') {
				$startHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$openingHour.' '.$openingMode);
			} else {
				$startHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$openingHour);
			}
			
			$closingHour = $chatOpeningHours['closing'];
			$closingMode = $chatOpeningHours['closingMode'];
			$endHourDate = null;
			if ($closingMode != '24h') {
				$endHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$closingHour.' '.$closingMode);
			} else {
				$endHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$closingHour);
			}
			
			if ($startHourDate != null && $endHourDate != null) {
				$nowDate = new DateTime();
			
				if ($nowDate->format('U') < $startHourDate->format('U') || $nowDate->format('U') > $endHourDate->format('U')) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	* Determines whether the chat is full according to the settings.
	*
	* @param string $channel
	*
	* @return boolean
	*/
	public function isChatChannelFull($channel) {
		$limit = $this->options->getIntegerOption('channel_users_limit', 0);
		if ($limit > 0) {
			$amountOfCurrentUsers = $this->usersDAO->getAmountOfCurrentUsersOfChannel($channel);
			$currentUserName = $this->usersDAO->getUserName();
			
			$isInChannel = $this->usersDAO->hasUserPingMessages($channel, $currentUserName);
			if (!$isInChannel) {
				$amountOfCurrentUsers++;
			}
			
			if ($amountOfCurrentUsers > $limit) {
				return true;
			}
		}
		
		return false;
	}
}