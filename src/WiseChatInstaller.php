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
					channel text NOT NULL, 
					text text NOT NULL, 
					ip text NOT NULL, 
					UNIQUE KEY id (id)
			) DEFAULT CHARSET=utf8;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
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
		
		// default options:
		$settings = new WiseChatSettings();
		$settings->setDefaultSettings();
	}
	
	public static function uninstall() {
		global $wpdb, $user_level, $sac_admin_user_level;
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}
		
		$tableName = self::getMessagesTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getBansTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		delete_option(WiseChatSettings::OPTIONS_NAME);
		delete_option(WiseChatUsersDAO::LAST_NAME_ID_OPTION);
	}
}