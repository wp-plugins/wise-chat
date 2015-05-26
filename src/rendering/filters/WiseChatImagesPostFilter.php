<?php

/**
 * Wise Chat images post-filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatImagesPostFilter {
	const SHORTCODE_REGEXP = '/\[img id=&quot;(\d+)&quot; src=&quot;(.+?)&quot; src-th=&quot;(.+?)&quot; src-org=&quot;(.+?)&quot;\]/i';
	const URL_PROTOCOLS_REGEXP = "/^(https|http|ftp)\:\/\//i";
	
	/**
	* Detects all images in shortcode format and converts them into real images or raw URLs
	*
	* @param string $text HTML-encoded string
	* @param boolean $imagesEnabled Whether to convert shortcodes into real images
	* @param boolean $linksEnabled Whether to convert shortcodes into real hyperlinks
	*
	* @return string
	*/
	public static function filter($text, $imagesEnabled, $linksEnabled = true) {
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 3) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$shortCodeSrc = $matches[2][$key];
				$shortCodeThumbnailSrc = $matches[3][$key];
				$shortCodeOrgSrc = $matches[4][$key];
			
				if ($imagesEnabled) {
					$imageTag = sprintf('<a href="%s" target="_blank" data-lightbox="wise_chat" rel="lightbox[wise_chat]"><img src="%s" class="wcImage" /></a>', $shortCodeSrc, $shortCodeThumbnailSrc);
					$text = self::strReplaceFirst($shortCode, $imageTag, $text);
				} else if ($linksEnabled) {
					if ($shortCodeOrgSrc == '_') {
						$text = self::strReplaceFirst($shortCode, '', $text);
					} else {
						$url = $shortCodeOrgSrc;
						if (!preg_match(self::URL_PROTOCOLS_REGEXP, $shortCodeOrgSrc)) {
							$url = "http://".$shortCodeOrgSrc;
						}
						$linkBody = htmlentities(urldecode($shortCodeOrgSrc), ENT_QUOTES, 'UTF-8', false);
						$linkTag = sprintf('<a href="%s" target="_blank" rel="nofollow">%s</a>', $url, $linkBody);
					
						$text = self::strReplaceFirst($shortCode, $linkTag, $text);
					}
				} else {
					$text = self::strReplaceFirst($shortCode, $shortCodeOrgSrc, $text);
				}
			}
		}
		
		return $text;
	}
	
	/**
	* Detects all images in shortcode format and returns their IDs.
	*
	* @param string $text HTML-encoded string
	*
	* @return array
	*/
	public static function getImageIds($text) {
		$response = array();
		
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 3) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$imageId = $matches[1][$key];
				$response[] = $imageId;
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