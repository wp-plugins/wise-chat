<?php

/**
 * Wise Chat message rendering class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatRenderer {
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatTemplater
	*/
	private $templater;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		WiseChatContainer::load('WiseChatThemes');
		WiseChatContainer::load('rendering/WiseChatTemplater');




		$this->templater = new WiseChatTemplater($this->options->getPluginBaseDir());
	}

    /**
     * Returns rendered password authorization page.
     *
     * @param string|null $authorizationError
     *
     * @return string HTML source
     * @throws Exception
     */
	public function getRenderedPasswordAuthorization($authorizationError = null) {
		$this->templater->setTemplateFile(WiseChatThemes::getInstance()->getPasswordAuthorizationTemplate());
		
		$data = array(
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'messageChannelPasswordAuthorizationHint' => $this->options->getEncodedOption(
				'message_channel_password_authorization_hint', 'This channel is protected. Enter your password:'
			),
			'messageLogin' => $this->options->getEncodedOption('message_login', 'Log in'),
			'authorizationError' => $authorizationError
		);
		
		return $this->templater->render($data);
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
		$this->templater->setTemplateFile(WiseChatThemes::getInstance()->getAccessDeniedTemplate());
		
		$data = array(
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'errorMessage' => $errorMessage,
			'cssClass' => $cssClass,
		);
		
		return $this->templater->render($data);
	}

	/**
	 * Returns the form which allows to enter username.
	 *
	 * @param string|null $errorMessage
	 *
	 * @return string HTML source
	 * @throws Exception
	 */
	public function getRenderedUserNameForm($errorMessage = null) {
		$this->templater->setTemplateFile(WiseChatThemes::getInstance()->getUserNameFormTemplate());
		$data = array(
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'windowTitle' => $this->options->getEncodedOption('window_title', ''),
			'errorMessage' => $errorMessage,
			'messageLogin' => $this->options->getEncodedOption('message_login', 'Log in'),
			'messageEnterUserName' => $this->options->getEncodedOption('message_enter_user_name', 'Enter your username'),
		);

		return $this->templater->render($data);
	}
	
	/**
	* Returns rendered message.
	*
	* @param WiseChatMessage $message
	*
	* @return string HTML source
	*/
	public function getRenderedMessage($message) {
		$this->templater->setTemplateFile(WiseChatThemes::getInstance()->getMessageTemplate());

		$data = array(
			'baseDir' => $this->options->getBaseDir(),
			'messageId' => $message->getId(),
			'messageUser' => $message->getUserName(),
			'messageChatUserId' => $message->getUserId(),
			'isAuthorWpUser' => $this->usersDAO->getWpUserByID($message->getWordPressUserId()) !== null,
			'isAuthorCurrentUser' => $this->authentication->getUserIdOrNull() == $message->getUserId(),
			'showDeleteButton' => $this->options->isOptionEnabled('enable_message_actions') && $this->usersDAO->hasCurrentWpUserRight('delete_message'),
			'showBanButton' => $this->options->isOptionEnabled('enable_message_actions') && $this->usersDAO->hasCurrentWpUserRight('ban_user'),
			'messageTimeUTC' => gmdate('c', $message->getTime()),
			'renderedUserName' => $this->getRenderedUserName($message),
			'messageContent' => $this->getRenderedMessageContent($message),
			'isTextColorSet' => $this->options->isOptionEnabled('allow_change_text_color') &&
								$message->getUser() !== null &&
								strlen($message->getUser()->getDataProperty('textColor')) > 0,
			'textColor' => $message->getUser() !== null ? $message->getUser()->getDataProperty('textColor') : ''
		);
		
		return $this->templater->render($data);
	}
	
	/**
	* Returns rendered users list in the given channel.
	*
	* @param WiseChatChannel $channel
	*
	* @return string HTML source
	*/
	public function getRenderedUsersList($channel) {
		$channelUsers = $this->channelUsersDAO->getAllActiveByChannelId($channel->getId());
		$isCurrentUserPresent = false;
		$userId = $this->authentication->getUserIdOrNull();

		$usersList = array();
		foreach ($channelUsers as $channelUser) {
			if ($channelUser->getUser() == null) {
				continue;
			}

			// text color feature:
			$styles = '';
			if ($this->options->isOptionEnabled('allow_change_text_color')) {
				$textColor = $channelUser->getUser()->getDataProperty('textColor');
				if (strlen($textColor) > 0) {
					$styles = sprintf('style="color: %s"', $textColor);
				}
			}

			$currentUserClassName = '';
			if ($userId == $channelUser->getUserId()) {
				$isCurrentUserPresent = true;
				$currentUserClassName = 'wcCurrentUser';
			}

			$encodedName = htmlspecialchars($channelUser->getUser()->getName(), ENT_QUOTES, 'UTF-8');
			$usersList[] = sprintf(
				'<span class="wcUserInChannel %s" %s>%s</span>', $currentUserClassName, $styles, $encodedName
			);
		}
		
		if (!$isCurrentUserPresent && $userId !== null) {
			array_unshift(
				$usersList, sprintf('<span class="wcCurrentUser">%s</span>', $this->authentication->getUserNameOrEmptyString())
			);
		}
		
		return implode('<br />', $usersList);
	}
	
	/**
	* Returns rendered user name for given message.
	*
	* @param WiseChatMessage $message
	*
	* @return string HTML source
	*/
	public function getRenderedUserName($message) {
		$formattedUserName = $message->getUserName();
		
		if ($this->options->isOptionEnabled('link_wp_user_name')) {
			$linkUserNameTemplate = $this->options->getOption('link_user_name_template', null);
			$wpUser = $message->getWordPressUserId() != null ? $this->usersDAO->getWpUserByID($message->getWordPressUserId()) : null;
			
			$userNameLink = null;
			if ($linkUserNameTemplate != null) {
				$variables = array(
					'id' => $wpUser !== null ? $wpUser->ID : '',
					'username' => $wpUser !== null ? $wpUser->user_login : $message->getUserName(),
					'displayname' => $wpUser !== null ? $wpUser->display_name : $message->getUserName()
				);
				
				$userNameLink = $this->getTemplatedString($variables, $linkUserNameTemplate);
			} else if ($wpUser !== null) {
				$userNameLink = get_author_posts_url($wpUser->ID, $wpUser->display_name);
			}
			
			if ($userNameLink != null) {
				$styles = '';
				$messageUser = $message->getUser();
				if (
					$this->options->isOptionEnabled('allow_change_text_color') &&
					$messageUser !== null &&
					strlen($messageUser->getDataProperty('textColor')) > 0
				) {
					$styles = sprintf('style="color: %s"', $messageUser->getDataProperty('textColor'));
				}

				$formattedUserName = sprintf(
					"<a href='%s' target='_blank' rel='nofollow' %s>%s</a>", $userNameLink, $styles, $formattedUserName
				);
			}
		}
		
		return $formattedUserName;
	}
	
	/**
	* Returns rendered channel statistics.
	*
	* @param WiseChatChannel $channel
	*
	* @return string HTML source
	*/
	public function getRenderedChannelStats($channel) {
		if ($channel === null) {
			return 'ERROR: channel does not exist';
		}

		$variables = array(
			'channel' => $channel->getName(),
			'messages' => $this->messagesService->getNumberByChannelName($channel->getName()),
			'users' => $this->channelUsersDAO->getAmountOfUsersInChannel($channel->getId())
		);
	
		return $this->getTemplatedString($variables, $this->options->getOption('template', 'ERROR: TEMPLATE NOT SPECIFIED'));
	}
	
	/**
	* Returns rendered message content.
	*
	* @param WiseChatMessage $message
	*
	* @return string HTML source
	*/
	private function getRenderedMessageContent($message) {
		$formattedMessage = htmlspecialchars($message->getText(), ENT_QUOTES, 'UTF-8');

        /** @var WiseChatLinksPostFilter $linksFilter */
        $linksFilter = WiseChatContainer::get('rendering/filters/post/WiseChatLinksPostFilter');
		$formattedMessage = $linksFilter->filter(
            $formattedMessage,
            $this->options->isOptionEnabled('allow_post_links')
        );

        /** @var WiseChatAttachmentsPostFilter $attachmentsFilter */
        $attachmentsFilter = WiseChatContainer::get('rendering/filters/post/WiseChatAttachmentsPostFilter');
		$formattedMessage = $attachmentsFilter->filter(
			$formattedMessage,
            $this->options->isOptionEnabled('enable_attachments_uploader'),
            $this->options->isOptionEnabled('allow_post_links')
		);

        /** @var WiseChatImagesPostFilter $imagesFilter */
        $imagesFilter = WiseChatContainer::get('rendering/filters/post/WiseChatImagesPostFilter');
        $formattedMessage = $imagesFilter->filter(
			$formattedMessage,
            $this->options->isOptionEnabled('allow_post_images'),
            $this->options->isOptionEnabled('allow_post_links')
		);

        /** @var WiseChatYouTubePostFilter $youTubeFilter */
        $youTubeFilter = WiseChatContainer::get('rendering/filters/post/WiseChatYouTubePostFilter');
		$formattedMessage = $youTubeFilter->filter(
			$formattedMessage,
            $this->options->isOptionEnabled('enable_youtube'),
            $this->options->isOptionEnabled('allow_post_links'),
			$this->options->getIntegerOption('youtube_width', 186),
            $this->options->getIntegerOption('youtube_height', 105)
		);
		
		if ($this->options->isOptionEnabled('enable_twitter_hashtags')) {
            /** @var WiseChatHashtagsPostFilter $hashTagsFilter */
            $hashTagsFilter = WiseChatContainer::get('rendering/filters/post/WiseChatHashtagsPostFilter');
			$formattedMessage = $hashTagsFilter->filter($formattedMessage);
		}
		
		if ($this->options->isOptionEnabled('emoticons_enabled', true)) {
            /** @var WiseChatEmoticonsFilter $emoticonsFilter */
            $emoticonsFilter = WiseChatContainer::get('rendering/filters/post/WiseChatEmoticonsFilter');
            $formattedMessage = $emoticonsFilter->filter($formattedMessage);
		}
		
		if ($this->options->isOptionEnabled('multiline_support')) {
			$formattedMessage = str_replace("\n", '<br />', $formattedMessage);
		}
		
		return $formattedMessage;
	}
	
	private function getTemplatedString($variables, $template) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", urlencode($value), $template);
		}
		
		return $template;
	}
}