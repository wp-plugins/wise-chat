<?php 

/**
 * Wise Chat admin abstract tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
abstract class WiseChatAbstractTab {

	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	protected $usersDAO;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	protected $messagesDAO;
	
	/**
	* @var WiseChatActionsDAO
	*/
	protected $actionsDAO;
	
	/**
	* @var WiseChatFiltersDAO
	*/
	protected $filtersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	protected $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->bansDAO = new WiseChatBansDAO();
		$this->usersDAO = new WiseChatUsersDAO();
		$this->messagesDAO = new WiseChatMessagesDAO();
		$this->actionsDAO = new WiseChatActionsDAO();
		$this->filtersDAO = new WiseChatFiltersDAO();
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
	* Shows error message. 
	*
	* @param string $message
	*
	* @return null
	*/
	protected function addErrorMessage($message) {
		$_SESSION[WiseChatSettings::SESSION_MESSAGE_ERROR_KEY] = $message;
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
	public function sanitizeOptionValue($inputValue) {
		$newInputValue = array();
		
		foreach ($this->getFields() as $field) {
			$id = $field[0];
			$type = $field[3];
			$value = array_key_exists($id, $inputValue) ? $inputValue[$id] : '';
			
			switch ($type) {
				case 'boolean':
					$newInputValue[$id] = isset($inputValue[$id]) && $value == '1' ? 1 : 0;
					break;
				case 'integer':
					if (isset($inputValue[$id])) {
						$newInputValue[$id] = absint($value);
					}
					break;
				case 'string':
					if (isset($inputValue[$id])) {
						$newInputValue[$id] = sanitize_text_field($value);
					}
					break;
			}
		}
		
		return $newInputValue;
	}
	
	/**
	* Callback method for displaying plain text field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*
	* @return null
	*/
	public function stringFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
	
		printf(
			'<input type="text" id="%s" name="'.WiseChatSettings::OPTIONS_NAME.'[%s]" value="%s" /><p class="description">%s</p>',
			$id, $id,
			$this->options->getEncodedOption($id, $defaultValue),
			$hint
		);
	}
	
	/**
	* Callback method for displaying color selection text field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*
	* @return null
	*/
	public function colorFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
	
		printf(
			'<input type="text" id="%s" name="'.WiseChatSettings::OPTIONS_NAME.'[%s]" value="%s" class="wc-color-picker" /><p class="description">%s</p>',
			$id, $id,
			$this->options->getEncodedOption($id, $defaultValue),
			$hint
		);
	}
	
	/**
	* Callback method for displaying boolean field (checkbox) with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*
	* @return null
	*/
	public function booleanFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
	
		printf(
			'<input type="checkbox" id="%s" name="'.WiseChatSettings::OPTIONS_NAME.'[%s]" value="1" %s /><p class="description">%s</p>',
			$id, $id, $this->options->isOptionEnabled($id, $defaultValue == 1) ? ' checked="1" ' : '', $hint
		);
	}
	
	/**
	* Callback method for displaying select field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name, hint, options
	*
	* @return null
	*/
	public function selectCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$options = $args['options'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$value = $this->options->getEncodedOption($id, $defaultValue);
		
		$optionsHtml = '';
		foreach ($options as $name => $label) {
			$optionsHtml .= sprintf("<option value='%s'%s>%s</option>", $name, $name == $value ? ' selected="1"' : '', $label);
		}
		
		printf(
			'<select id="%s" name="'.WiseChatSettings::OPTIONS_NAME.'[%s]">%s</select><p class="description">%s</p>',
			$id, $id, $optionsHtml, $hint
		);
	}
}