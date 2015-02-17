<?php


/**
 * Wise Chat class for accessing plugin options.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatOptions {
	
	/**
	* @var WiseChatOptions
	*/
	private static $instance;
	
	/**
	* @var array
	*/
	private $options;
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new WiseChatOptions();
		}
		
		return self::$instance;
	}
	
	/**
	* Returns boolean value of the given option.
	*
	* @param string $property
	* @param boolean $default
	*
	* @return boolean
	*/
	public function isOptionEnabled($property, $default = false) {
		if (!array_key_exists($property, $this->options)) {
			return $default;
		}
		
		if (!empty($this->options[$property]) && $this->options[$property] == '1') {
			return true;
		}
		
		return false;
	}
	
	/**
	* Returns text value of the given option.
	*
	* @param string $property
	* @param string $default
	*
	* @return string
	*/
	public function getOption($property, $default = '') {
		return isset($this->options[$property]) ? $this->options[$property] : $default;
	}
	
	/**
	* Returns encoded text value of the given option.
	*
	* @param string $property
	* @param string $default
	*
	* @return string
	*/
	public function getEncodedOption($property, $default = '') {
		return htmlentities($this->getOption($property, $default), ENT_QUOTES, 'UTF-8');
	}
	
	/**
	* Returns integer value of the given option.
	*
	* @param string $property
	* @param string $default
	*
	* @return string
	*/
	public function getIntegerOption($property, $default = 0) {
		return intval(isset($this->options[$property]) ? $this->options[$property] : $default);
	}
	
	/**
	* Replaces current options with given.
	*
	* @param array $options
	*
	* @return null
	*/
	public function replaceOptions($options) {
		$this->options = array_merge($this->options, $options);
	}
	
	/**
	* Deletes all options.
	*
	* @return null
	*/
	public function dropAllOptions() {
		delete_option(WiseChatSettings::OPTIONS_NAME);
		delete_option(WiseChatUsersDAO::LAST_NAME_ID_OPTION);
	}
	
	public function dump() {
		foreach ($this->options as $key => $value) {
			echo "$key=\"$value\"\n";
		}
	}
	
	private function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
	}
	
	
}