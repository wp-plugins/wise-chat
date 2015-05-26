<?php

require_once(dirname(__FILE__).'/WiseChat.php');

/**
 * Wise Chat widget.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
class WiseChatWidget extends WP_Widget {
	
	public function __construct() {
		$widgetOps = array('classname' => 'WiseChatWidget', 'description' => 'Displays Wise Chat window' );
		$this->WP_Widget('WiseChatWidget', 'Wise Chat Window', $widgetOps);
	}
 
	public function form($instance) {
		$instance = wp_parse_args((array) $instance, array('channel' => ''));
		
		$channel = $instance['channel'];
		?>
			<p>
				<label for="<?php echo $this->get_field_id('channel'); ?>">
					Channel: <input class="widefat" id="<?php echo $this->get_field_id('channel'); ?>" 
								name="<?php echo $this->get_field_name('channel'); ?>" 
								type="text" value="<?php echo attribute_escape($channel); ?>" />
				</label>
			</p>
		<?php
	}
 
	public function update($newInstance, $oldInstance) {
		$instance = $oldInstance;
		$instance['channel'] = $newInstance['channel'];
		
		return $instance;
	}
	
	public function widget($args, $instance) {
		extract($args, EXTR_SKIP);
	
		echo $before_widget;
		
		$channel = $instance['channel'];
	
		$wiseChat = new WiseChat();
		$wiseChat->render($channel);
	
		echo $after_widget;
	}
}