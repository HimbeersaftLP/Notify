<?php

declare(strict_types=1);

namespace Himbeer\Notify;

use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {
	private const VALIDATION_SUCCESS = 0;
	private const VALIDATION_ERR_MISSING_EVENT = 1;
	private const VALIDATION_ERR_MISSING_MESSAGE = 2;
	private const VALIDATION_ERR_MISSING_ACTION = 3;
	private const VALIDATION_ERR_STRINGS = [
		self::VALIDATION_ERR_MISSING_EVENT => 'Property "event" is missing.',
		self::VALIDATION_ERR_MISSING_MESSAGE => 'Property "message" is missing.',
		self::VALIDATION_ERR_MISSING_ACTION => "One of the listener's variables does not have an action."
	];

	public function onEnable() {
		$this->saveDefaultConfig();
		$this->registerEventListeners();
	}

	private function validateEventListenerFromConfig($options) {
		if (!isset($options["event"])) return self::VALIDATION_ERR_MISSING_EVENT;
		if (!isset($options["message"])) return self::VALIDATION_ERR_MISSING_MESSAGE;
		if (isset($options["variables"]) && is_array($options["variables"])) {
			foreach ($options["variables"] as $v) {
				if (!isset($v["action"])) return self::VALIDATION_ERR_MISSING_ACTION;
			}
		}
		return self::VALIDATION_SUCCESS;
	}

	private function registerEventListeners() {
		$listeners = $this->getConfig()->get("eventListeners");

		foreach ($listeners as $name => $options) {
			if ($vErr = $this->validateEventListenerFromConfig($options) !== self::VALIDATION_SUCCESS) {
				$this->getLogger()->warning("Listener $name could not be registered, reason: " . self::VALIDATION_ERR_STRINGS[$vErr]);
			}

			$eventName = $options["event"];
			$message = $options["message"];
			$variables = $options["variables"] ?? [];

			$priority = isset($options["priority"]) ?
				EventPriority::fromString($options["priority"]) :
				EventPriority::MONITOR;

			$this->getServer()->getPluginManager()->registerEvent(
				$eventName,
				new NotifyListener($this, $name, $message, $variables),
				$priority,
				new MethodEventExecutor("exec"),
				$this
			);
		}
	}

	public function sendMessage($listenerName, $message, $variables) {
		if (!isset($message["type"])) {
			$this->getLogger()->error("Listener $listenerName: Missing message type. Must be one of: discordMessage, discordEmbed, chat");
			return;
		}
		switch ($message["type"]) {
			case "discordMessage":
				if (!isset($message["webhookUrl"]) || !isset($message["data"])) {
					$this->getLogger()->error("Listener $listenerName: discordMessage is missing one of: webhookUrl, data");
					return;
				}
				Discord::sendMessage($message["webhookUrl"], $message["data"], $variables);
				break;
			case "discordEmbed":
				if (!isset($message["webhookUrl"]) || !isset($message["data"])) {
					$this->getLogger()->error("Listener $listenerName: discordEmbed is missing one of: webhookUrl, data");
					return;
				}
				Discord::sendEmbed($message["webhookUrl"], $message["data"], $variables);
				break;
			case "chat":
				if (!isset($message["text"])) {
					$this->getLogger()->error("Listener $listenerName: chat is missing the text property");
					return;
				}
				$this->getServer()->broadcastMessage(strtr($message["text"], $variables));
				break;
			default:
				$this->getLogger()->error("Listener $listenerName: Invalid message type " . $message["type"] . ". Must be one of: discordMessage, discordEmbed, chat");
		}
	}
}
