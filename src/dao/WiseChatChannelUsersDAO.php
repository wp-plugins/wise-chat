<?php

/**
 * Wise Chat channels users DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatChannelUsersDAO {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Checks whether the given user name belongs to a different user (with different session ID) either active or inactive.
	*
	* @param string $user User name to check
	* @param string $sessionId Current session ID
	*
	* @return boolean
	*/
	public function isUserNameOccupied($user, $sessionId) {
		global $wpdb;
		
		$user = addslashes($user);
		$sessionId = addslashes($sessionId);
		$table = WiseChatInstaller::getChannelUsersTable();
		$results = $wpdb->get_results(
			sprintf('SELECT * FROM %s WHERE user = "%s" AND session_id != "%s" LIMIT 1;', $table, $user, $sessionId)
		);
		
		return is_array($results) && count($results) > 0 ? true : false;
	}
	
	/**
	* Returns channel-user association either active or inactive.
	*
	* @param string $user
	* @param string $channel
	*
	* @return object
	*/
	public function getByUserAndChannel($user, $channel) {
		global $wpdb;
		
		$user = addslashes($user);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getChannelUsersTable();
		$results = $wpdb->get_results(
			sprintf('SELECT * FROM %s WHERE user = "%s" AND channel = "%s" LIMIT 1;', $table, $user, $channel)
		);
		
		return is_array($results) && count($results) > 0 ? $results[0] : null;
	}
	
	/**
	* Returns active channel-user association either.
	*
	* @param string $user
	* @param string $channel
	*
	* @return object
	*/
	public function getByActiveUserAndChannel($user, $channel) {
		global $wpdb;
		
		$user = addslashes($user);
		$channel = addslashes($channel);
		$table = WiseChatInstaller::getChannelUsersTable();
		$results = $wpdb->get_results(
			sprintf('SELECT * FROM %s WHERE active = 1 AND user = "%s" AND channel = "%s" LIMIT 1;', $table, $user, $channel)
		);
		
		return is_array($results) && count($results) > 0 ? $results[0] : null;
	}
	
	/**
	* Returns channel-user associations for given session ID.
	*
	* @param string $sessionId
	*
	* @return array Array of objects
	*/
	public function getAllBySessionId($sessionId) {
		global $wpdb;
		
		$sessionId = addslashes($sessionId);
		$table = WiseChatInstaller::getChannelUsersTable();
		$results = $wpdb->get_results(
			sprintf('SELECT * FROM %s WHERE session_id = "%s";', $table, $sessionId)
		);
		
		return $results;
	}
	
	/**
	* Returns the amount of active channel-user associations for given session ID.
	*
	* @param string $sessionId
	*
	* @return integer
	*/
	public function getAmountOfActiveBySessionId($sessionId) {
		global $wpdb;
		
		$sessionId = addslashes($sessionId);
		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf('SELECT count(*) AS quantity FROM %s WHERE active = 1 AND session_id = "%s";', $table, $sessionId);
		
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}
		
		return 0;
	}
	
	/**
	* Returns the amount of active users of the given channel.
	*
	* @param string $channel Channel
	*
	* @return integer
	*/
	public function getAmountOfUsersInChannel($channel) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf('SELECT count(DISTINCT user) AS quantity FROM %s WHERE active = 1 AND channel = "%s";', $table, $channel);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}
		
		return 0;
	}
	
	/**
	* Returns active users of given channel.
	*
	* @param string $channel Channel
	*
	* @return array
	*/
	public function getUsersOfChannel($channel) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf('SELECT DISTINCT user AS name FROM %s WHERE active = 1 AND channel = "%s" ORDER BY user ASC LIMIT 1000;', $table, $channel);
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Returns array of channels and amount of active users that use each channel.
	*
	* @return array Array of objects (fields: channel, users)
	*/
	public function getUsersOfChannels() {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf('SELECT channel, count(DISTINCT user) as users FROM %s WHERE active = 1 GROUP BY channel ORDER BY channel ASC LIMIT 1000;', $table);
				
		return $wpdb->get_results($sql);
	}
	
	/**
	* Creates channel-user association.
	*
	* @param string $user
	* @param string $channel
	* @param string $sessionId
	* @param string $ip
	*
	* @return null
	*/
	public function create($user, $channel, $sessionId, $ip) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$wpdb->insert($table,
			array(
				'channel' => addslashes($channel),
				'user' => addslashes($user),
				'session_id' => addslashes($sessionId),
				'ip' => addslashes($ip),
				'active' => 1,
				'last_activity_time' => time()
			)
		);
	}
	
	/**
	* Updates channel-user association.
	*
	* @param string $user
	* @param string $channel
	* @param integer $lastActivityTime
	* @param boolean $active
	*
	* @return null
	*/
	public function updateLastActivityTimeAndActive($user, $channel, $lastActivityTime, $active) {
		global $wpdb;
	
		$table = WiseChatInstaller::getChannelUsersTable();
		$wpdb->update(
			$table,
			array(
				'last_activity_time' => intval($lastActivityTime),
				'active' => $active === true ? 1 : 0
			), 
			array(
				'channel' => addslashes($channel),
				'user' => addslashes($user)
			),
			'%d', '%s'
		);
	}
	
	/**
	* Updates channel-user association.
	*
	* @param string $user
	* @param string $newUser
	*
	* @return null
	*/
	public function updateUser($user, $newUser) {
		global $wpdb;
	
		$table = WiseChatInstaller::getChannelUsersTable();
		$wpdb->update(
			$table,
			array('user' => addslashes($newUser)), 
			array('user' => addslashes($user)),
			'%s', '%s'
		);
	}
	
	/**
	* Updates active status of all associations older than given amount of seconds.
	*
	* @param boolean $active
	* @param integer $time
	*
	* @return null
	*/
	public function updateActiveForOlderByLastActivityTime($active, $time) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$threshold = time() - $time;
			
		$wpdb->get_results(
			sprintf("UPDATE %s SET active = %d WHERE last_activity_time < %d;", $table, $active === true ? 1 : 0, $threshold)
		);
	}
	
	/**
	* Deletes all associations older than given amount of seconds.
	*
	* @param integer $time
	*
	* @return null
	*/
	public function deleteOlderByLastActivityTime($time) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelUsersTable();
		$threshold = time() - $time;
			
		$wpdb->get_results(
			sprintf("DELETE FROM %s WHERE last_activity_time < %s;", $table, $threshold)
		);
	}
}