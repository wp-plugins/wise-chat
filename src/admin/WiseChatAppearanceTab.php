<?php 

/**
 * Wise Chat admin appearance settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatAppearanceTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('background_color', 'Background Color <br />(messages window)', 'backgroundColorCallback'),
			array('background_color_input', 'Background Color <br />(new message input)', 'backgroundColorInputCallback'),
			array('text_color', 'Text Color', 'textColorCallback')
		);
	}
	
	public function getDefaultValues() {
		return array(
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => ''
		);
	}
	
	public function backgroundColorCallback()
	{
		printf(
			'<input type="text" id="background_color" name="'.WiseChatSettings::OPTIONS_NAME.'[background_color]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the messages window</p>',
			isset( $this->options['background_color'] ) ? esc_attr( $this->options['background_color']) : ''
		);	
	}
	
	public function backgroundColorInputCallback()
	{
		printf(
			'<input type="text" id="background_color_input" name="'.WiseChatSettings::OPTIONS_NAME.'[background_color_input]" value="%s" class="wc-color-picker" />
			<p class="description">Background color of the new message window</p>',
			isset( $this->options['background_color_input'] ) ? esc_attr( $this->options['background_color_input']) : ''
		);	
	}
	
	public function textColorCallback()
	{
		printf(
			'<input type="text" id="text_color" name="'.WiseChatSettings::OPTIONS_NAME.'[text_color]" value="%s" class="wc-color-picker" />
			<p class="description">Text color</p>',
			isset( $this->options['text_color'] ) ? esc_attr( $this->options['text_color']) : ''
		);	
	}
	
	public function sanitizeOptionValue($input) {
		$new_input = array();
		
		if (isset($input['background_color'])) {
			$new_input['background_color'] = sanitize_text_field($input['background_color']);
		}
		
		if (isset($input['background_color_input'])) {
			$new_input['background_color_input'] = sanitize_text_field($input['background_color_input']);
		}
		
		if (isset($input['text_color'])) {
			$new_input['text_color'] = sanitize_text_field($input['text_color']);
		}

		return $new_input;
	}
}