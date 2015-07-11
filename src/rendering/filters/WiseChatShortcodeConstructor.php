<?php

/**
 * Wise Chat shortcodes constructors.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatShortcodeConstructor {
	const IMAGE_SHORT_TAG = '[img id="%d" src="%s" src-th="%s" src-org="%s"]';
	
	/**
	* Constructs image shortcode.
	*
	* @param integer $attachmentId
	* @param string $imageSrc
	* @param string $imageThumbnailSrc
	* @param string $originalSrc
	*
	* @return string
	*/
	public static function getImageShortcode($attachmentId, $imageSrc, $imageThumbnailSrc, $originalSrc) {
		return sprintf(self::IMAGE_SHORT_TAG, $attachmentId, $imageSrc, $imageThumbnailSrc, $originalSrc);
	}
}	