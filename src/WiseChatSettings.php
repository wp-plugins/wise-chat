<?php

require_once(dirname(__FILE__).'/admin/WiseChatAbstractTab.php');

/**
 * Wise Chat admin settings page.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatSettings {
	const OPTIONS_GROUP = 'wise_chat_options_group';
	const OPTIONS_NAME = 'wise_chat_options_name';
	const MENU_SLUG = 'wise-chat-admin';
	
	const PAGE_TITLE = 'Settings Admin';
	const MENU_TITLE = 'Wise Chat Settings';
	const SESSION_MESSAGE_KEY = 'wc_plugin_data_messages_update';
	
	/**
	* @var array Tabs definition
	*/
	private $tabs = array('wise-chat-general' => 'General', 'wise-chat-appearance' => 'Appearance');
	
	public function initialize() {
		add_action('admin_menu', array($this, 'addAdminMenu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
		add_action('admin_init', array($this, 'pageInit'));
	}
	
	public function addAdminMenu() {
		add_options_page(self::PAGE_TITLE, self::MENU_TITLE, 'manage_options', self::MENU_SLUG, array($this, 'renderAdminPage'));
		$this->handleActions();
	}
	
	public function enqueueScripts() {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker-script', plugins_url('../js/wise_chat_admin.js', __FILE__), array('wp-color-picker'), false, true);
	}
	
	public function pageInit() {
		register_setting(self::OPTIONS_GROUP, self::OPTIONS_NAME, array($this, 'getSanitizedFormValues'));
		
		foreach ($this->tabs as $key => $caption) {
			$sectionKey = "section_{$key}";
			$tabObject = $this->getTabObject($key);
			add_settings_section($sectionKey, "{$caption} Settings", null, $key);
			
			$fields = $tabObject->getFields();
			foreach ($fields as $field) {
				add_settings_field($field[0], $field[1], array($tabObject, $field[2]), $key, $sectionKey);
			}
		}
	}
	
	/**
	* Sets the default values of all configuration fields.
	* It should be used right after the installation of the plugin.
	*
	* @return null
	*/
	public function setDefaultSettings() {
		$options = get_option(self::OPTIONS_NAME, array());
		
		foreach ($this->tabs as $key => $caption) {
			$tabObject = $this->getTabObject($key);
			foreach ($tabObject->getDefaultValues() as $key => $value) {
				if (!array_key_exists($key, $options)) {
					$options[$key] = $value;
				}
			}
		}
		update_option(self::OPTIONS_NAME, $options);
	}

	public function renderAdminPage() {
		?>
			<div class="wrap">
				<h2><?php echo self::MENU_TITLE ?></h2>
				<?php $this->renderTabs(); ?>
				<form method="post" action="options.php">
					<?php settings_fields(self::OPTIONS_GROUP); ?>
					<?php
						$isFirstContainer = true;
						foreach ($this->tabs as $tabKey => $tabCaption) {
							$hideContainer = $isFirstContainer ? '' : 'display:none';
							echo "<div id='{$tabKey}Container' class='wiseChatTabContainer' style='{$hideContainer}'>";
							do_settings_sections($tabKey);
							echo "</div>";
							$isFirstContainer = false;
						}
					?>
					<?php submit_button(); ?>
				</form>
				
				<script type="text/javascript">
					jQuery(window).load(function() {
						jQuery('h2.wiseChatTabs a').click(function() {
							jQuery('.wiseChatTabContainer').hide();
							jQuery('#' + jQuery(this).attr('id') + 'Container').show();
							jQuery('h2.wiseChatTabs a').removeClass('nav-tab-active');
							jQuery(this).addClass('nav-tab-active');
						});
					});
				</script>
			</div>
		<?php
	}
	
	private function renderTabs() {
		echo '<h2 class="nav-tab-wrapper wiseChatTabs">';
		$isFirstTab = true;
		foreach ($this->tabs as $key => $caption) {
			$isActive = $isFirstTab ? 'nav-tab-active' : '';
			echo '<a id="'.$key.'" class="nav-tab '.$isActive.'" href="javascript://">'.$caption.'</a>';
			$isFirstTab = false;
		}
		echo '</h2>';
	}
	
	/**
	* Detects actions passed in parameters and delegates to an action method.
	*
	* @return null
	*/
	public function handleActions() {
		global $wpdb;
		
		if (isset($_GET['wc_action'])) {
			foreach ($this->tabs as $tabKey => $tabCaption) {
				$tabObject = $this->getTabObject($tabKey);
				$actionMethod = $_GET['wc_action'].'Action';
				if (method_exists($tabObject, $actionMethod)) {
					$tabObject->$actionMethod();
				}
			}
			
			$redirURL = admin_url("options-general.php?page=".self::MENU_SLUG);
			echo '<script type="text/javascript">location.replace("' . $redirURL . '");</script>';
		} else {
			$this->showUpdatedMessage();
		}
	}
	
	/**
	* Filters form input using filters from each tab object.
	*
	* @param array $input A key-value list of form values
	*
	* @return array Filtered array
	*/
	public function getSanitizedFormValues($input) {
		$sanitized = array();
		foreach ($this->tabs as $tabKey => $tabCaption) {
			$sanitized = array_merge($sanitized, $this->getTabObject($tabKey)->sanitizeOptionValue($input));
		}
		
		return $sanitized;
	}
	
	
	/**
	* Returns an instance of the requested tab object.
	*
	* @param string $tabKey A key from $this->tabs array
	*
	* @return WiseChatAbstractTab
	*/
	private function getTabObject($tabKey) {
		static $cache = array();
		
		if (array_key_exists($tabKey, $cache)) {
			return $cache[$tabKey];
		}
		
		$tabKey = ucfirst(str_replace('wise-chat-', '', $tabKey));
		$tabClassName = "WiseChat{$tabKey}Tab";
		
		require_once(dirname(__FILE__)."/admin/{$tabClassName}.php");
		
		$tabObject = new $tabClassName(get_option(self::OPTIONS_NAME));
		$cache[$tabKey] = $tabObject;
		
		return $tabObject;
	}
	
	/**
	* Shows a message stored in session.
	*
	* @return null
	*/
	private function showUpdatedMessage() {
		if (isset($_SESSION[self::SESSION_MESSAGE_KEY])) {
			add_settings_error(md5($_SESSION[self::SESSION_MESSAGE_KEY]), esc_attr('settings_updated'), $_SESSION[self::SESSION_MESSAGE_KEY], 'updated');
			unset($_SESSION[self::SESSION_MESSAGE_KEY]);
		}
	}
}