<?php

/**
 * Wise Chat filters chain
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatFilterChain {
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	public function filter($text) {
		$filtersDao = new WiseChatFiltersDAO();
		$chain = $filtersDao->getAll();
		
		foreach ($chain as $filter) {
			$type = $filter['type'];
			$replace = $filter['replace'];
			$replaceWith = $filter['with'];
			
			if ($type == 'text') {
				$text = str_replace($replace, $replaceWith, $text);
			} else {
				$matches = array();
				$replace = '/'.$replace.'/i';
				if (preg_match_all($replace, $text, $matches)) {
					foreach ($matches[0] as $value) {
						$text = self::strReplaceFirst($value, $replaceWith, $text);
					}
				}
			}
		}
		
		return $text;
	}
	
	private static function strReplaceFirst($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		
		if ($pos !== false) {
			return substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		
		return $haystack;
	}
}