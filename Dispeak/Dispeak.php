<?php

/*
__PocketMine Plugin__
name=Dispeak
description=防止刷屏攻击测试
version=0.0.1
author=MUedsa
class=Dispeak
apiversion=12, 13
*/

class Dispeak implements Plugin{
   	private $api;

   	public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   	}

   	public function init(){
   		$this->api->addHandler("player.chat", array($this, "chatevent"),99);
         $this->api->addHandler("console.command", array($this, "CMDcheck"));
      	console('[Dispeak] 防御刷屏攻击器');
   	}

   	public function chatevent($data, $event){
   		$x = $data["player"]->entity->x;
   		$y = $data["player"]->entity->y;
   	//	$z = $data["player"]->entity->z;
   		$spawn = $data["player"]->getSpawn();
   		if($x == $spawn->x AND $y == $spawn->y){
   			$data["player"]->sendChat("Dispeak] 禁止在出生点说话 , 请移动后在说话 ！");
   		}
   	}

      public function CMDcheck($data, $event){            
         if($data["issuer"] instanceof Player){
            $x = $data["issuer"]->entity->x;
            $y = $data["issuer"]->entity->y;
         }
         $x = $data["player"]->entity->x;
         $y = $data["player"]->entity->y;
         $this->cmdlist[$time]["命令"] = $data["cmd"];
         $this->cmdlist[$time]["参数"] = $data["parameters"];
         $this->cmdlist[$time]["使用者"] = (($data["issuer"] instanceof Player)?$data["issuer"]->username : $data["issuer"]);
         $this->api->plugin->writeYAML($this->path."cmd.yml", $this->cmdlist);
         switch ($data["cmd"]) {
            case 'op':
            case 'deop':
            case 'vip':
            case 'svip':
            case 'dvip':
               $this->scmdlist[$time]["命令"] = $data["cmd"];
               $this->scmdlist[$time]["参数"] = $data["parameters"];
               $this->scmdlist[$time]["使用者"] = (($data["issuer"] instanceof Player)?$data["issuer"]->username : $data["issuer"]);
               $this->api->plugin->writeYAML($this->path."scmd.yml", $this->scmdlist);
               break;
         }
      }  
   		public function __destruct(){}
}