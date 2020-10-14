<?php

/*
__PocketMine Plugin__
name=Dispeak
description=出生禁止说话和使用某些命令,防御刷屏刷命令攻击
version=0.2
author=MUedsa
class=Dispeak
apiversion=12, 13
*/

class Dispeak implements Plugin{
   private $api,$spawnpos,$cmd = array("ping","help");

   public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   }

   public function init(){
   	$this->api->addHandler("player.chat", array($this, "CHATevent"),49);//49 FCLogin为50
      $this->api->addHandler("player.spawn", array($this, "SPAWNevent"),49);
      $this->api->addHandler("console.command", array($this, "CMDevent"),49);
      console(FORMAT_RED."\t[Dispeak] ".FORMAT_YELLOW."出生禁止说话和使用某些命令,防御刷屏刷命令攻击\n\t\t\t\t".FORMAT_GREEN."作者:".FORMAT_RED."MUedsa\n\t\t\t\t".FORMAT_GREEN."QQ:".FORMAT_RED."471215557\n");
   }

   public function SPAWNevent($data, $event){
      $this->spawnpos["x"] = $data->entity->x;
      //$this->spawnpos["y"] = $data->entity->y;
      $this->spawnpos["z"] = $data->entity->z;
   }

   public function CHATevent($data, $event){
   	$x = $data["player"]->entity->x;
   	//$y = $data["player"]->entity->y;
   	$z = $data["player"]->entity->z;
   	if($x == $this->spawnpos["x"] AND $z == $this->spawnpos["z"]){
   	  $data["player"]->sendChat("[Dispeak] 请移动后再说话 ！");
        return false;
      }
   }

   public function CMDevent($data, $event){
      if($data["issuer"] instanceof Player){
         if($this->spawnpos["x"] == $data["issuer"]->entity->x AND $this->spawnpos["z"] == $data["issuer"]->entity->z){
            if(in_array($data["cmd"],$this->cmd)){
               $data["issuer"]->sendChat("[Dispeak] 请移动后再使用命令 : ".$data["cmd"]);
               return false;
            }
         }
      }
   }

   	public function __destruct(){}
}