<?php
/*
	Plugin Name: Wise Chat
	Version: 1.5.0
	Plugin URI: http://kaine.pl/projects/wp-plugins/wise-chat-donate
	Description: Displays Ajax-powered Chat.
	Author: Marcin Ławrowski
	Author URI: http://kaine.pl
*/

// DAO imports:
require_once(dirname(__FILE__).'/src/dao/WiseChatUsersDAO.php');
require_once(dirname(__FILE__).'/src/dao/WiseChatMessagesDAO.php');
require_once(dirname(__FILE__).'/src/dao/WiseChatBansDAO.php');

require_once(dirname(__FILE__).'/src/WiseChatOptions.php');
require_once(dirname(__FILE__).'/src/WiseChatInstaller.php');
require_once(dirname(__FILE__).'/src/WiseChatSettings.php');
require_once(dirname(__FILE__).'/src/commands/WiseChatCommandsResolver.php');
require_once(dirname(__FILE__).'/src/WiseChat.php');
require_once(dirname(__FILE__).'/src/WiseChatEndpoints.php');
require_once(dirname(__FILE__).'/src/WiseChatWidget.php');
require_once(dirname(__FILE__).'/src/messages/WiseChatImagesDownloader.php');

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

// removing images downloaded by the chat:
$wiseChatImagesDownloader = new WiseChatImagesDownloader();
add_action('delete_attachment', array($wiseChatImagesDownloader, 'removeRelatedImages'));

// Endpoints fo AJAX requests:
$wiseChatEndpoints = new WiseChatEndpoints();
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", array($wiseChatEndpoints, 'messagesEndpoint'));
add_action("wp_ajax_wise_chat_messages_endpoint", array($wiseChatEndpoints, 'messagesEndpoint'));

add_action("wp_ajax_nopriv_wise_chat_message_endpoint", array($wiseChatEndpoints, 'messageEndpoint'));
add_action("wp_ajax_wise_chat_message_endpoint", array($wiseChatEndpoints, 'messageEndpoint'));

add_action("wp_ajax_nopriv_wise_chat_delete_message_endpoint", array($wiseChatEndpoints, 'messageDeleteEndpoint'));
add_action("wp_ajax_wise_chat_delete_message_endpoint", array($wiseChatEndpoints, 'messageDeleteEndpoint'));

add_action("wp_ajax_nopriv_wise_chat_actions_endpoint", array($wiseChatEndpoints, 'actionsEndpoint'));
add_action("wp_ajax_wise_chat_actions_endpoint", array($wiseChatEndpoints, 'actionsEndpoint'));

add_action("wp_ajax_nopriv_wise_chat_settings_endpoint", array($wiseChatEndpoints, 'settingsEndpoint'));
add_action("wp_ajax_wise_chat_settings_endpoint", array($wiseChatEndpoints, 'settingsEndpoint'));

// widget:
add_action('widgets_init', create_function('', 'return register_widget("WiseChatWidget");'));