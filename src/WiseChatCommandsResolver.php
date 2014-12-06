<?php

/**
 * Wise Chat commands resolver.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @project wise-chat
 */
class WiseChatCommandsResolver {
	private $wiseChat;
	
	public function __construct(WiseChat $wiseChat) {
		$this->wiseChat = $wiseChat;
	}

	public function resolve($user, $channel, $message) {
		if ($this->isCommand($message) && $this->isAdmin()) {
			$this->wiseChat->addMessage($user, $channel, $message, 1);
		
			$resolver = $this->getCommandResolver($channel, $message);
		
			return true;
		}
		
		return false;
	}
	
	private function isCommand($message) {
		return strlen($message) > 0 && strpos($message, '/') === 0;
	}
	
	private function isAdmin() {
		return current_user_can('manage_options');
	}
	
	private function getCommandResolver($channel, $message) {
		$splited = preg_split('/\s+/', trim(trim($message), '/'));
		
		$commandName = str_replace('/', '', ucfirst($splited[0]));
		$commandClassName = "WiseChat{$commandName}Command";
		$commandFile = dirname(__FILE__)."/commands/{$commandClassName}.php";
		if (file_exists($commandFile)) {
			require_once($commandFile);
			array_shift($splited);
			$command = new $commandClassName($this->wiseChat, $channel, $splited);
			$command->execute();
		} else {
			$this->wiseChat->addMessage('System', $channel, 'Command not found', 1);
		}
	}
}