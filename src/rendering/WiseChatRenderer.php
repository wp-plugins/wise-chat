<?php

require_once(dirname(__FILE__).'/filters/WiseChatFilter.php');
require_once(dirname(__FILE__).'/filters/WiseChatLinksPostFilter.php');
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
	* Returns rendered user name for given message.
	*
	* @param object $message Message details
	*
	* @return string HTML source
	*/
	private function getRenderedUserName($message) {
		$formatedUserName = $message->user;
		
		if ($this->options->isOptionEnabled('link_wp_user_name')) {
			$linkUserNameTemplate = $this->options->getOption('link_user_name_template', null);
			$wpUser = $this->usersDAO->getWpUserByDisplayName($message->user);
			
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
		
		return $formatedUserName;
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