<?php

/**
 * Wise Chat bans DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatBansDAO {
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Deletes bans that are out of date.
	*
	* @return null
	*/
	public function deleteOldBans() {
		global $wpdb;
		
		$time = time();
		$table = WiseChatInstaller::getBansTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE time < $time");
	}
	
	/**
	* Returns details of the first ban added on specific IP address.
	* Retruns null if no bans were detected.
	*
	* @param string $ip Given IP address
	*
	* @return array|null
	*/
	public function getBanByIp($ip) {
		global $wpdb;
		
		$ip = addslashes($ip);
		$table = WiseChatInstaller::getBansTable();
		$bans = $wpdb->get_results("SELECT * FROM {$table} WHERE ip = \"{$ip}\" LIMIT 1;");
		
		return is_array($bans) && count($bans) > 0 ? $bans[0] : null;
	}
	
	/**
	* Checks whether given IP is banned.
	*
	* @param string $ip Given IP address
	*
	* @return boolean
	*/
	public function isIpBanned($ip) {
		return $this->getBanByIp($ip) !== null;
	}
	
	/**
	* Returns all bans.
	*
	* @return array
	*/
	public function getAll() {
		global $wpdb;
		
		$table = WiseChatInstaller::getBansTable();
		$bans = $wpdb->get_results("SELECT * FROM {$table};");
		
		return $bans;
	}
	
	/**
	* Removes ban by IP address.
	*
	* @param string $ip Given IP address
	*
	* @return null
	*/
	public function deleteByIp($ip) {
		global $wpdb;
		
		$ip = addslashes($ip);
		$table = WiseChatInstaller::getBansTable();
		$wpdb->get_results("DELETE FROM {$table} WHERE ip = '{$ip}'");
	}
	
	/**
	* Creates and saves a new ban on IP address.
	*
	* @param string $ip Given IP address
	* @param integer $duration Duration of the ban (in seconds)
	*
	* @return null
	*/
	public function createAndSave($ip, $duration) {
		global $wpdb;
		
		$ip = addslashes($ip);
		$table = WiseChatInstaller::getBansTable();
		$currentBan = $wpdb->get_results("SELECT * FROM {$table} WHERE ip = \"{$ip}\";");
		
		if (is_array($currentBan) && count($currentBan) > 0) {
			return false;
		} else {
			$wpdb->insert($table,
				array(
					'created' => time(),
					'time' => time() + $duration,
					'ip' => $ip
				)
			);
			
			return true;
		}
	}
	
	/**
	* Converts duration string into amount of seconds. 
	* If the value cannot be determined the default value is returned. 
	*
	* @param string $durationString Eg. 1h, 2d, 7m
	* @param integer $defaultValue One hour
	*
	* @return integer
	*/
	public function getDurationFromString($durationString, $defaultValue = 3600) {
		$duration = $defaultValue;
		
		if (strlen($durationString) > 0) {
			if (preg_match('/\d+m/', $durationString)) {
				$duration = intval($durationString) * 60;
			}
			if (preg_match('/\d+h/', $durationString)) {
				$duration = intval($durationString) * 60 * 60;
			}
			if (preg_match('/\d+d/', $durationString)) {
				$duration = intval($durationString) * 60 * 60 * 24;
			}
			
			if ($duration === 0) {
				$duration = $defaultValue;
			}
		}
		
		return $duration;
	}
}