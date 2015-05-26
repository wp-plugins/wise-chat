<?php

/**
 * Wise Chat themes support.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatThemes {
	private static $themes = array(
		'' => 'Default',
		'lightgray' => 'Light Gray',
		'colddark' => 'Cold Dark'
	);
	
	private static $themesSettings = array(
		'' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/default/message.tpl',
			'css' => '/themes/default/theme.css',
		),
		'colddark' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/colddark/message.tpl',
			'css' => '/themes/colddark/theme.css',
		),
		'lightgray' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/lightgray/message.tpl',
			'css' => '/themes/lightgray/theme.css',
		)
	);
	
	/**
	* @var WiseChatThemes
	*/
	private static $instance;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	private function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new WiseChatThemes();
		}
		
		return self::$instance;
	}
 
	public static function getAllThemes() {
		return self::$themes;
	}

	public function getMainTemplate() {
		return $this->getThemeProperty('mainTemplate');
	}
	
	public function getMessageTemplate() {
		return $this->getThemeProperty('messageTemplate');
	}
	
	public function getCss() {
		return $this->getThemeProperty('css');
	}
	
	private function getThemeProperty($property) {
		$theme = $this->options->getEncodedOption('theme', '');
		
		return self::$themesSettings[$theme][$property];
	}
}