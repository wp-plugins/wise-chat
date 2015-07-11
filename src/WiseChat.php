<?php

require_once(dirname(__FILE__).'/WiseChatThemes.php');
require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatActionsDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatFiltersDAO.php');
require_once(dirname(__FILE__).'/dao/filters/WiseChatFilterChain.php');
require_once(dirname(__FILE__).'/rendering/WiseChatRenderer.php');
require_once(dirname(__FILE__).'/rendering/WiseChatCssRenderer.php');
require_once(dirname(__FILE__).'/rendering/WiseChatTemplater.php');
require_once(dirname(__FILE__).'/rendering/filters/WiseChatLinksPreFilter.php');
require_once(dirname(__FILE__).'/rendering/filters/WiseChatShortcodeConstructor.php');
require_once(dirname(__FILE__).'/services/WiseChatService.php');
require_once(dirname(__FILE__).'/services/WiseChatBansService.php');
require_once(dirname(__FILE__).'/services/WiseChatUserService.php');
require_once(dirname(__FILE__).'/services/WiseChatMessagesService.php');

/**
 * Wise Chat core class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChat {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatActionsDAO
	*/
	private $actionsDAO;
	
	/**
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var WiseChatCssRenderer
	*/
	private $cssRenderer;
	
	/**
	* @var WiseChatBansService
	*/
	private $bansService;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
	/**
	* @var WiseChatService
	*/
	private $service;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->renderer = new WiseChatRenderer();
		$this->cssRenderer = new WiseChatCssRenderer();
		$this->bansService = new WiseChatBansService();
		$this->userService = new WiseChatUserService();
		$this->messagesService = new WiseChatMessagesService();
		$this->service = new WiseChatService();
		
		$this->userService->initializeCookie();
	}
	
	public function initializeCore() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		
		$this->usersDAO->generateUserName();
		add_action('after_setup_theme', array($this->usersDAO, 'generateLoggedUserName'));
		
		add_shortcode('wise-chat', array($this, 'renderShortcode'));
	}
	
	public function enqueueScripts() {
		wp_enqueue_script('wise_chat_messages_history',  $this->options->getBaseDir().'js/utils/messages_history.js', array());
		wp_enqueue_script('wise_chat_messages',  $this->options->getBaseDir().'js/ui/messages.js', array());
		wp_enqueue_script('wise_chat_settings',  $this->options->getBaseDir().'js/ui/settings.js', array());
		wp_enqueue_script('wise_chat_actions_executor',  $this->options->getBaseDir().'js/actions/executor.js', array());
		wp_enqueue_script('wise_chat_core',  $this->options->getBaseDir().'js/wise_chat.js', array());
		wp_enqueue_style('wise_chat_core', $this->options->getBaseDir().'css/wise_chat.css');
		wp_enqueue_style('wise_chat_theme', $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss());
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
   
		return $this->getRenderedChat($channel);
	}
	
	private function getRenderedChat($channel = null) {
		if ($this->options->isOptionEnabled('restrict_to_wp_users') && !$this->usersDAO->isWpUserLogged()) {
			return sprintf("<div class='wcAccessDenied'>%s</div>", $this->options->getOption('message_error_4', 'Only logged in users are allowed to enter the chat'));
		}
		
		if (!$this->service->isChatOpen()) {
			return sprintf("<div class='wcChatClosed'>%s</div>", $this->options->getOption('message_error_5', 'The chat is closed now'));
		}
		
		if ($this->service->isChatChannelFull($channel)) {
			return sprintf("<div class='wcChatFull'>%s</div>", $this->options->getOption('message_error_6', 'The chat is full now. Try again later.'));
		}
		
		$channel = $this->getValidChannel($channel);
		$chatId = $this->getChatID();
		
		$this->userService->startUpMaintenance($channel);
		$this->bansService->startUpMaintenance();
		$this->messagesService->startUpMaintenance();
		
		$messages = $this->messagesDAO->getMessages($channel);
		$renderedMessages = '';
		$lastId = 0;
		foreach ($messages as $message) {
			// ommit non-admin messages:
			if ($message->admin == 1 && !$this->usersDAO->isWpUserAdminLogged()) {
				continue;
			}
				
			$renderedMessages .= $this->renderer->getRenderedMessage($message);
			
			if ($lastId < $message->id) {
				$lastId = $message->id;
			}
		}
		
		$jsOptions = array(
			'chatId' => $chatId,
			'channel' => $channel,
			'lastId' => $lastId,
			'lastActionId' => $this->actionsDAO->getLastActionId(),
			'baseDir' => $this->options->getBaseDir(),
			'apiEndpointBase' => $this->getEndpointBase(),
			'messagesRefreshTime' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'enableTitleNotifications' => $this->options->isOptionEnabled('enable_title_notifications'),
			'soundNotification' => $this->options->getEncodedOption('sound_notification'),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'siteURL' => get_site_url(),
			'messages' => array(
				'message_sending' => $this->options->getEncodedOption('message_sending', 'Sending ...'),
				'hint_message' => $this->options->getEncodedOption('hint_message')
			),
			'userSettings' => $this->userService->getUserSettings()
		);
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMainTemplate());
		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'messages' => $renderedMessages,
			'showMessageSubmitButton' => $this->options->isOptionEnabled('show_message_submit_button'),
			'messageSubmitButtonCaption' => $this->options->getEncodedOption('message_submit_button_caption', 'Send'),
			'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
			'showUsers' => $this->options->isOptionEnabled('show_users'),
			'showUsersCounter' => $this->options->isOptionEnabled('show_users_counter'),
			
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'enableChannelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0) > 0,
			
			'totalUsers' => $this->usersDAO->getAmountOfCurrentUsersOfChannel($channel),
			'showUserName' => $this->options->isOptionEnabled('show_user_name') && !$this->usersDAO->isWpUserLogged(),
			'currentUserName' => htmlentities($this->usersDAO->getUserName()),
			
			'inputControlsTopLocation' => $this->options->getEncodedOption('input_controls_location') == 'top',
			'inputControlsBottomLocation' => $this->options->getEncodedOption('input_controls_location') == '',
			
			'showCustomizationsPanel' => 
				$this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged() ||
				$this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0,
				
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged(),
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0,
				
			'messageCustomize' => $this->options->getEncodedOption('message_customize', 'Customize'),
			'messageName' => $this->options->getEncodedOption('message_name', 'Name'),
			'messageSave' => $this->options->getEncodedOption('message_save', 'Save'),
			'messageMuteSounds' => $this->options->getEncodedOption('message_mute_sounds', 'Mute sounds'),
			'messageTotalUsers' => $this->options->getEncodedOption('message_total_users', 'Total users'),
			
			'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
			'multilineSupport' => $this->options->isOptionEnabled('multiline_support'),
			'hintMessage' => $this->options->getEncodedOption('hint_message'),
			'messageMaxLength' => $this->options->getIntegerOption('message_max_length', 100),
			
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'showWindowTitle' => strlen($this->options->getEncodedOption('window_title', '')) > 0,
			
			'jsOptions' => json_encode($jsOptions),
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition()
		);
		
		$data = array_merge($data, $this->userService->getUserSettings());
		
		return $templater->render($data);
	}
	
	private function getChatID() {
		return 'wc'.md5(uniqid('', true));
	}
	
	private function getValidChannel($channel) {
		return $channel === null || $channel === '' ? 'global' : $channel;
	}
	
	private function getEndpointBase() {
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if ($this->options->getEncodedOption('ajax_engine', null) === 'lightweight') {
			$endpointBase = get_site_url().'/wp-content/plugins/wise-chat/src/endpoints/';
		}
		
		return $endpointBase;
	}
}