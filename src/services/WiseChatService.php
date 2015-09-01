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
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatChannelsDAO
	*/
	protected $channelsDAO;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = new WiseChatChannelsDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
		$this->userService = new WiseChatUserService();
	}
	
	/**
	* Returns unique ID for the plugin.
	*
	* @return string
	*/
	public function getChatID() {
		return 'wc'.md5(uniqid('', true));
	}
	
	/**
	* Determines whether the chat is restricted for anonymous users.
	*
	* @return boolean
	*/
	public function isChatRestrictedForAnonymousUsers() {
		return $this->options->getOption('access_mode') == 1 && !$this->usersDAO->isWpUserLogged();
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
				
				$nowU = $nowDate->format('U');
				$startHourDateU = $startHourDate->format('U');
				$endHourDateU = $endHourDate->format('U');
				
				if ($startHourDateU <= $endHourDateU) {
					if ($nowU < $startHourDateU || $nowU > $endHourDateU) {
						return false;
					}
				} else {
					if ($nowU > $endHourDateU && $nowU < $startHourDateU) {
						return false;
					}
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
			$this->userService->refreshChannelUsersData();
			$amountOfCurrentUsers = $this->channelUsersDAO->getAmountOfUsersInChannel($channel);
			$currentUserName = $this->usersDAO->getUserName();
			
			if ($this->channelUsersDAO->getByActiveUserAndChannel($currentUserName, $channel) === null) {
				$amountOfCurrentUsers++;
			}
			
			if ($amountOfCurrentUsers > $limit) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* Determines whether the current user has to be authorized.
	*
	* @param string $channelName
	*
	* @return boolean
	*/
	public function hasUserToBeAuthorizedInChannel($channelName) {
		$channel = $this->channelsDAO->getByName($channelName);
		
		return $channel !== null && strlen($channel->password) > 0 && !$this->usersDAO->isUserAuthorizedForChannel($channelName);
	}
	
	/**
	* Authorizes the current user in the given channel.
	*
	* @param string $channelName
	* @param string $password
	*
	* @return boolean
	*/
	public function authorize($channelName, $password) {
		$channel = $this->channelsDAO->getByName($channelName);
		
		if ($channel !== null && $channel->password === md5($password)) {
			$this->usersDAO->markAuthorizedForChannel($channelName);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	* Determines whether the amount of the chat channels has been reached.
	*
	* @param string $channel
	*
	* @return boolean
	*/
	public function isChatChannelsLimitReached($channel) {
		$limit = $this->options->getIntegerOption('channels_limit', 0);
		if ($limit > 0) {
			$this->userService->refreshChannelUsersData();
			$amountOfChannels = $this->channelUsersDAO->getAmountOfActiveBySessionId(session_id());
			$currentUserName = $this->usersDAO->getUserName();
			
			if ($this->channelUsersDAO->getByActiveUserAndChannel($currentUserName, $channel) === null) {
				$amountOfChannels++;
			}
			
			if ($amountOfChannels > $limit) {
				return true;
			}
		}
		
		return false;
	}
}