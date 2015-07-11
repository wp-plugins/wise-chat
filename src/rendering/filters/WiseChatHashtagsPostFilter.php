<?php

/**
 * Wise Chat hashtags post-filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatHashtagsPostFilter {
	const REGEXP = "/(.)?#([^\s#]+)/i";
	/**
	* @var integer
	*/
	private $replacementOffset = 0;
	
	/**
	* Detects hashtags in the text and converts them into regular links.
	*
	* @param string $text HTML-encoded string
	*
	* @return string
	*/
	public function filter($text) {
		$this->replacementOffset = 0;
		
		$stripedText = $this->stripTagsContent($text);
		if (preg_match_all(self::REGEXP, $stripedText, $matches)) {
			if (count($matches) == 0) {
				return $text;
			}
			
			foreach ($matches[2] as $key => $detectedHashtag) {
				$previousChar = $matches[1][$key];
				if ($previousChar === '&') {
					continue;
				}
			
				$url = sprintf('https://twitter.com/hashtag/%s?src=hash', $detectedHashtag);
				$linkTag = sprintf('<a href="%s" target="_blank" rel="nofollow">%s</a>', $url, '#'.$detectedHashtag);
				
				$text = $this->strReplaceFirst('#'.$detectedHashtag, $linkTag, $text);
			}
		}
		
		return $text;
	}
	
	private function strReplaceFirst($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle, $this->replacementOffset);
		
		if ($pos !== false) {
			$this->replacementOffset = $pos + strlen($replace);
			return substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		
		return $haystack;
	}
	
	private function stripTagsContent($text, $tags = '', $invert = false) {
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
		$tags = array_unique($tags[1]);
		
		if	(is_array($tags) && count($tags) > 0) {
			if ($invert == false) {
				return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
			}
			else {
				return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
			}
		}
		else if($invert == false) {
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
		}
		
		return $text;
	} 
}