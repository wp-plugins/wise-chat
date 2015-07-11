<?php 

/**
 * Wise Chat admin general settings tab class.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatGeneralTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array(
				'restrict_to_wp_users', 'Only For Logged In Users', 'booleanFieldCallback', 'boolean',
				'Denies access for anonymous users, only logged in WP users are allowed to see the chat'
			),
			
			array('_section', 'Chat Opening Hours and Days'),
			array('enable_opening_control', 'Enable Opening Control', 'booleanFieldCallback', 'boolean', 'Allows to specify when the chat is available for users.'),
			array('opening_days', 'Opening Days', 'checkboxesCallback', 'multivalues', 'Select chat opening days.', self::getOpeningDaysValues()),
			array('opening_hours', 'Opening Hours', 'openingHoursCallback', 'multivalues', 'Specify chat opening hours (HH:MM format)'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'restrict_to_wp_users' => 0,
			'enable_opening_control' => 0,
			'opening_days' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
			'opening_hours' => array('opening' => '8:00', 'openingMode' => 'AM', 'closing' => '4:00', 'closingMode' => 'PM')
		);
	}
	
	public function getParentFields() {
		return array(
			'opening_days' => 'enable_opening_control',
			'opening_hours' => 'enable_opening_control'
		);
	}
	
	public function openingHoursCallback($args) {
		$id = 'opening_hours';
		$hint = $args['hint'];
		
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$values = $this->options->getOption($id, $defaultValue);
		$parentId = $this->getFieldParent($id);
		$disabledAttribute = $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? 'disabled="1"' : '';
		
		$modes = array('AM', 'PM', '24h');
		$openingModesSelect = sprintf(
			'<select name="%s[%s][openingMode]" %s data-parent-field="%s">', 
			WiseChatSettings::OPTIONS_NAME, $id,
			$disabledAttribute, $parentId != null ? $parentId : ''
		);
		$closingModesSelect = sprintf(
			'<select name="%s[%s][closingMode]" %s data-parent-field="%s">', 
			WiseChatSettings::OPTIONS_NAME, $id,
			$disabledAttribute, $parentId != null ? $parentId : ''
		);
		foreach ($modes as $mode) {
			$openingModesSelect .= sprintf(
				'<option value="%s" %s>%s</option>', 
				$mode, array_key_exists('openingMode', $values) && $values['openingMode'] == $mode ? 'selected="1"' : '', $mode
			);
			$closingModesSelect .= sprintf(
				'<option value="%s" %s>%s</option>', 
				$mode, array_key_exists('closingMode', $values) && $values['closingMode'] == $mode ? 'selected="1"' : '', $mode
			);
		}
		$openingModesSelect .= '</select>';
		$closingModesSelect .= '</select>';
		
		print(
			sprintf(
				'From: <input type="text" value="%s" placeholder="HH:MM" id="openingHour" name="%s[%s][opening]" pattern="\d{1,2}:\d{2}"
						%s data-parent-field="%s" style="max-width: 90px;" />'.$openingModesSelect,
				array_key_exists('opening', $values) ? $values['opening'] : '',
				WiseChatSettings::OPTIONS_NAME, $id,
				$disabledAttribute,
				$parentId != null ? $parentId : ''
			).
			sprintf(
				'&nbsp;&nbsp; To: <input type="text" value="%s" placeholder="HH:MM" id="closingHour" name="%s[%s][closing]" pattern="\d{1,2}:\d{2}"
						%s data-parent-field="%s" style="max-width: 90px;" />'.$closingModesSelect,
				array_key_exists('closing', $values) ? $values['closing'] : '',
				WiseChatSettings::OPTIONS_NAME, $id,
				$disabledAttribute,
				$parentId != null ? $parentId : ''
			).
			sprintf('<p class="description">%s</p>', $hint)
		);
	}
	
	public static function getOpeningDaysValues() {
		return array(
			'Monday' => 'Monday', 
			'Tuesday' => 'Tuesday', 
			'Wednesday' => 'Wednesday', 
			'Thursday' => 'Thursday', 
			'Friday' => 'Friday', 
			'Saturday' => 'Saturday',
			'Sunday' => 'Sunday'
		);
	}
}