<?php

namespace Seyz\InventorySynchronizer;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\{
	Listener,
	player\PlayerLoginEvent,
	player\PlayerQuitEvent
};

class Main extends PluginBase implements Listener {

	private $config;
	private static $instance;

	public function onLoad()
	{
		self::$instance = $this;
	}

	public function onEnable()
	{
		@mkdir($this->getDataFolder());
		if(!file_exists($this->getDataFolder()."config.yml")) $this->saveResource("config.yml");
		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		Database::init();
	}

	public function onDisable()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if(Database::isRegistered($player)){
                Database::saveInventory($player);
            }
        }
    }

    public function onLogin(PlayerLoginEvent $ev)
	{
		$player = $ev->getPlayer();

		if(!Database::isRegistered($player)){
			Database::register($player);
		} else {
			Database::restoreInventory($player);
		}
	}

	public function onQuit(PlayerQuitEvent $ev)
	{
		$player = $ev->getPlayer();

		if(Database::isRegistered($player)){
			Database::saveInventory($player);
		}
	}

	public function getConfig() : Config
	{
		return $this->config;
	}

	public static function getInstance()
	{
		return self::$instance;
	}
}