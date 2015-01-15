<?php 

/**
 * Wise Chat admin abstract tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
abstract class WiseChatAbstractTab {
	protected $options;
	
	public function __construct($options) {
		$this->options = $options;
	}
	
	/**
	* Shows the message. 
	*
	* @param string $message
	*
	* @return null
	*/
	protected function addMessage($message) {
		$_SESSION[WiseChatSettings::SESSION_MESSAGE_KEY] = $message;
	}
	
	/**
	* Returns an array of fields displayed on the tab.
	*
	* @return array
	*/
	public abstract function getFields();
	
	/**
	* Returns an array of default values of fields.
	*
	* @return null
	*/
	public abstract function getDefaultValues();
	
	/**
	* Filters values of fields.
	*
	* @param array $inputValue
	*
	* @return null
	*/
	public abstract function sanitizeOptionValue($inputValue);
}