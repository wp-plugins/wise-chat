<?php

require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatBansDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/rendering/WiseChatRenderer.php');
require_once(dirname(__FILE__).'/rendering/WiseChatCssRenderer.php');

/**
 * Wise Chat core class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChat {
	/**
	* @var string Plugin's base directory
	*/
	private $baseDir;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	/**
	* @var WiseChatBansDAO
	*/
	private $bansDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var WiseChatCssRenderer
	*/
	private $cssRenderer;
	
	public function __construct($baseDir) {
		$this->baseDir = $baseDir;
		$this->options = WiseChatOptions::getInstance();
		$this->options->setBaseDir($baseDir);
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->renderer = new WiseChatRenderer();
		$this->cssRenderer = new WiseChatCssRenderer();
	}
	
	public function initializeCore() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		
		$this->usersDAO->generateUserName();
		add_action('plugins_loaded', array($this->usersDAO, 'generateLoggedUserName'));
		
		add_shortcode('wise-chat', array($this, 'renderShortcode'));
	}
	
	public function enqueueScripts() {
		wp_enqueue_script('wise_chat_messages_history',  $this->baseDir.'js/utils/messages_history.js', array());
		wp_enqueue_script('wise_chat_messages',  $this->baseDir.'js/ui/messages.js', array());
		wp_enqueue_script('wise_chat_settings',  $this->baseDir.'js/ui/settings.js', array());
		wp_enqueue_script('wise_chat_core',  $this->baseDir.'js/wise_chat.js', array());
		wp_enqueue_style('wise_chat_core', $this->baseDir.'css/wise_chat.css');
	}
	
	public function render($channel = null) {
		echo $this->getRenderedChat($channel);
	}

	public function renderShortcode($atts) {
		if (!is_array($atts)) {
			$atts = array();
		}
		extract(shortcode_atts(array(
			'channel' => 'global',
		), $atts));
		
		$this->options->replaceOptions($atts);
   
		return $this->getRenderedChat($channel);;
	}
	
	private function getRenderedChat($channel = null) {
		if ($this->options->isOptionEnabled('restrict_to_wp_users') && !$this->usersDAO->isWpUserLogged()) {
			return sprintf("<div class='wcAccessDenied'>%s</div>", $this->options->getOption('message_error_4', 'Only logged in users are allowed to enter the chat'));
		}
		
		$outString = '';

		if ($channel === null || $channel === '') {
			$channel = 'global';
		}
		$chatId = 'wc'.md5(uniqid('', true));
		
		$messages = $this->messagesDAO->getMessages($channel);
		$messagesRendering = '';
		$lastId = 0;
		foreach ($messages as $message) {
			// ommit non-admin messages:
			if ($message->admin == 1 && !$this->usersDAO->isWpUserAdminLogged()) {
				continue;
			}
				
			$messagesRendering .= $this->renderer->getRenderedMessage($message);
			$lastId = $message->id;
		}
		
		$customizationsPanel = $this->getCustomizationsPanel();
		$userNamePanel = $this->getCurrentUserNamePanel();
		$containerClasses = $this->getContainerClasses();
		$submitButton = $this->getSubmitButton();
		$inputField = $this->getInputField();
		$outString .= "<div id='$chatId' class='$containerClasses'>
				<div class='wcMessages'>{$messagesRendering}</div>
				$userNamePanel
				$submitButton
				<div class='wcInputContainer'>
					$inputField
				</div>
				$customizationsPanel
			</div>
		";
		
		$jsOptions = array(
			'chatId' => $chatId,
			'channel' => $channel,
			'lastId' => $lastId,
			'siteURL' => get_site_url()
		);
		$jsOptionsRender = json_encode($jsOptions);
		
		$outString .= $this->cssRenderer->getCssDefinition($chatId);
		$outString .= "<script type='text/javascript'>jQuery(window).load(function() {  new WiseChatController({$jsOptionsRender}); }); </script>";
		
		$this->bansDAO->deleteOldBans();
		
		return $outString;
	}
	
	private function getInputField() {
		$html = '';
		$hintMessage = $this->options->getEncodedOption('hint_message');
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		
		if (!$this->options->isOptionEnabled('multiline_support')) {
			$html = "<input class='wcInput' type='text' maxlength='{$messageMaxLength}' placeholder='{$hintMessage}' />";
		} else {
			$html = "<textarea class='wcInput' maxlength='{$messageMaxLength}' placeholder='{$hintMessage}'></textarea>";
		}
		
		return $html;
	}
	
	private function getCurrentUserNamePanel() {
		$html = "";
		$showUserName = $this->options->isOptionEnabled('show_user_name') && !$this->usersDAO->isWpUserLogged();
		$currentUserName = $this->usersDAO->getUserName();
		
		if ($showUserName) {
			$html = "<span class='wcCurrentUserName'>$currentUserName:</span>";
		}
		
		return $html;
	}
	
	private function getSubmitButton() {
		if ($this->options->isOptionEnabled('show_message_submit_button')) {
			return sprintf("<input type='button' class='wcSubmitButton' value='%s' />", $this->options->getEncodedOption('message_submit_button_caption', 'Send'));
		}
		
		return '';
	}
	
	private function getCustomizationsPanel() {
		$html = '';
	
		$allowChangeUserName = $this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged();
		$isAnyCustomizationEnabled = $allowChangeUserName;
		
		if ($isAnyCustomizationEnabled) {
			$html .= "<div class='wcCustomizations'>";
			$html .= sprintf("<a href='javascript://' class='wcCustomizeButton'>%s</a>", $this->options->getEncodedOption('message_customize', 'Customize'));
			$html .= "<div class='wcCustomizationsPanel' style='display:none;'>";
			if ($allowChangeUserName) {
				$currentUserName = $this->usersDAO->getUserName();
				$html .= sprintf(
							"<label>%s: <input class='wcUserName' type='text' value='%s' /></label>", 
							$this->options->getEncodedOption('message_name', 'Name'), htmlentities($currentUserName)
						);
				$html .= sprintf(
							"<input class='wcUserNameApprove' type='button' value='%s' />", 
							$this->options->getEncodedOption('message_save', 'Save')
						);
			}
			$html .= "</div></div>";
		}
		
		return $html;
	}
	
	private function getContainerClasses() {
		$classes = array('wcContainer');
		if ($this->options->isOptionEnabled('show_message_submit_button')) {
			$classes[] = 'wcSubmitButtonIncluded';
		}
		
		return implode(' ', $classes);
	}
}