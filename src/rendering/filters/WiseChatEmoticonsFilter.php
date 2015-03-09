<?php

/**
 * Wise Chat emoticons filter.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatEmoticonsFilter {
	const EMOTICONS_BASE_DIR = "gfx/emoticons";
	
	private static $emoticons = array(
		'zip-it', 'blush', 'angry', 'not-one-care', 'xd', 'please', 'cool', 'minishock', 
		'devil', 'silly', 'smile', 'devil-laugh', 'heart', 'not-guilty', 'hay', 
		'in-love', 'meow', 'tease', 'gift', 'kissy', 'sad', 'speechless', 'goatse', 
		'fools', 'why-thank-you', 'wink', 'angel', 'annoyed', 'flower', 'surprised', 
		'female', 'laugh', 'ill', 'total-shock', 'zzz', 'clock', 'oh', 'mail', 'crazy', 
		'cry', 'boring', 'geek'
	);
	
	private static $aliases = array(
		'smile' => array(':)', ':-)'),
		'laugh' => array(':D', ':-D', ':d', ':-d'),
		'sad' => array(':(', ':-('),
		'cry' => array(';(', ';-('),
		'kissy' => array(':*', ':-*'),
		'silly' => array(':P', ':-P', ':p', ':-p'),
		'crazy' => array(';P', ';-P', ';p', ';-p'),
		'angry' => array(':[', ':-['),
		'devil-laugh' => array(':&gt;', ':-&gt;'),
		'devil' => array(':]', ':-]'),
		'goatse' => array(':|', ':-|'),
	);
	
	/**
	* Detects emoticons and replaces them with a proper image tag.
	*
	* @param string $text HTML-encoded string
	*
	* @return string
	*/
	public static function filter($text) {
		$options = WiseChatOptions::getInstance();
		foreach (self::$emoticons as $emoticon) {
			$filePath = sprintf("%s%s/%s.png", $options->getBaseDir(), self::EMOTICONS_BASE_DIR, $emoticon);
			$imgTag = sprintf("<img src='%s' class='wcEmoticon' />", $filePath);
			
			$replaceArray = array(htmlentities(sprintf('<%s>', $emoticon)));
			if (array_key_exists($emoticon, self::$aliases)) {
				$replaceArray = array_merge($replaceArray, self::$aliases[$emoticon]);
			}
			
			$text = str_replace($replaceArray, $imgTag, $text);
		}
		
		return $text;
	}
}