<?php

/**
 * Wise Chat links pre-filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatLinksPreFilter {
	const URL_REGEXP = "/((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)+[a-zA-Z]{2,6}(\/[^ \?]*)?(\?[^\"'<> ]+)?/i";
	const URL_IMAGE_REGEXP = "/((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)+[a-zA-Z]{2,6}(\/[^ \?]*)?\.(jpg|jpeg|gif|bmp|png|tiff)(\?[^\"'<> ]+)?/i";
	const URL_PROTOCOLS_REGEXP = "/^(https|http|ftp)\:\/\//i";
	
	/**
	* @var WiseChatImagesDownloader
	*/
	private $imagesDownloader;
	
	/**
	* @var integer
	*/
	private $replacementOffset = 0;
	
	/**
	* Constructor
	*
	* @param WiseChatImagesDownloader $imagesDownloader
	*
	* @return WiseChatLinksPreFilter
	*/
	public function __construct($imagesDownloader) {
		$this->imagesDownloader = $imagesDownloader;
	}
	
	/**
	* Detects URLs in the text and converts them into shortcodes indicating either regular links or images.
	*
	* @param string $text HTML-encoded string
	* @param string $channel Chat channel
	* @param boolean $detectAndDownloadImages Whether to check and download images
	*
	* @return string
	*/
	public function filter($text, $channel, $detectAndDownloadImages) {
		$this->replacementOffset = 0;
		
		if (preg_match_all(self::URL_REGEXP, $text, $matches)) {
			if (count($matches) == 0) {
				return $text;
			}
			
			foreach ($matches[0] as $detectedURL) {
				$shortCode = null;
				$regularLink = false;
				
				if ($detectAndDownloadImages && preg_match(self::URL_IMAGE_REGEXP, $detectedURL)) {
					$imageUrl = $detectedURL;
					if (!preg_match(self::URL_PROTOCOLS_REGEXP, $detectedURL)) {
						$imageUrl = "http://".$detectedURL;
					}
				
					$result = $this->imagesDownloader->downloadImage($imageUrl, $channel);
					if ($result != null) {
						$shortCode = WiseChatShortcodeConstructor::getImageShortcode($result['id'], $result['image'], $result['image-th'], $detectedURL);
					} else {
						$regularLink = true;
					}
				} else {
					$regularLink = true;
				}
				
				if ($regularLink) {
					$shortCode = sprintf('[link src="%s"]', $detectedURL);
				}
				
				if ($shortCode !== null) {
					$text = $this->strReplaceFirst($detectedURL, $shortCode, $text);
				}
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
}