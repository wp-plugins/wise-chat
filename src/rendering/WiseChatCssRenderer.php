<?php

/**
 * Wise Chat CSS styles renderer class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatCssRenderer {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var string
	*/
	private $containerId;
	
	/**
	* @var array
	*/
	private $definitions;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Returns CSS styles definition for the plugin.
	*
	* @param string $containerId ID of the chat HTML container
	*
	* @return string HTML source
	*/
	public function getCssDefinition($containerId) {
		$this->containerId = $containerId;
		$this->definitions = array();
		
		$this->addDefinition('.wcWpMessage', 'text_color_logged_user', 'color');
		$this->addDefinition('.wcWpMessage a', 'text_color_logged_user', 'color');
		$this->addDefinition('.wcMessages', 'background_color', 'background-color');
		$this->addDefinition('.wcInput', 'background_color_input', 'background-color');
		$this->addDefinition('.wcInput', 'text_color', 'color');
		$this->addDefinition('.wcMessages', 'text_color', 'color');
		$this->addDefinition('.wcCurrentUserName', 'text_color', 'color');
		$this->addLengthDefinition('', 'chat_width', 'width');
		$this->addLengthDefinition('.wcMessages', 'chat_height', 'height');
		
		return $this->getDefinitions();
	}
	
	/**
	* Adds single style definition.
	*
	* @param string $cssSelector
	* @param string $property
	* @param string $cssProperty
	*
	* @return null
	*/
	private function addDefinition($cssSelector, $property, $cssProperty) {
		if ($this->options->isOptionNotEmpty($property)) {
			$fullCssSelector = sprintf("#%s %s", $this->containerId, $cssSelector);
			$this->definitions[$fullCssSelector][] = sprintf("%s: %s;", $cssProperty, $this->options->getOption($property));
		}
	}
	
	/**
	* Adds single length style definition.
	*
	* @param string $cssSelector
	* @param string $lengthProperty
	* @param string $cssProperty
	*
	* @return null
	*/
	private function addLengthDefinition($cssSelector, $lengthProperty, $cssProperty) {
		if ($this->options->isOptionNotEmpty($lengthProperty)) {
			$fullCssSelector = sprintf("#%s %s", $this->containerId, $cssSelector);
			$value = $this->options->getOption($lengthProperty);
			if (preg_match('/^\d+$/', $value)) {
				$value .= 'px';
			}
			if (preg_match('/^\d+((px)|%)$/', $value)) {
				$this->definitions[$fullCssSelector][] = sprintf("%s: %s;", $cssProperty, $value);
			}
		}
	}
	
	/**
	* Returns rendered styles definition. 
	*
	* @return string HTML source
	*/
	private function getDefinitions() {
		$html = '';
		foreach ($this->definitions as $cssSelector => $stylesList) {
			$html .= "$cssSelector { ".implode(" ", $stylesList)." }\n";
		}
		
		return sprintf('<style type="text/css">%s</style>', $html);
	}
}