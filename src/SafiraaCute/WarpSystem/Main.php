<?php

namespace SafiraaCute\WarpSystem;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF; 

class Main extends PluginBase implements Listener {
	
	public function onEnable(): void{
		$this->getLogger()->info("Plugin Enable");
		$this->getServer()->getPluginManager()->registerEvents($this, $this); 
		$this->saveResource("config.yml");
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML, array());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool {
		switch($cmd->getName()){
			case "warp":
			if(!$sender instanceof Player) {
				return true;
			}
			if(!isset($args[0])){
				$warpName = array();
				foreach($this->data->getAll(true) as $warps) {
					array_push($warpName, $warps); 
				}
				$sender->sendMessage(str_replace(["{count}", "{warp}"], [count((array) $warpName), implode(",", (array) $warpName)], $this->config->get("warp-list")));
				return true; 
			}
			if(is_null($this->data)) {
				$sender->sendMessage($this->config->get("warp-not-available"));
				return true; 
			}
			$warp = $args[0];
			if(!$this->data->exists($warp)) {
				$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-not-found")));
				return true; 
			}
			$y = $this->data->getNested($warp.".y");
			$level = $this->getServer()->getWorldManager()->getWorldByName($this->data->getNested($warp.".world"));
			$x = $sender->getPosition()->getX() >> 4;
            $z = $sender->getPosition()->getZ() >> 4;
            $radius = 15;
            for ($chunkX = -$radius; $chunkX <= $radius; $chunkX++){
                for ($chunkZ = -$radius; $chunkZ <= $radius; $chunkZ++){
                       if (sqrt($chunkX*$chunkX + $chunkZ*$chunkZ) <= $radius) $level->loadChunk($chunkX + $x, $chunkZ + $z);
                }
             }
			$sender->teleport(new Position($x, $y, $z, $level)); 
			$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-teleport-success")));
			return true; 
			case "setwarp":
			if(!$sender instanceof Player) {
				return true; 
			}
			if(!$sender->hasPermission("setwarp.cmd")){
				$sender->sendMessage($this->config->get("no-permission"));
				return true; 
			}
			if(!isset($args[0])){
				$sender->sendMessage($this->config->get("warp-create-invalid"));
				return true; 
			}
			$warp = $args[0];
			if($this->data->exists($warp)) {
				$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-create-exists")));
				return true; 
			}
			$x = $sender->getPosition()->getX();
			$y = $sender->getPosition()->getY();
			$z = $sender->getPosition()->getZ();
			$world = $sender->getWorld()->getDisplayName();
			$this->data->setNested($warp . ".x", $x); 
			$this->data->setNested($warp . ".y", $y); 
			$this->data->setNested($warp . ".z", $z); 
			$this->data->setNested($warp . ".world", $world); 
			$this->data->save();
			$this->data->reload();
			$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-create-success")));
			return true; 
			case "delwarp":
			if(!$sender instanceof Player){
				return true; 
			}
			if(!$sender->hasPermission("delwarp.cmd")){
				$sender->sendMessage($this->config->get("no-permission"));
				return true; 
			}
			if(!isset($args[0])){
				$sender->sendMessage($this->config->get("warp-delete-invalid"));
				return true; 
			}
			$warp = $args[0];
			if(!$this->data->exists($warp)) {
				$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-delete-not-exist")));
				return true; 
			}
			$this->data->remove($warp); 
			$this->data->save();
			$this->data->reload();
			$sender->sendMessage(str_replace(["{warp}"], [$warp], $this->config->get("warp-delete-success")));
			return true; 
		}
	}
}