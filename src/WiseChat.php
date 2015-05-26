<?php

require_once(dirname(__FILE__).'/WiseChatThemes.php');
require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatActionsDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatBansDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatFiltersDAO.php');
require_once(dirname(__FILE__).'/dao/filters/WiseChatFilterChain.php');
require_once(dirname(__FILE__).'/rendering/WiseChatRenderer.php');
require_once(dirname(__FILE__).'/rendering/WiseChatCssRenderer.php');
require_once(dirname(__FILE__).'/rendering/WiseChatTemplater.php');
require_once(dirname(__FILE__).'/rendering/filters/WiseChatLinksPreFilter.php');
require_once(dirname(__FILE__).'/rendering/filters/WiseChatShortcodeConstructor.php');


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
	* @var WiseChatBansDAO
	*/
	private $bansDAO;
	
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
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->bansDAO = new WiseChatBansDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->renderer = new WiseChatRenderer();
		$this->cssRenderer = new WiseChatCssRenderer();
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
		
		$this->usersDAO->resetEventTracker('usersList', $channel);
		$this->bansDAO->deleteOldBans();
		
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
		
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if ($this->options->getEncodedOption('ajax_engine', null) === 'lightweight') {
			$endpointBase = get_site_url().'/wp-content/plugins/wise-chat/src/endpoints/';
		}
		
		$jsOptions = array(
			'chatId' => $chatId,
			'channel' => $channel,
			'lastId' => $lastId,
			'lastActionId' => $this->actionsDAO->getLastActionId(),
			'apiEndpointBase' => $endpointBase,
			'messagesRefreshTime' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
			'siteURL' => get_site_url(),
			'messages' => array(
				'message_sending' => $this->options->getEncodedOption('message_sending', 'Sending ...'),
				'hint_message' => $this->options->getEncodedOption('hint_message')
			)
		);
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMainTemplate());
		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'messages' => $messagesRendering,
			'showMessageSubmitButton' => $this->options->isOptionEnabled('show_message_submit_button'),
			'messageSubmitButtonCaption' => $this->options->getEncodedOption('message_submit_button_caption', 'Send'),
			'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
			'showUsers' => $this->options->isOptionEnabled('show_users'),
			'showUserName' => $this->options->isOptionEnabled('show_user_name') && !$this->usersDAO->isWpUserLogged(),
			'currentUserName' => htmlentities($this->usersDAO->getUserName()),
			
			'showCustomizationsPanel' => $this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged(),
			'messageCustomize' => $this->options->getEncodedOption('message_customize', 'Customize'),
			'messageName' => $this->options->getEncodedOption('message_name', 'Name'),
			'messageSave' => $this->options->getEncodedOption('message_save', 'Save'),
			
			'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
			'multilineSupport' => $this->options->isOptionEnabled('multiline_support'),
			'hintMessage' => $this->options->getEncodedOption('hint_message'),
			'messageMaxLength' => $this->options->getIntegerOption('message_max_length', 100),
			
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'showWindowTitle' => strlen($this->options->getEncodedOption('window_title', '')) > 0,
			
			'jsOptions' => json_encode($jsOptions),
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId)
		);
		
		return $templater->render($data);
	}
}