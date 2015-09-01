<?php

/**
 * Wise Chat attachments post-filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatAttachmentsPostFilter {
	const SHORTCODE_REGEXP = '/\[attachment id=&quot;(.+?)&quot; src=&quot;(.+?)&quot; name-org=&quot;(.+?)&quot;\]/i';
	
	/**
	* Detects all attachments in shortcode format and converts them into real hyperlinks or raw URLs
	*
	* @param string $text HTML-encoded string
	* @param boolean $attachmentsEnabled Whether to convert shortcodes into real hyperlinks
	*
	* @return string
	*/
	public static function filter($text, $attachmentsEnabled) {
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 2) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$shortCodeSrc = $matches[2][$key];
				$shortCodeNameOrg = $matches[3][$key];
				$linkBody = htmlentities(urldecode($shortCodeNameOrg), ENT_QUOTES, 'UTF-8', false);
				
				if ($attachmentsEnabled) {
					$linkTag = sprintf('<a href="%s" target="_blank" rel="nofollow">%s</a>', $shortCodeSrc, $linkBody);
					$text = self::strReplaceFirst($shortCode, $linkTag, $text);
				} else {
					$text = self::strReplaceFirst($shortCode, $linkBody, $text);
				}
			}
		}
		
		return $text;
	}
	
	/**
	* Detects all attachments in shortcode format and returns their IDs.
	*
	* @param string $text HTML-encoded string
	*
	* @return array
	*/
	public static function getAttachmentsIds($text) {
		$response = array();
		
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 3) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$response[] = $matches[1][$key];
			}
		}
		
		return $response;
	}
	
	private static function strReplaceFirst($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		
		if ($pos !== false) {
			return substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		
		return $haystack;
	}
}	