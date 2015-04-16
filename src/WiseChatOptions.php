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
	* @var string Plugin's base directory
	*/
	private $baseDir;
	
	/**
	* @var array Raw options array
	*/
	private $options;
	
	private function __construct() {
		$this->options = get_option(WiseChatSettings::OPTIONS_NAME);
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new WiseChatOptions();
		}
		
		return self::$instance;
	}
	
	public function getBaseDir() {
		return $this->baseDir;
	}
	
	public function setBaseDir($baseDir) {
		$this->baseDir = $baseDir;
	}
	
	/**
	* Returns value of the boolean option.
	*
	* @param string $property Boolean property
	* @param boolean $default Default value if the property is not found
	*
	* @return boolean
	*/
	public function isOptionEnabled($property, $default = false) {
		if (!array_key_exists($property, $this->options)) {
			return $default;
		} else if ($this->options[$property] == '1') {
			return true;
		}
		
		return false;
	}
	
	/**
	* Returns text value of the given option.
	*
	* @param string $property String property
	* @param string $default Default value if the property is not found
	*
	* @return string
	*/
	public function getOption($property, $default = '') {
		return array_key_exists($property, $this->options) ? $this->options[$property] : $default;
	}
	
	/**
	* Checks if the option is not empty.
	*
	* @param string $property String property
	*
	* @return boolean
	*/
	public function isOptionNotEmpty($property) {
		return array_key_exists($property, $this->options) && strlen($this->options[$property]) > 0;
	}
	
	/**
	* Returns HTML-encoded text value of the given option.
	*
	* @param string $property String property
	* @param string $default Default value if the property is not found
	*
	* @return string
	*/
	public function getEncodedOption($property, $default = '') {
		return htmlentities($this->getOption($property, $default), ENT_QUOTES, 'UTF-8');
	}
	
	/**
	* Returns integer value of the given option.
	*
	* @param string $property Integer value
	* @param string $default Default value if the property is not found
	*
	* @return integer
	*/
	public function getIntegerOption($property, $default = 0) {
		return intval(array_key_exists($property, $this->options) ? $this->options[$property] : $default);
	}
	
	/**
	* Replaces current options with given.
	*
	* @param array $options New options
	*
	* @return null
	*/
	public function replaceOptions($options) {
		$this->options = array_merge($this->options, $options);
	}
	
	/**
	* Deletes all options from WordPress DB.
	*
	* @return null
	*/
	public function dropAllOptions() {
		delete_option(WiseChatSettings::OPTIONS_NAME);
		delete_option(WiseChatUsersDAO::LAST_NAME_ID_OPTION);
	}
	
	/**
	* Dumps all options to stdout.
	*
	* @return null
	*/
	public function dump() {
		foreach ($this->options as $key => $value) {
			echo "$key=\"$value\"\n";
		}
	}
}