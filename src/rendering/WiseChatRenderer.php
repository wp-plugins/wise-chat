<?php

require_once(dirname(__FILE__).'/filters/WiseChatFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatLinksPostFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatAttachmentsPostFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatImagesPostFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatEmoticonsFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatHashtagsPostFilter.php');

/**
 * Wise Chat message rendering class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatRenderer {
	
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
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->channelUsersDAO = new WiseChatChannelUsersDAO();
	}
	
	/**
	* Returns rendered password authorization page.
	*
	* @return string HTML source
	*/
	public function getRenderedPasswordAuthorization($authorizationError = null) {
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getPasswordAuthorizationTemplate());
		
		$data = array(
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'showWindowTitle' => strlen($this->options->getEncodedOption('window_title', '')) > 0,
			'messageChannelPasswordAuthorizationHint' => $this->options->getEncodedOption(
				'message_channel_password_authorization_hint', 'This channel is protected. Enter your password:'
			),
			'messageLogin' => $this->options->getEncodedOption('message_login', 'Log in'),
			'authorizationError' => $authorizationError,
			'showAuthorizationError' => $authorizationError !== null
		);
		
		return $templater->render($data);
	}
	
	/**
	* Returns rendered access-denied page.
	*
	* @param object $errorMessage
	* @param object $cssClass
	*
	* @return string HTML source
	*/
	public function getRenderedAccessDenied($errorMessage, $cssClass) {
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getAccessDeniedTemplate());
		
		$data = array(
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'showWindowTitle' => strlen($this->options->getEncodedOption('window_title', '')) > 0,
			'errorMessage' => $errorMessage,
			'cssClass' => $cssClass,
		);
		
		return $templater->render($data);
	}
	
	/**
	* Returns rendered message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	public function getRenderedMessage($message) {
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMessageTemplate());
		
		$data = array(
			'baseDir' => $this->options->getBaseDir(),
			'messageId' => $message->id,
			'messageUser' => $message->user,
			'isAuthorWpUser' => $this->usersDAO->getWpUserByDisplayName($message->user) !== null,
			'isAuthorCurrentUser' => $message->user == $this->usersDAO->getUserName(),
			'showAdminActions' => $this->options->isOptionEnabled('enable_message_actions') && $this->usersDAO->isWpUserAdminLogged(),
			'messageTimeUTC' => gmdate('c', $message->time),
			'renderedUserName' => $this->getRenderedUserName($message),
			'messageContent' => $this->getRenderedMessageContent($message)
		);
		
		return $templater->render($data);
	}
	
	/**
	* Returns rendered users list.
	*
	* @param string $channel Given channel
	*
	* @return string HTML source
	*/
	public function getRenderedUsersList($channel) {
		$users = $this->channelUsersDAO->getUsersOfChannel($channel);
		$usersList = array();
		$isCurrentUserPresent = false;
		
		foreach ($users as $user) {
			$name = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
			if ($this->usersDAO->getUserName() == $user->name) {
				$name = sprintf('<span class="wcCurrentUser">%s</span>', $name);
				$isCurrentUserPresent = true;
			}
			
			$usersList[] = $name;
		}
		
		if (!$isCurrentUserPresent) {
			array_unshift($usersList, sprintf('<span class="wcCurrentUser">%s</span>', $this->usersDAO->getUserName()));
		}
		
		return implode('<br />', $usersList);
	}
	
	/**
	* Returns rendered user name for given message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	public function getRenderedUserName($message) {
		$formatedUserName = $message->user;
		
		if ($this->options->isOptionEnabled('link_wp_user_name')) {
			$linkUserNameTemplate = $this->options->getOption('link_user_name_template', null);
			$wpUser = $this->usersDAO->getWpUserByDisplayName($message->user);
			
			$userNameLink = null;
			if ($linkUserNameTemplate != null) {
				$variables = array(
					'id' => $wpUser !== null ? $wpUser->ID : '',
					'username' => $wpUser !== null ? $wpUser->user_login : $message->user,
					'displayname' => $wpUser !== null ? $wpUser->display_name : $message->user
				);
				
				$userNameLink = $this->getTemplatedString($variables, $linkUserNameTemplate);
			} else if ($wpUser !== null) {
				$userNameLink = get_author_posts_url($wpUser->ID, $message->user);
			}
			
			if ($userNameLink != null) {
				$formatedUserName = sprintf("<a href='%s' target='_blank' rel='nofollow'>%s</a>", $userNameLink, $formatedUserName);
			}
		}
		
		return $formatedUserName;
	}
	
	/**
	* Returns rendered channel statistics.
	*
	* @param string $channel
	* @param WiseChatOptions $options
	*
	* @return string HTML source
	*/
	public function getRenderedChannelStats($channel, $options) {
		$variables = array(
			'channel' => $channel,
			'messages' => $this->messagesDAO->getAmountByChannel($channel),
			'users' => $this->channelUsersDAO->getAmountOfUsersInChannel($channel)
		);
	
		return $this->getTemplatedString($variables, $options->getOption('template', 'ERROR: TEMPLATE NOT SPECIFIED'));
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
		
		$formatedMessageContent = WiseChatLinksPostFilter::filter($formatedMessageContent, $this->options->isOptionEnabled('allow_post_links'));
		$formatedMessageContent = WiseChatAttachmentsPostFilter::filter($formatedMessageContent, $this->options->isOptionEnabled('enable_attachments_uploader'));
		$formatedMessageContent = WiseChatImagesPostFilter::filter(
			$formatedMessageContent, $this->options->isOptionEnabled('allow_post_images'), $this->options->isOptionEnabled('allow_post_links')
		);
		
		if ($this->options->isOptionEnabled('enable_twitter_hashtags')) {
			$hashtagsPostFilter = new WiseChatHashtagsPostFilter();
			$formatedMessageContent = $hashtagsPostFilter->filter($formatedMessageContent);
		}
		
		if ($this->options->isOptionEnabled('emoticons_enabled', true)) {
			$formatedMessageContent = WiseChatEmoticonsFilter::filter($formatedMessageContent);
		}
		
		if ($this->options->isOptionEnabled('multiline_support')) {
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