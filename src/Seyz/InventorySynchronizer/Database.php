<?php

namespace Seyz\InventorySynchronizer;

use pocketmine\Player;

class Database {

	public static function init()
	{
		$db = self::getDatabase();
		$db->query("CREATE TABLE IF NOT EXISTS inventories(name VARCHAR(255), inventory TEXT, armor TEXT)");
		$db->close();
	}

	public static function restoreInventory(Player $player)
	{
		$name = $player->getName();
		$inv = self::getInventory($name);
		$armor = self::getArmorInventory($name);

		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();

		foreach ($inv as $slot => $item) {
			$player->getInventory()->setItem($slot, $item);
		}
		
		if(isset($armor["helmet"])) $player->getArmorInventory()->setHelmet($armor["helmet"]);
		if(isset($armor["chestplate"])) $player->getArmorInventory()->setChestplate($armor["chestplate"]);
		if(isset($armor["leggings"])) $player->getArmorInventory()->setLeggings($armor["leggings"]);
		if(isset($armor["boots"])) $player->getArmorInventory()->setBoots($armor["boots"]);
	}

	public static function saveInventory(Player $player)
	{
		$db = self::getDatabase();
		$name = $player->getName();
		$invContent = $player->getInventory()->getContents();
		$armorContent = $player->getArmorInventory()->getContents();

		$inv64 = [];
		$armor64 = [];
		
		foreach ($invContent as $slot => $item) {
			$inv64[$slot] = $item; // Maybe useless...
		}
		foreach ($armorContent as $slot => $item) {
			switch ($slot) {
				case 0:
					$armor64["helmet"] = $item;
					break;
				case 1:
					$armor64["chestplate"] = $item;
					break;
				case 2:
					$armor64["leggings"] = $item;
					break;
				case 3:
					$armor64["boots"] = $item;
					break;
			}
		}

		$inv64 = base64_encode(serialize($inv64));
		$armor64 = base64_encode(serialize($armor64));

		$db->query("UPDATE inventories SET inventory='$inv64', armor='$armor64' WHERE name='$name'");
		$db->close();
	}

	public static function getInventory(string $name)
	{
		$db = self::getDatabase();

		$res = $db->query("SELECT * FROM inventories WHERE name='$name'");
		$db->close();
		
		$array = $res->fetch_array();

		$str = base64_decode($array["inventory"]);
		$inv = unserialize($str);

		return $inv;
	}

	public static function getArmorInventory(string $name)
	{
		$db = self::getDatabase();

		$res = $db->query("SELECT * FROM inventories WHERE name='$name'");
		$db->close();
		
		$array = $res->fetch_array();

		$str = base64_decode($array["armor"]);
		$armorInv = unserialize($str);

		return $armorInv;
	}

	public static function register(Player $player)
	{
		$db = self::getDatabase();
		$name = $player->getName();
		$invContent = $player->getInventory()->getContents();
		$armorContent = $player->getArmorInventory()->getContents();

		$inv64 = [];
		$armor64 = [];
		
		foreach ($invContent as $slot => $item) {
			$inv64[$slot] = $item;
		}
		foreach ($armorContent as $slot => $item) {
			$armor64[$slot] = $item;
		}

		$inv64 = base64_encode(serialize($inv64));
		$armor64 = base64_encode(serialize($armor64));

		$db->query("INSERT INTO inventories(name, inventory, armor) VALUES ('$name', '$inv64', '$armor64')");
		$db->close();
	}

	public static function isRegistered(Player $player)
	{
		$db = self::getDatabase();
		$name = $player->getName();
		
		$res = $db->query("SELECT * FROM inventories WHERE name='$name'");
		$db->close();
		
		if($res->num_rows <= 0){
			return false;
		} else
		{
			return true;
		}
		return false;
	}

	public static function getDatabase()
	{
		$db = new \MySQLi(Main::getInstance()->getConfig()->get("mysql-host"), Main::getInstance()->getConfig()->get("mysql-user"), Main::getInstance()->getConfig()->get("mysql-password"), Main::getInstance()->getConfig()->get("mysql-database"));

		return $db;
	}
}