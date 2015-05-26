<?php

/**
 * Wise Chat actions DAO. Actions are commands sent from the chat server to each client.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatActionsDAO {
	const ACTIONS_LIMIT = 10;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	* @var string
	*/
	private $table;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->table = WiseChatInstaller::getActionsTable();
	}
	
	/**
	* Returns ID of the last action.
	*
	* @return integer
	*/
	public function getLastActionId() {
		global $wpdb;
		
		$actions = $wpdb->get_results("SELECT max(id) AS id FROM {$this->table};");
		if (is_array($actions) && count($actions) > 0) {
			$action = $actions[0];
			return $action->id;
		}
		
		return 0;
	}
	
	/**
	* Returns actions begining from the specified ID.
	*
	* @param integer $fromId Offset
	*
	* @return array
	*/
	public function getActions($fromId) {
		global $wpdb;
		
		$conditions = array();
		$conditions[] = "id > ".intval($fromId);
		$sql = sprintf("SELECT * FROM %s WHERE %s ORDER BY id ASC LIMIT %d", $this->table, implode(" AND ", $conditions), self::ACTIONS_LIMIT);
		
		return $wpdb->get_results($sql);
	}
	
	/**
	* Returns actions begining from specified ID. The result is JSON ready.
	* Some of the fields are hidden and command is decoded to array.
	*
	* @param integer $fromId Offset
	*
	* @return array
	*/
	public function getJsonReadyActions($fromId) {
		$actions = $this->getActions($fromId);
		foreach ($actions as $action) {
			unset($action->user);
			unset($action->time);
			$action->command = json_decode($action->command, true);
		}
		
		return $actions;
	}
	
	/**
	* Publishes an action in the queue.
	*
	* @param string $name Name of the action
	* @param array $data Additional data for the action
	* @param string $user Recipient
	*
	* @return boolean
	*/
	public function publishAction($name, $data, $user = null) {
		global $wpdb;
		
		$name = trim($name);
		if (strlen($name) === 0 || !is_array($data)) {
			return false;
		}
		
		$command = array(
			'name' => $name,
			'data' => $data
		);
		
		$wpdb->insert($this->table,
			array(
				'time' => time(),
				'user' => $user,
				'command' => json_encode($command)
			)
		);
		
		return true;
	}
}