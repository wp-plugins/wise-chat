<?php

/**
 * Wise Chat core class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChat {
	const LAST_NAME_ID_OPTION = 'wise_chat_last_name_id';

	private $baseDir;
	private $options;
	
	public function __construct($baseDir) {
		$this->baseDir = $baseDir;
		$this->options = get_option('wise_chat_options_name');
	}
	
	public function initializeCore() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		
		$this->generateUserName();
		add_action('plugins_loaded', array($this, 'generateLoggedUserName'));
		
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
	
	public function getCurrentUserName() {
		$this->startSession();
		
		if (array_key_exists('wise_chat_user_name', $_SESSION)) {
			return $_SESSION['wise_chat_user_name'];
		} else {
			return null;
		}
	}
	
	public function getAjaxMessages() {
		$lastId = intval($this->getGetParam('lastId', 0));
		$channel = $this->getGetParam('channel');
		
		$response = array();
		$response['result'] = array();
		if (strlen($channel) > 0) {	
			$messages = $this->getMessages($channel, $lastId > 0 ? $lastId : null);
			foreach ($messages as $message) {
				// ommit non-admin messages:
				if ($message->admin == 1 && !$this->isWpUserAdminLogged()) {
					continue;
				}
				
				$messageToJson = array();
				$messageToJson['text'] = $this->getRenderedMessage($message);
				$messageToJson['id'] = $message->id;
				
				$response['result'][] = $messageToJson;
			}
		}
    
		echo json_encode($response);
		die();
	}
	
	public function handleAjaxMessage() {
		$_POST = stripslashes_deep($_POST);
    
		$response = array();
		$channel = $this->getPostParam('channel');
		$message = $this->getPostParam('message');
		
		$ban = $this->getBanByIp($_SERVER['REMOTE_ADDR']);
		if ($ban != null) {
			$response['error'] = 'You were banned from posting messages';
		} else {
			if (strlen($message) > 0 && strlen($channel) > 0) {
			
				$wiseChatCommandsResolver = new WiseChatCommandsResolver($this);
				$isCommandResolved = $wiseChatCommandsResolver->resolve($this->getCurrentUserName(), $channel, $message);
				if (!$isCommandResolved) {
					$this->addMessage($this->getCurrentUserName(), $channel, $message, false);
				}
				$response['result'] = 'OK';
			} else {
				$response['error'] = 'Missing required fields';
			}
		}
		
		echo json_encode($response);
		die();
	}
	
	public function addMessage($user, $channel, $message, $isAdmin = false) {
		global $wpdb;
		
		$badWordsFilter = $this->options['filter_bad_words'] == '1' && $isAdmin == 0;
		$table = WiseChatInstaller::getMessagesTable();
		$wpdb->insert($table,
			array(
				'time' => time(),
				'admin' => $isAdmin ? 1 : 0,
				'user' => $user,
				'text' => $badWordsFilter ? WiseChatFilter::filter($message) : $message,
				'channel' => $channel,
				'ip' => $_SERVER['REMOTE_ADDR']
			)
		);
	}
	
	private function getRenderedChat($channel = null) {
		$outString = '';

		if ($channel === null) {
			$channel = 'global';
		}
		$chatId = 'wc'.md5(uniqid('', true));
		
		$messages = $this->getMessages($channel);
		$messagesRendering = '';
		$lastId = 0;
		foreach ($messages as $message) {
			// ommit non-admin messages:
			if ($message->admin == 1 && !$this->isWpUserAdminLogged()) {
				continue;
			}
				
			$messagesRendering .= $this->getRenderedMessage($message);
			$messagesRendering .= "<br />";
			$lastId = $message->id;
		}
		
		$hintMessage = str_replace("'", '', $this->options['hint_message']);
		$messagesBgColor = !empty($this->options['background_color']) ? "background-color: ".$this->options['background_color'].";" : '';
		$inpuBgColor = !empty($this->options['background_color_input']) ? "background-color: ".$this->options['background_color_input'].";" : '';
		$textColor = !empty($this->options['text_color']) ? "color: ".$this->options['text_color'].";" : '';
		
		$outString .= "<div id='$chatId' class='wcContainer'>
				<div class='wcMessages' style='$messagesBgColor $textColor'>{$messagesRendering}</div>
				<input class='wcInput' type='text' maxlength='{$this->options['message_max_length']}' placeholder='$hintMessage'  style='$inpuBgColor $textColor' />
			</div>
		";
		
		$jsOptions = array(
			'chatId' => $chatId,
			'channel' => $channel,
			'lastId' => $lastId,
			'siteURL' => get_site_url()
		);
		$jsOptionsRender = json_encode($jsOptions);
		
		$outString .= "<script type='text/javascript'>jQuery(window).load(function() {  new WiseChatController({$jsOptionsRender}); }); </script>";
		
		return $outString;
	}
	
	private function getMessages($channel, $fromId = null) {
		global $wpdb;
		
		$limit = intval($this->options['messages_limit']);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "channel = '{$channel}'";
		if ($fromId !== null) {
			$fromId = intval($fromId);
			$conditions[] = "id > {$fromId}";
		}
		if (!$this->isWpUserAdminLogged()) {
			$conditions[] = "admin = 0";
		}
		
		$sql = "SELECT * FROM {$table} ".
				(count($conditions) > 0 ? ' WHERE '.implode(' AND ', $conditions) : '').
				" ORDER BY id DESC ".
				" LIMIT {$limit};";
		
		$messages = $wpdb->get_results($sql);
		
		$this->deleteBans();
		
		return array_reverse($messages, true);
	}
	
	private function startSession() {
		if (!isset($_SESSION)) {
			session_start();
		}
	}
	
	private function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}
	
	private function getGetParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}
	
	private function getRenderedMessage($message) {
		$addDate = '';
		if (date('Y-m-d', $message->time) != date('Y-m-d')) {
			$addDate = date('Y-m-d', $message->time).' ';
		}
		$formated = '<span class="wcMessageTime">'.$addDate.date('H:i', $message->time).'</span> ';
		
		$userName = $message->user;
		if ($userName == $this->getCurrentUserName()) {
			$userName = "<strong>{$userName}</strong>";
		}
		$formated .= '<span class="wcMessageUser">'.$userName.'</span>: ';
		
		$formated .= htmlspecialchars($message->text, ENT_QUOTES, 'UTF-8');
		
		return $formated;
	}
	
	private function generateUserName() {
		$this->startSession();

		if (!array_key_exists('wise_chat_user_name', $_SESSION)) {
			$lastNameId = intval(get_option(self::LAST_NAME_ID_OPTION, 1)) + 1;
			update_option(self::LAST_NAME_ID_OPTION, $lastNameId);
			$_SESSION['wise_chat_user_name'] = $this->options['user_name_prefix'].get_option(self::LAST_NAME_ID_OPTION);
		}
	}
	
	public function generateLoggedUserName() {
		if (is_user_logged_in()) {
			$currentUser = wp_get_current_user();
			$displayName = $currentUser->display_name;
			if (strlen($displayName) > 0) {
				if (!array_key_exists('wise_chat_user_name_auto', $_SESSION)) {
					$_SESSION['wise_chat_user_name_auto'] = $_SESSION['wise_chat_user_name'];
				}
				$_SESSION['wise_chat_user_name'] = $displayName;
			}
		} else {
			if (array_key_exists('wise_chat_user_name_auto', $_SESSION)) {
				$_SESSION['wise_chat_user_name'] = $_SESSION['wise_chat_user_name_auto'];
			}
		}
	}
	
	private function isWpUserAdminLogged() {
		return current_user_can('manage_options');
	}
	
	private function deleteBans() {
		global $wpdb;
		
		$time = time();
		$table = WiseChatInstaller::getBansTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE time < $time");
	}
	
	private function getBanByIp($ip) {
		global $wpdb;
		
		$this->deleteBans();
		
		$ip = addslashes($ip);
		$table = WiseChatInstaller::getBansTable();
		$messages = $wpdb->get_results("SELECT * FROM {$table} WHERE ip = \"{$ip}\" LIMIT 1;");
		
		return is_array($messages) && count($messages) > 0 ? $messages[0] : null;
	}
}