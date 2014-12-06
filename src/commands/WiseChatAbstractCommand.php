<?php

/**
 * Wise Chat abstract command.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatAbstractCommand {
	protected $wiseChat;
	protected $channel;
	protected $arguments;
	
	public function __construct(WiseChat $wiseChat, $channel, $arguments) {
		$this->wiseChat = $wiseChat;
		$this->arguments = $arguments;
		$this->channel = $channel;
	}
	
	protected function addMessage($message) {
		$this->wiseChat->addMessage('System', $this->channel, $message, 1);
	}
}