<?php

/**
 * Wise Chat links filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatLinksFilter {
	const URL_REGEXP = "/((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)+[a-zA-Z]{2,6}(\/[^ \?]*)?(\?[^\"'<> ]+)?/i";
	const URL_PROTOCOLS_REGEXP = "/^(https|http|ftp)\:\/\//i";
	
	/**
	* Detects all URLs in the given text and replaces them with hyperlinks.
	*
	* @param string $text HTML-encoded string
	*
	* @return string
	*/
	public static function filter($text) {
		if (preg_match_all(self::URL_REGEXP, $text, $matches)) {
			if (count($matches) == 0) {
				return $text;
			}
			
			foreach ($matches[0] as $detectedURL) {
				$url = $detectedURL;
				if (!preg_match(self::URL_PROTOCOLS_REGEXP, $detectedURL)) {
					$url = "http://".$detectedURL;
				}
				
				$linkBody = htmlentities(urldecode($detectedURL), ENT_QUOTES, 'UTF-8', false);
				$text = str_replace($detectedURL, "<a href='".$url."' target='_blank' rel='nofollow'>".$linkBody."</a>", $text);
			}
		}
		
		return $text;
	}
}