<?php

require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');

/**
 * Wise Chat renderer class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatRenderer {
	const URL_REGEXP = "/((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)+[a-zA-Z]{2,6}(\/[^ \?]*)?(\?[^\"'<> ]+)?/i";
	const URL_PROTOCOLS_REGEXP = "/^(https|http|ftp)\:\/\//i";
	
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
		
		return '<div class="'.implode(' ', $messageClasses).'">'.$formated.'</div>';
	}
	
	/**
	* Returns rendered date and time (UTC) for given message.
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
		
		if ($wpUser !== null && $this->options->isOptionEnabled('link_wp_user_name')) {
			$authorURL = get_author_posts_url($wpUser->ID, $message->user);
			$formatedUserName = "<a href='{$authorURL}'>$formatedUserName</a>";
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
			$formatedMessageContent = $this->detectAndConvertURLs($formatedMessageContent);
		}
		
		return $formatedMessageContent;
	}
	
	/**
	* Detects an URL in the given text and replaces it with a hyperlink.
	*
	* @param string $text
	*
	* @return string
	*/
	private function detectAndConvertURLs($text) {
		if (preg_match(self::URL_REGEXP, $text, $matches)) {
			$detectedURL = $matches[0];
			$url = $detectedURL;
			if (!preg_match(self::URL_PROTOCOLS_REGEXP, $detectedURL)) {
				$url = "http://".$detectedURL;
			}
			
			return str_replace($detectedURL, "<a href='".$url."' target='_blank' rel='nofollow'>".urldecode($detectedURL)."</a> ", $text);
		}
		
		return $text;
	}
	
	/**
	* Returns rendered styles definition. 
	*
	* @param string $containerId ID of the container
	*
	* @return string HTML source
	*/
	public function getRenderedStylesDefinition($containerId) {
		$containerId = "#$containerId";
		$styles = array();
		
		if (strlen($this->options->getOption('text_color_logged_user')) > 0) {
			$styles["$containerId .wcWpMessage, $containerId .wcWpMessage a"][] = "color: ".$this->options->getOption('text_color_logged_user').";";
		}
		if (strlen($this->options->getOption('background_color')) > 0) {
			$styles["$containerId .wcMessages"][] = "background-color: ".$this->options->getOption('background_color').";";
		}
		if (strlen($this->options->getOption('background_color_input')) > 0) {
			$styles["$containerId .wcInput"][] = "background-color: ".$this->options->getOption('background_color_input').";";
		}
		if (strlen($this->options->getOption('text_color')) > 0) {
			$styles["$containerId .wcInput, $containerId .wcMessages, $containerId .wcCurrentUserName"][] = "color: ".$this->options->getOption('text_color').";";
		}
		
		$html = '<style type="text/css">';
		foreach ($styles as $cssSelector => $stylesList) {
			$html .= "$cssSelector { ".implode(" ", $stylesList)." }\n";
		}
		$html .= '</style>';
		
		return $html;
	}
}