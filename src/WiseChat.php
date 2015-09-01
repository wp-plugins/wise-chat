<?php

require_once(dirname(__FILE__).'/WiseChatThemes.php');
require_once(dirname(__FILE__).'/WiseChatCrypt.php');
require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatActionsDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatChannelsDAO.php');
require_once(dirname(__FILE__).'/dao/WiseChatChannelUsersDAO.php');
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
require_once(dirname(__FILE__).'/services/WiseChatAttachmentsService.php');

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
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
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
	
	/**
	* @var WiseChatAttachmentsService
	*/
	private $attachmentsService;
	
	/**
	* @var array
	*/
	private $shortCodeOptions;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->renderer = new WiseChatRenderer();
		$this->cssRenderer = new WiseChatCssRenderer();
		$this->bansService = new WiseChatBansService();
		$this->userService = new WiseChatUserService();
		$this->messagesService = new WiseChatMessagesService();
		$this->service = new WiseChatService();
		$this->attachmentsService = new WiseChatAttachmentsService();
		
		$this->userService->initMaintenance();
		$this->shortCodeOptions = array();
	}
	
	public function initializeCore() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		
		$this->usersDAO->generateUserName();
		add_action('after_setup_theme', array($this->userService, 'switchUser'));
		
		add_shortcode('wise-chat', array($this, 'renderShortcode'));
		add_shortcode('wise-chat-channel-stats', array($this, 'renderChannelStatsShortcode'));
	}
	
	public function enqueueScripts() {
		wp_enqueue_script('wise_chat_messages_history',  $this->options->getBaseDir().'js/utils/messages_history.js', array());
		wp_enqueue_script('wise_chat_messages',  $this->options->getBaseDir().'js/ui/messages.js', array());
		wp_enqueue_script('wise_chat_settings',  $this->options->getBaseDir().'js/ui/settings.js', array());
		wp_enqueue_script('wise_chat_maintenance_executor',  $this->options->getBaseDir().'js/maintenance/executor.js', array());
		wp_enqueue_script('wise_chat_core',  $this->options->getBaseDir().'js/wise_chat.js', array());
		wp_enqueue_style('wise_chat_core', $this->options->getBaseDir().'css/wise_chat.css');
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
		$this->shortCodeOptions = $atts;
   
		return $this->getRenderedChat($channel);
	}
	
	public function renderChannelStatsShortcode($atts) {
		$this->userService->startUpMaintenance($channel);
		$this->bansService->startUpMaintenance();
		$this->messagesService->startUpMaintenance($channel);
	
		if (!is_array($atts)) {
			$atts = array();
		}
		extract(shortcode_atts(array(
			'channel' => 'global'
		), $atts));
		
		$this->options->replaceOptions($atts);
		
		return $this->renderer->getRenderedChannelStats($channel, $this->options);
	}
	
	private function getRenderedChat($channel = null) {
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_4', 'Only logged in users are allowed to enter the chat'), 'wcAccessDenied'
			);
		}
		
		if (!$this->service->isChatOpen()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_5', 'The chat is closed now'), 'wcChatClosed'
			);
		}
		
		if ($this->service->isChatChannelFull($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_6', 'The chat is full now. Try again later.'), 'wcChatFull'
			);
		}
		
		if ($this->service->isChatChannelsLimitReached($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_10', 'You cannot enter the chat due to the limit of channels you can participate simultaneously.'), 'wcChatChannelLimitFull'
			);
		}
		
		if ($this->service->hasUserToBeAuthorizedInChannel($channel)) {
			if ($this->getPostParam('wcChannelAuthorization') !== null) {
				if (!$this->service->authorize($channel, $this->getPostParam('wcChannelPassword'))) {
					return $this->renderer->getRenderedPasswordAuthorization($this->options->getOption('message_error_9', 'Invalid password.'));
				}
			} else {
				return $this->renderer->getRenderedPasswordAuthorization();
			}
		}
		
		$channel = $this->getValidChannel($channel);
		$chatId = $this->service->getChatID();
		
		$this->userService->startUpMaintenance($channel);
		$this->bansService->startUpMaintenance();
		$this->messagesService->startUpMaintenance($channel);
		
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
		
		$lastActionId = $this->actionsDAO->getLastActionId();
		
		$jsOptions = array(
			'chatId' => $chatId,
			'channel' => $channel,
			'nowTime' => gmdate('c', time()),
			'lastId' => $lastId,
			'checksum' => base64_encode(WiseChatCrypt::encrypt(serialize($this->shortCodeOptions))),
			'lastActionId' => $lastActionId !== null ? $lastActionId : 0,
			'baseDir' => $this->options->getBaseDir(),
			'apiEndpointBase' => $this->getEndpointBase(),
			'messagesRefreshTime' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'enableTitleNotifications' => $this->options->isOptionEnabled('enable_title_notifications'),
			'soundNotification' => $this->options->getEncodedOption('sound_notification'),
			'messagesTimeMode' => $this->options->getEncodedOption('messages_time_mode'),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'siteURL' => get_site_url(),
			'messages' => array(
				'message_sending' => $this->options->getEncodedOption('message_sending', 'Sending ...'),
				'hint_message' => $this->options->getEncodedOption('hint_message'),
				'messageSecAgo' => $this->options->getEncodedOption('message_sec_ago', 'sec. ago'),
				'messageMinAgo' => $this->options->getEncodedOption('message_min_ago', 'min. ago'),
				'messageYesterday' => $this->options->getEncodedOption('message_yesterday', 'yesterday'),
				'messageUnsupportedTypeOfFile' => $this->options->getEncodedOption('message_error_7', 'Unsupported type of file.'),
				'messageSizeLimitError' => $this->options->getEncodedOption('message_error_8', 'The size of the file exceeds allowed limit.')
			),
			'userSettings' => $this->userService->getUserSettings(),
			'attachmentsValidFileFormats' => $this->attachmentsService->getAllowedFormats(),
			'attachmentsSizeLimit' => $this->attachmentsService->getSizeLimit()
		);
		
		$totalUsers = $this->channelUsersDAO->getAmountOfUsersInChannel($channel);
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMainTemplate());
		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'messages' => $renderedMessages,
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'showMessageSubmitButton' => $this->options->isOptionEnabled('show_message_submit_button'),
			'messageSubmitButtonCaption' => $this->options->getEncodedOption('message_submit_button_caption', 'Send'),
			'showUsersList' => $this->options->isOptionEnabled('show_users'),
			'usersList' => $this->options->isOptionEnabled('show_users') ? $this->renderer->getRenderedUsersList($channel) : '',
			'showUsersCounter' => $this->options->isOptionEnabled('show_users_counter'),
			
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'enableChannelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0) > 0,
			
			'totalUsers' => $totalUsers == 0 ? 1 : $totalUsers,
			'showUserName' => $this->options->isOptionEnabled('show_user_name'),
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
			'messagePictureUploadHint' => $this->options->getEncodedOption('message_picture_upload_hint', 'Upload a picture'),
			'messageAttachFileHint' => $this->options->getEncodedOption('message_attach_file_hint', 'Attach a file'),
			
			'enableAttachmentsPanel' => $this->options->isOptionEnabled('enable_images_uploader') || $this->options->isOptionEnabled('enable_attachments_uploader'),
			'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
			'enableAttachmentsUploader' => $this->options->isOptionEnabled('enable_attachments_uploader'),
			'attachmentsExtensionsList' => $this->attachmentsService->getAllowedExtensionsList(),
			
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
	
	private function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}
}