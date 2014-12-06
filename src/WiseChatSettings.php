<?php

/**
 * Wise Chat admin settings page.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatSettings {
	const OPTIONS_GROUP = 'wise_chat_options_group';
	const OPTIONS_NAME = 'wise_chat_options_name';
	const OPTIONS_PAGE = 'wise_chat_options_page';
	const OPTIONS_SECTION_BASE = 'wise_chat_options_section_base';
	
	private $options;

	public function __construct() {
		add_action('admin_menu', array($this, 'addPluginPage'));
		add_action('admin_init', array($this, 'pageInit'));
		add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
	}
	
	public function enqueueScripts() {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script( 'wp-color-picker-script', plugins_url('../js/wise_chat_admin.js', __FILE__), array('wp-color-picker'), false, true );
	}

	public function addPluginPage() {
		add_options_page('Settings Admin', 'Wise Chat Settings', 'manage_options', 'wise-chat-admin', array($this, 'renderAdminPage'));
		
		$this->handleActions();
	}

	public function renderAdminPage() {
		
		self::setDefaultOptions();
		$this->options = get_option(self::OPTIONS_NAME);
		
		?>
		
		
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Wise Chat Settings</h2>           
			<form method="post" action="options.php">
				<?php
					settings_fields(self::OPTIONS_GROUP);   
					do_settings_sections(self::OPTIONS_PAGE);
				?>
				<?php
					submit_button(); 
				?>
			</form>
		</div>
		<?php
	}
	
	public function handleActions() {
		global $wpdb;
		
		if (isset($_GET['wc_action'])) {
			$action = $_GET['wc_action'];
			
			if ($action == 'clear_messages') {
				$table = WiseChatInstaller::getMessagesTable();
				$wpdb->get_results("DELETE FROM {$table} WHERE 1 = 1;");
				$this->addUpdateMessage('All messages have been deleted');
			}
			
			$redirURL = admin_url("options-general.php?page=wise-chat-admin");
			echo '<script type="text/javascript">location.replace("' . $redirURL . '");</script>';
		} else {
			$this->showUpdatedMessage();
		}
	}

	public function pageInit() {        
		register_setting(self::OPTIONS_GROUP, self::OPTIONS_NAME, array($this, 'sanitize'));

		add_settings_section(self::OPTIONS_SECTION_BASE, 'Base Settings', null, self::OPTIONS_PAGE);

		add_settings_field('messages_limit', 'Messages Limit', array($this, 'messagesLimitCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('hint_message', 'Hint Message', array($this, 'hintMessageCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('message_max_length', 'Message Max Length', array($this, 'messageMaxLengthCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('user_name_prefix', 'User Name Prefix', array($this, 'userNamePrefixCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('filter_bad_words', 'Filter Bad Words', array($this, 'filterBadWordsCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		
		add_settings_field('admin_actions', 'Admin Actions', array($this, 'adminActionsCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('background_color', 'Background Color <br />(messages window)', array($this, 'backgroundColorCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('background_color_input', 'Background Color <br />(new message input)', array($this, 'backgroundColorInputCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
		add_settings_field('text_color', 'Text Color <br />', array($this, 'textColorCallback'), self::OPTIONS_PAGE, self::OPTIONS_SECTION_BASE);
	}
	
	public static function setDefaultOptions($forceReplace = false) {
		$options = get_option(self::OPTIONS_NAME);
		$defaultOptions = array(
			'hint_message' => 'Enter message here',
			'messages_limit' => 30,
			'message_max_length' => 400,
			'user_name_prefix' => 'Anonymous',
			'filter_bad_words' => 1,
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => ''
		);
		
		foreach ($defaultOptions as $key => $value) {
			if ($forceReplace == true || !array_key_exists($key, $options)) {
				$options[$key] = $value;
			}
		}
		update_option(self::OPTIONS_NAME, $options);
	}

	public function sanitize($input)
	{
		$new_input = array();
		if (isset($input['messages_limit'])) {
			$new_input['messages_limit'] = absint($input['messages_limit']);
		}
		
		if (isset($input['filter_bad_words']) && $input['filter_bad_words'] == '1') {
			$new_input['filter_bad_words'] = 1;
		} else {
			$new_input['filter_bad_words'] = 0;
		}
		
		if (isset($input['message_max_length'])) {
			$new_input['message_max_length'] = absint($input['message_max_length']);
		}

		if (isset($input['hint_message'])) {
			$new_input['hint_message'] = sanitize_text_field($input['hint_message']);
		}
		
		if (isset($input['user_name_prefix'])) {
			$new_input['user_name_prefix'] = sanitize_text_field($input['user_name_prefix']);
		}
		
		if (isset($input['background_color'])) {
			$new_input['background_color'] = sanitize_text_field($input['background_color']);
		}
		
		if (isset($input['background_color_input'])) {
			$new_input['background_color_input'] = sanitize_text_field($input['background_color_input']);
		}
		
		if (isset($input['text_color'])) {
			$new_input['text_color'] = sanitize_text_field($input['text_color']);
		}

		return $new_input;
	}

	public function messageMaxLengthCallback()
	{
		printf(
			'<input type="text" id="message_max_length" name="'.self::OPTIONS_NAME.'[message_max_length]" value="%s" />
			<p class="description">Maximum length of a message</p>',
			isset( $this->options['message_max_length'] ) ? esc_attr( $this->options['message_max_length']) : ''
		);
	}
	
	public function messagesLimitCallback()
	{
		printf(
			'<input type="text" id="messages_limit" name="'.self::OPTIONS_NAME.'[messages_limit]" value="%s" />
			<p class="description">The limit of messages loaded on start-up</p>',
			isset( $this->options['messages_limit'] ) ? esc_attr( $this->options['messages_limit']) : ''
		);
	}

	public function hintMessageCallback()
	{
		printf(
			'<input type="text" id="hint_message" name="'.self::OPTIONS_NAME.'[hint_message]" value="%s" />
			<p class="description">A hint message displayed in the input field</p>',
			isset( $this->options['hint_message'] ) ? esc_attr( $this->options['hint_message']) : ''
		);
	}
	
	public function userNamePrefixCallback()
	{
		printf(
			'<input type="text" id="user_name_prefix" name="'.self::OPTIONS_NAME.'[user_name_prefix]" value="%s" />
			<p class="description">User\'s name prefix</p>',
			isset( $this->options['user_name_prefix'] ) ? esc_attr( $this->options['user_name_prefix']) : ''
		);
	}
	
	public function filterBadWordsCallback()
	{
		printf(
			'<input type="checkbox" id="filter_bad_words" name="'.self::OPTIONS_NAME.'[filter_bad_words]" value="1" %s />
			<p class="description">Uses its own dictionary to filter bad words</p>',
			$this->options['filter_bad_words'] == '1' ? ' checked="1" ' : ''
		);	
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=wise-chat-admin&wc_action=clear_messages");
		
		printf(
			'<a class="button-secondary" href="%s" title="Clears all messages sent to any channel">Clear Messages</a>',
			wp_nonce_url($url)
		);
	}
	
	public function backgroundColorCallback()
	{
		printf(
			'<input type="text" id="background_color" name="'.self::OPTIONS_NAME.'[background_color]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the messages window</p>',
			isset( $this->options['background_color'] ) ? esc_attr( $this->options['background_color']) : ''
		);	
	}
	
	public function backgroundColorInputCallback()
	{
		printf(
			'<input type="text" id="background_color_input" name="'.self::OPTIONS_NAME.'[background_color_input]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the new message window</p>',
			isset( $this->options['background_color_input'] ) ? esc_attr( $this->options['background_color_input']) : ''
		);	
	}
	
	public function textColorCallback()
	{
		printf(
			'<input type="text" id="text_color" name="'.self::OPTIONS_NAME.'[text_color]" value="%s" class="wc-color-picker" />
			<p class="description">Text color</p>',
			isset( $this->options['text_color'] ) ? esc_attr( $this->options['text_color']) : ''
		);	
	}
	
	private function showUpdatedMessage() {
		$key = 'wc_plugin_data_messages_update';
		if (isset($_SESSION[$key])) {
			add_settings_error(md5($_SESSION[$key]), esc_attr('settings_updated'), $_SESSION[$key], 'updated');
			unset($_SESSION[$key]);
		}
	}
	
	private function addUpdateMessage($message) {
		$_SESSION['wc_plugin_data_messages_update'] = $message;
	}
}