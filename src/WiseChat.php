<?php

require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatBansDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/WiseChatRenderer.php');

/**
 * Wise Chat core class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChat {
	private $baseDir;
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
	
	public function __construct($baseDir) {
		$this->baseDir = $baseDir;
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->renderer = new WiseChatRenderer();
	}
	
	public function initializeCore() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		
		$this->usersDAO->generateUserName();
		add_action('plugins_loaded', array($this->usersDAO, 'generateLoggedUserName'));
		
		add_shortcode('wise-chat', array($this, 'renderShortcode'));
	}
	
	public function enqueueScripts() {
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
		
		$this->options = array_merge($this->options, $atts);
   
		return $this->getRenderedChat($channel);;
	}
	
	private function getRenderedChat($channel = null) {
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
			$messagesRendering .= "<br />";
			$lastId = $message->id;
		}
		
		$hintMessage = str_replace("'", '', $this->options['hint_message']);
		
		$customizationsPanel = $this->getCustomizationsPanel();
		$userNamePanel = $this->getCurrentUserNamePanel();
		$outString .= "<div id='$chatId' class='wcContainer'>
				<div class='wcMessages'>{$messagesRendering}</div>
				$userNamePanel
				<input class='wcInput' type='text' maxlength='{$this->options['message_max_length']}' placeholder='$hintMessage' />
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
		
		$outString .= $this->renderer->getRenderedStylesDefinition($chatId);
		$outString .= "<script type='text/javascript'>jQuery(window).load(function() {  new WiseChatController({$jsOptionsRender}); }); </script>";
		
		$this->bansDAO->deleteOldBans();
		
		return $outString;
	}
	
	private function getCurrentUserNamePanel() {
		$html = "";
		$showUserName = isset($this->options['show_user_name']) && $this->options['show_user_name'] == '1' && !is_user_logged_in();
		$currentUserName = $this->usersDAO->getUserName();
		
		if ($showUserName) {
			$html = "<span class='wcCurrentUserName'>$currentUserName:</span>";
		}
		
		return $html;
	}
	
	private function getCustomizationsPanel() {
		$html = '';
		$allowChangeUserName = isset($this->options['allow_change_user_name']) && $this->options['allow_change_user_name'] == '1' && !is_user_logged_in();
		$currentUserName = $this->usersDAO->getUserName();
		
		if ($allowChangeUserName === true) {
			$html .= "<div class='wcCustomizations'>
					<a href='javascript://' class='wcCustomizeButton'>Customize</a>
					<div class='wcCustomizationsPanel' style='display:none;'>
						<label>Name: <input class='wcUserName' type='text' value='$currentUserName' /></label><input class='wcUserNameApprove' type='button' value='OK' />
					</div>
				</div>
			";
		}
		
		return $html;
	}
}