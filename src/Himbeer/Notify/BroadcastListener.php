<?php

declare(strict_types=1);

namespace Himbeer\Notify;

use pocketmine\command\CommandSender;
use pocketmine\lang\TextContainer;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;

class BroadcastListener implements CommandSender {
	private $plugin;
	private $perm;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->perm = new PermissibleBase($this);
	}

	public function sendMessage($message) {
		if ($message instanceof TextContainer) {
			$message = $this->getServer()->getLanguage()->translate($message);
		} else {
			$message = $this->getServer()->getLanguage()->translateString($message);
		}
		$this->plugin->getLogger()->debug("BroadcastListener got message: " . $message);
	}

	public function getServer() {
		return $this->plugin->getServer();
	}

	public function getName(): string {
		return "NotifyPluginBroadcastListener";
	}

	public function getScreenLineHeight(): int {
		return PHP_INT_MAX;
	}

	public function setScreenLineHeight(int $height = null) {
	}

	public function isPermissionSet($name): bool {
		return $this->perm->isPermissionSet($name);
	}

	public function hasPermission($name): bool {
		return $this->perm->hasPermission($name);
	}

	public function addAttachment(Plugin $plugin, string $name = null, bool $value = null): PermissionAttachment {
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	public function removeAttachment(PermissionAttachment $attachment) {
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions() {
		$this->perm->recalculatePermissions();
	}

	public function getEffectivePermissions(): array {
		return $this->perm->getEffectivePermissions();
	}

	public function isOp(): bool {
		return false;
	}

	public function setOp(bool $value) {
	}
}