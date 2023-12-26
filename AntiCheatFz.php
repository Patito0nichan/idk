<?php

namespace patitoonichan\detection;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\entity\effect\VanillaEffects;

class SpeedDetection implements Listener {
    private PluginBase $plugin;
    private array $lastPositions = [];
    private array $lastTimes = [];
    private float $minSpeed;
    private float $maxSpeed;
    private float $maxSpeedBan;
    private bool $kickOnSpeedBan;
    private bool $notifyOnHighSpeed;
    private bool $notifyOnLowSpeed;

    public function __construct(PluginBase $plugin) {
        $this->plugin = $plugin;
        
        // Load settings from config.yml
        $this->minSpeed = (float) $plugin->getConfig()->get("min-speed", 200);
        $this->maxSpeed = (float) $plugin->getConfig()->get("max-speed", 400);
        $this->maxSpeedBan = (float) $plugin->getConfig()->get("max-speed-ban", 10000);
        $this->kickOnSpeedBan = $plugin->getConfig()->get("kick-on-speed-ban", true);
        $this->notifyOnHighSpeed = $plugin->getConfig()->get("notify-on-high-speed", true);
        $this->notifyOnLowSpeed = $plugin->getConfig()->get("notify-on-low-speed", false);

        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $currentTime = microtime(true);
        $currentPosition = $player->getPosition();

        if (isset($this->lastPositions[$playerName]) && isset($this->lastTimes[$playerName])) {
            $lastPosition = $this->lastPositions[$playerName];
            $lastTime = $this->lastTimes[$playerName];
            $timeDiff = $currentTime - $lastTime;
            $distance = $currentPosition->distance($lastPosition);

            $speed = $distance / $timeDiff;

            $speedEffectMultiplier = 1.0;
            if ($player->getEffects()->has(VanillaEffects::SPEED())) {
                $effect = $player->getEffects()->get(VanillaEffects::SPEED());
                $speedEffectMultiplier += 0.2 * ($effect->getEffectLevel() + 1);
            }

            $adjustedMaxSpeed = $this->maxSpeed * $speedEffectMultiplier;

            if ($speed > $this->maxSpeedBan && $this->kickOnSpeedBan) {
                $player->kick(TextFormat::RED . "Velocidad no permitida detectada, has sido kickeado.");
            } elseif ($speed > $adjustedMaxSpeed && $this->notifyOnHighSpeed) {
                $this->plugin->getLogger()->info(TextFormat::RED . "Velocidad alta detectada: " . $player->getName());
                $player->sendMessage(TextFormat::RED . "Se ha detectado velocidad alta.");
            } elseif ($speed < $this->minSpeed && $this->notifyOnLowSpeed) {
                $this->plugin->getLogger()->info(TextFormat::RED . "Velocidad baja detectada: " . $player->getName());
                $player->sendMessage(TextFormat::RED . "Se ha detectado velocidad baja.");
            }
        }

        $this->lastPositions[$playerName] = $currentPosition;
        $this->lastTimes[$playerName] = $currentTime;
    }
}
