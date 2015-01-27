<?php

require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');

/**
 * Wise Chat renderer class.
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
	* @var array
	*/
	private $options;
	
	public function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
		$this->usersDAO = new WiseChatUsersDAO();
	}
	
	/**
	* Returns rendered message.
	*
	* @param array $message Message details
	*
	* @return string HTML source
	*/
	public function getRenderedMessage($message) {
		$addDate = '';
		if (date('Y-m-d', $message->time) != date('Y-m-d')) {
			$addDate = date('Y-m-d', $message->time).' ';
		}
		$formated = '<span class="wcMessageTime">'.$addDate.date('H:i', $message->time).'</span> ';
		
		$userNameFormated = $message->user;
		if ($userNameFormated == $this->usersDAO->getUserName()) {
			$userNameFormated = "<strong>{$userNameFormated}</strong>";
		}
		$formated .= '<span class="wcMessageUser">'.$userNameFormated.'</span>: ';
		
		$formated .= htmlspecialchars($message->text, ENT_QUOTES, 'UTF-8');
		
		// mark WP user:
		$wpUser = $this->usersDAO->getWpUserByDisplayName($message->user);
		if ($wpUser !== null) {
			$formated = '<span class="wcWpMessage">'.$formated.'</span>';
		}
		
		return $formated;
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
		
		if (!empty($this->options['text_color_logged_user'])) {
			$styles["$containerId .wcWpMessage"][] = "color: ".$this->options['text_color_logged_user'].";";
		}
		if (!empty($this->options['background_color'])) {
			$styles["$containerId .wcMessages"][] = "background-color: ".$this->options['background_color'].";";
		}
		if (!empty($this->options['background_color_input'])) {
			$styles["$containerId .wcInput"][] = "background-color: ".$this->options['background_color_input'].";";
		}
		if (!empty($this->options['text_color'])) {
			$styles["$containerId .wcInput, $containerId .wcMessages, $containerId .wcCurrentUserName"][] = "color: ".$this->options['text_color'].";";
		}
		
		$html = '<style type="text/css">';
		foreach ($styles as $cssSelector => $stylesList) {
			$html .= "$cssSelector { ".implode(" ", $stylesList)." }\n";
		}
		$html .= '</style>';
		
		return $html;
	}
}