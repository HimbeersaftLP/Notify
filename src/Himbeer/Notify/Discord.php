<?php

declare(strict_types=1);

namespace Himbeer\Notify;

use pocketmine\scheduler\BulkCurlTask;
use pocketmine\Server;
use pocketmine\utils\InternetException;

class Discord {
	/**
	 * @param string $url The URL to POST to
	 * @param string $body The (json) body data that should be sent
	 * @param callable $callback A callback that gets called with the result or null if the request fails
	 */
	private static function asyncHttpJsonPost(string $url, string $body, callable $callback) {
		Server::getInstance()->getAsyncPool()->submitTask(new class([[
			"page" => $url,
			"extraOpts" => [
				CURLOPT_HTTPHEADER => [
					"Content-Type: application/json"
				],
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $body,
				CURLOPT_FOLLOWLOCATION => false
			]
		]], $callback) extends BulkCurlTask {
			public function __construct(array $operations, $callback) {
				parent::__construct($operations, $callback);
			}
			public function onCompletion(Server $server) {
				/** @var callable $callback */
				$callback = $this->fetchLocal();
				if (isset($this->getResult()[0]) && !$this->getResult()[0] instanceof InternetException) {
					$response = $this->getResult()[0];
					$callback($response);
				} else {
					$callback(null);
				}
			}
		});
	}

	public static function replaceVariables(&$options, $propName, $variables) {
		if (isset($options[$propName])) $options[$propName] = strtr($options[$propName], $variables);
	}

	public static function replaceEmbedVariables(&$options, $variables) {
		self::replaceVariables($options, "title", $variables);
		self::replaceVariables($options, "description", $variables);
		if (isset($options["footer"])) {
			self::replaceVariables($options["footer"], "text", $variables);
		}
		if (isset($options["author"])) {
			self::replaceVariables($options["author"], "name", $variables);
		}
		if (isset($options["fields"]) && is_array($options["fields"])) {
			foreach ($options["fields"] as $i => $field) {
				self::replaceVariables($options["fields"][$i], "name", $variables);
				self::replaceVariables($options["fields"][$i], "value", $variables);
			}
		}
	}

	public static function discordCallback($response) {
		if ($response === null || $response[2] !== 204) {
			Server::getInstance()->getLogger()->warning("[Notify] Received non 204 Response from Discord: " . print_r($response, true));
		}
	}

	public static function sendMessage($webhookUrl, $options, $variables) {
		self::replaceVariables($options, "content", $variables);
		self::replaceVariables($options, "username", $variables);
		if (isset($options["embeds"]) && is_array($options["embeds"])) {
			foreach ($options["embeds"] as $i => $embed) {
				self::replaceEmbedVariables($options["embeds"][$i], $variables);
			}
		}

		self::asyncHttpJsonPost($webhookUrl, json_encode($options), [self::class, "discordCallback"]);
	}

	public static function sendEmbed($webhookUrl, $options, $variables) {
		self::replaceEmbedVariables($options, $variables);

		$msgOptions = [
			"embeds" => [$options]
		];

		self::asyncHttpJsonPost($webhookUrl, json_encode($msgOptions), [self::class, "discordCallback"]);
	}
}