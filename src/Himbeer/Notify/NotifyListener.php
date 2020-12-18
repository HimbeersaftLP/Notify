<?php

declare(strict_types=1);

namespace Himbeer\Notify;

use pocketmine\event\Listener;

class NotifyListener implements Listener {
	private $plugin;
	private $name;
	private $message;
	private $variables;

	public function __construct(Main $plugin, string $name, array $message, array $variables) {
		$this->plugin = $plugin;
		$this->name = $name;
		$this->message = $message;
		$this->variables = $variables;
	}

	private static function getTypeName($var) {
		return is_object($var) ? get_class($var) : gettype($var);
	}

	public function exec($event) {
		$results = [];
		foreach ($this->variables as $name => $variable) {
			$actions = is_array($variable["action"]) ? $variable["action"] : [$variable["action"]];
			$result = $event;
			foreach ($actions as $action) {
				if (is_callable([$result, $action])) {
					$result = $result->$action();
				} else {
					$this->plugin->getLogger()->warning("Uncallable result (type: " . self::getTypeName($result) . ") encountered by listener {$this->name} when trying to execute action $action");
					return;
				}
			}
			if (!is_scalar($result) && $results !== null && !method_exists($result, '__toString')) {
				$this->plugin->getLogger()->warning("Non-string result (type: " . self::getTypeName($result) . ") encountered by listener {$this->name} when trying to execute action $action");
				return;
			}
			$results['$' . $name] = $result;
		}

		$this->plugin->getLogger()->debug("NotifyListener " . $this->name . " called.");

		$this->plugin->sendMessage($this->name, $this->message, $results);
	}
}