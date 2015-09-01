<?php

/**
 * Wise Chat installer.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatInstaller {

	public static function getMessagesTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_messages';
	}
	
	public static function getBansTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_bans';
	}
	
	public static function getActionsTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_actions';
	}
	
	public static function getChannelUsersTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_channel_users';
	}
	
	public static function getChannelsTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_channels';
	}

	public static function install() {
		global $wpdb, $user_level, $sac_admin_user_level;
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}
		
		$tableName = self::getMessagesTable();
		$checkTable = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
		if ($checkTable != $tableName) {
			$sql = "CREATE TABLE ".$tableName." (
					id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					time bigint(11) DEFAULT '0' NOT NULL, 
					admin boolean not null default 0,
					user tinytext NOT NULL, 
					channel_user_id bigint(11),
					channel text NOT NULL, 
					text text NOT NULL, 
					ip text NOT NULL, 
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		// remove legacy messages:
		$wpdb->get_results('DELETE FROM '.$tableName.' WHERE text = "__user_ping";');
		
		$tableName = self::getBansTable();
		$checkTable = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
		if ($checkTable != $tableName) {
			$sql = "CREATE TABLE " . $tableName . " (
					id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					time bigint(11) DEFAULT '0' NOT NULL,
					created bigint(11) DEFAULT '0' NOT NULL,
					ip text NOT NULL, 
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		$tableName = self::getActionsTable();
		$checkTable = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
		if ($checkTable != $tableName) {
			$sql = "CREATE TABLE " . $tableName . " (
					id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					time bigint(11) DEFAULT '0' NOT NULL,
					user tinytext,
					command text NOT NULL,
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		$tableName = self::getChannelUsersTable();
		$checkTable = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
		if ($checkTable != $tableName) {
			$sql = "CREATE TABLE " . $tableName . " (
					id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					channel text NOT NULL,
					user text NOT NULL,
					session_id text NOT NULL,
					active boolean not null default 1,
					ip text NOT NULL,
					last_activity_time bigint(11) DEFAULT '0' NOT NULL,
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		$tableName = self::getChannelsTable();
		$checkTable = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
		if ($checkTable != $tableName) {
			$sql = "CREATE TABLE " . $tableName . " (
					id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name text NOT NULL,
					password text,
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		// default options:
		$settings = new WiseChatSettings();
		$settings->setDefaultSettings();
	}
	
	public static function uninstall() {
		global $wpdb, $user_level, $sac_admin_user_level;
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}
		
		// remove all messages and related images:
		require_once(dirname(__FILE__).'/dao/WiseChatMessagesDAO.php');
		$messagesDAO = new WiseChatMessagesDAO();
		$messagesDAO->deleteAll();
		
		$tableName = self::getMessagesTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getBansTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getActionsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelUsersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		WiseChatOptions::getInstance()->dropAllOptions();
	}
}