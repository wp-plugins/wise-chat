<?php
/*
	Plugin Name: Wise Chat
	Version: 1.2.0
	Plugin URI: http://kaine.pl/projects/wp-plugins/wise-chat-donate
	Description: Displays Ajax-powered Chat.
	Author: Marcin Ławrowski
	Author URI: http://kaine.pl
*/

require_once(dirname(__FILE__).'/src/WiseChatInstaller.php');
require_once(dirname(__FILE__).'/src/WiseChatSettings.php');
require_once(dirname(__FILE__).'/src/WiseChatCommandsResolver.php');
require_once(dirname(__FILE__).'/src/WiseChatFilter.php');
require_once(dirname(__FILE__).'/src/WiseChat.php');
require_once(dirname(__FILE__).'/src/WiseChatEndpoints.php');
require_once(dirname(__FILE__).'/src/WiseChatWidget.php');

// installer part:
register_activation_hook(__FILE__, array('WiseChatInstaller', 'install'));
register_deactivation_hook(__FILE__, array('WiseChatInstaller', 'uninstall'));

// settings for admin:
if (is_admin()) {
	$settings = new WiseChatSettings();
	$settings->initialize();
}

// initialize core:
$wiseChat = new WiseChat(plugin_dir_url(__FILE__));
$wiseChat->initializeCore();

// chat rendering function:
function wise_chat($channel = null) {
	$wiseChat = new WiseChat(plugin_dir_url(__FILE__));
	$wiseChat->render($channel);
}

// Endpoints fo AJAX requests:
$wiseChatEndpoints = new WiseChatEndpoints();
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", array($wiseChatEndpoints, 'messagesEndpoint'));
add_action("wp_ajax_wise_chat_messages_endpoint", array($wiseChatEndpoints, 'messagesEndpoint'));
add_action("wp_ajax_nopriv_wise_chat_message_endpoint", array($wiseChatEndpoints, 'messageEndpoint'));
add_action("wp_ajax_wise_chat_message_endpoint", array($wiseChatEndpoints, 'messageEndpoint'));
add_action("wp_ajax_nopriv_wise_chat_settings_endpoint", array($wiseChatEndpoints, 'settingsEndpoint'));
add_action("wp_ajax_wise_chat_settings_endpoint", array($wiseChatEndpoints, 'settingsEndpoint'));

// widget:
add_action('widgets_init', create_function('', 'return register_widget("WiseChatWidget");'));