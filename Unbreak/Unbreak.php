<?php
/*
__PocketMine Plugin__
name=Unbreak
description=Unbreak
version=0.1
author=MUedsa
class=Unbreak
apiversion=12,13
*/

class Unbreak implements Plugin {
	private $api,$lock;

	public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   	}

   	public function init(){
   		$this->path = $this->api->plugin->configPath($this);
   		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array());
   		$this->api->console->register("ub","禁止破坏方块", array($this, "CMDevent"));
   		$this->api->console->register("ob","解锁方块禁止破坏", array($this, "CMDevent"));
   		$this->api->addHandler("player.block.touch", array($this, "onTouch"));
   		$this->api->addHandler("player.spawn", array($this, "Join"));
   	}

   	public function CMDevent($cmd, $args, $issuer){
   		$output = "";
   		if(!($issuer instanceof Player)){
   			$output .= "[Unbreak] 请在游戏里运行此命令.";
   		}
   		switch($cmd){
   			case "ub" :
   				$this->lock[$issuer->iusername] = "lock";
   				$output .= "[Unbreak] 请点击要禁止破坏的方块";
   			break;
   			case "ob" :
   				$this->lock[$issuer->iusername] ="unlock";
   				$output .= "[Unbreak] 请点击要解锁禁止破坏的方块";
   			break;
   		}
   		return $output;
   	}

   	public function onTouch(&$data, $event){
   		$x = $data['target']->x;
		   $y = $data['target']->y;
		   $z = $data['target']->z;
		   $world = $data['player']->level->getName();
   		if(isset($this->lock[$data["player"]->iusername])){
   			switch($this->lock[$data["player"]->iusername]){
   				case "lock" :
   					$this->config->set($x.":".$y.":".$z.":".$world,$x.":".$y.":".$z.":".$world);
   					$this->config->save();
   					unset($this->lock[$data["player"]->iusername]);
   					$data["player"]->sendChat("[Unbreak] 禁止破坏方块 x=".$x.",y=".$y.",z=".$z.",World=".$world);
   				break;

   				case "unlock" :
                  $set = $this->config->exists($x.":".$y.":".$z.":".$world);
                  if($set) {
                     $this->config->remove($x.":".$y.":".$z.":".$world);
                     $this->config->save();
                     unset($this->lock[$data["player"]->iusername]);
                     $data["player"]->sendChat("[Unbreak] 解锁禁止破坏方块 x=".$x.",y=".$y.",z=".$z.",World=".$world);
                  }else{
                     unset($this->lock[$data["player"]->iusername]);
                     $data["player"]->sendChat("[Unbreak] 方块 x=".$x.",y=".$y.",z=".$z.",World=".$world." 没有禁止破坏");
                  }
   				break;
   			}
   		}else{
   			if(!$this->api->ban->isOp($data["player"]->iusername)){
   				$set = $this->config->exists($x.":".$y.":".$z.":".$world);
   				if($set){
   					$data["player"]->sendChat("[Unbreak] 此方块禁止破坏");
   					return false;
   				}
   			}
   		}
   	}

   	public function Join(&$data, $event){
   		unset($this->lock[$data->iusername]);
   	}

   	public function __destruct(){}
}