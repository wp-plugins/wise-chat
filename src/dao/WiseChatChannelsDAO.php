<?php

/**
 * Wise Chat channels DAO
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatChannelsDAO {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Returns channel by name.
	*
	* @param string $name
	*
	* @return object
	*/
	public function getByName($name) {
		global $wpdb;
		
		$name = addslashes($name);
		$table = WiseChatInstaller::getChannelsTable();
		$results = $wpdb->get_results(sprintf('SELECT * FROM %s WHERE name = "%s";', $table, $name));
		
		return is_array($results) && count($results) > 0 ? $results[0] : null;
	}
	
	/**
	* Returns all channels
	*
	* @return array
	*/
	public function getAll() {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelsTable();
		return $wpdb->get_results(sprintf('SELECT * FROM %s ORDER BY name;', $table));
	}
	
	/**
	* Creates a new channel.
	*
	* @param string $name
	* @param string $password
	*
	* @return null
	*/
	public function create($name, $password = null) {
		global $wpdb;
		
		$table = WiseChatInstaller::getChannelsTable();
		$wpdb->insert($table,
			array(
				'name' => addslashes($name),
				'password' => $password
			)
		);
	}
	
	/**
	* Updates channel's password.
	*
	* @param integer $channelId
	* @param string $password
	*
	* @return null
	*/
	public function updatePassword($channelId, $password) {
		global $wpdb;
	
		$table = WiseChatInstaller::getChannelsTable();
		$wpdb->update(
			$table,
			array('password' => $password), 
			array('id' => $channelId),
			'%s', '%d'
		);
	}
}