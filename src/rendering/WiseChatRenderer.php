<?php

require_once(dirname(__FILE__).'/filters/WiseChatFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatLinksFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatEmoticonsFilter.php');

/**
 * Wise Chat message rendering class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatRenderer {
	
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
	* Returns rendered message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	public function getRenderedMessage($message) {
		$formated = $this->getRenderedMessageDateAndTime($message);
		$formated .= $this->getRenderedUserName($message);
		$formated .= $this->getRenderedMessageContent($message);
		
		$messageClasses = array('wcMessage');
		if ($this->usersDAO->getWpUserByDisplayName($message->user) !== null) {
			$messageClasses[] = 'wcWpMessage';
		}
		if ($message->user == $this->usersDAO->getUserName()) {
			$messageClasses[] = 'wcCurrentUserMessage';
		}
		
		return sprintf('<div class="%s">%s</div>', implode(' ', $messageClasses), $formated);
	}
	
	/**
	* Returns rendered date and time (UTC time) for the given message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	private function getRenderedMessageDateAndTime($message) {
		$utcDateAndTime = gmdate('c', $message->time);
		
		return sprintf('<span class="wcMessageTime" data-utc="%s"></span> ', $utcDateAndTime, $utcDateAndTime);
	}
	
	/**
	* Returns rendered user name for given message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	private function getRenderedUserName($message) {
		$wpUser = $this->usersDAO->getWpUserByDisplayName($message->user);
		$formatedUserName = $message->user;
		
		if ($this->options->isOptionEnabled('link_wp_user_name')) {
			$linkUserNameTemplate = $this->options->getOption('link_user_name_template', null);
			$userNameLink = null;
			if ($linkUserNameTemplate != null) {
				$variables = array('username' => $message->user, 'id' => $wpUser !== null ? $wpUser->ID : '');
				$userNameLink = $this->getTemplatedString($variables, $linkUserNameTemplate);
			} else if ($wpUser !== null) {
				$userNameLink = get_author_posts_url($wpUser->ID, $message->user);
			}
			
			if ($userNameLink != null) {
				$formatedUserName = sprintf("<a href='%s' rel='nofollow'>%s</a>", $userNameLink, $formatedUserName);
			}
		}
		
		return '<span class="wcMessageUser">'.$formatedUserName.'</span>: ';
	}
	
	/**
	* Returns rendered message content.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	private function getRenderedMessageContent($message) {
		$formatedMessageContent = htmlspecialchars($message->text, ENT_QUOTES, 'UTF-8');
		if ($this->options->isOptionEnabled('allow_post_links')) {
			$formatedMessageContent = WiseChatLinksFilter::filter($formatedMessageContent);
		}
		if ($this->options->isOptionEnabled('emoticons_enabled', true)) {
			$formatedMessageContent = WiseChatEmoticonsFilter::filter($formatedMessageContent);
		}
		
		if ($this->options->isOptionEnabled('multiline_support')) {
			if (strstr($formatedMessageContent, "\n") !== false) {
				$formatedMessageContent = "\n".$formatedMessageContent;
			}
			$formatedMessageContent = str_replace("\n", '<br />', $formatedMessageContent);
		}
		
		return $formatedMessageContent;
	}
	
	private function getTemplatedString($variables, $template) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", urlencode($value), $template);
		}
		
		return $template;
	}
}