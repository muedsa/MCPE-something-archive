<?php

namespace FoodEffect;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\entity\Effect;
use pocketmine\entity\InstantEffect;
use pocketmine\item\Fish;

class MainClass extends PluginBase implements Listener{

	public function onLoad(){
		$this->path = $this->getDataFolder();
		@mkdir($this->path);
		$this->getLogger()->info(TextFormat::WHITE . "Loading!");
		$this->config = new Config($this->path."Config.yml", Config::YAML,array(
			"349:0" => array(
				array(
					"effect" => 1,
					"amplifier" => 1,
					"duration" => 20,
					),
				),
			"349:1" => array(
				array(
					"effect" => 8,
					"amplifier" => 5,
					"duration" => 20,
					),
				),
			"349:2" => array(
				array(
					"effect" => 13,
					"amplifier" => 1,
					"duration" => 20,
					),
				),
			"350:0" => array(
				array(
					"effect" => 1,
					"amplifier" => 1,
					"duration" => 40,
					),
				),
			"350:1" => array(
				array(
					"effect" => 8,
					"amplifier" => 5,
					"duration" => 40,
					),
				),
			));
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info(TextFormat::DARK_GREEN . "Enable!");
    }

	public function onDisable(){
		$this->getLogger()->info(TextFormat::DARK_RED . "Disable!");
	}

	public function onEat(PlayerItemConsumeEvent $event){
		$item = $event->getItem();
		$player = $event->getPlayer();
		$id = $item->getId();
		$meta = $item->getDamage();
		if($this->config->exists($id.":".$meta)){
			$effects = $this->config->get($id.":".$meta);
			foreach ($effects as $effect) {
				$player->addEffect(Effect::getEffect((int)$effect["effect"])->setAmplifier((int)$effect["amplifier"])->setDuration((int)$effect["duration"] * 20));
			}
		}
	}
}
