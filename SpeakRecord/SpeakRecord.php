<?php
/*
__PocketMine Plugin__
name=SpeakRecord
description=SpeakRecord
version=0.1
author=MUedsa
class=SpeakRecord
apiversion=12,13
*/

class SpeakRecord implements Plugin{
    private $api;

   	public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   	}

   	public function init(){
   		date_default_timezone_set('ETC/GMT-8');//重设时区
   		$this->path = $this->api->plugin->configPath($this);
   		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array());
      $this->speaklist = $this->api->plugin->readYAML($this->path."config.yml");
		  $this->api->addHandler("player.chat", array($this, "CHATevent") ,99);
		  console(FORMAT_RED."\t[Speak] ".FORMAT_YELLOW."聊天记录\n\t\t".FORMAT_GREEN."作者:".FORMAT_RED."MUedsa\n\t\t".FORMAT_GREEN."QQ:".FORMAT_RED."471215557\n");

   	}

   	public function CHATevent($data, $event){
   		$time = date('Y-m-d H:m:s');
   		$this->speaklist[] = $time."  <".$data["player"]->iusername.">: ".$data["message"];
      $this->api->plugin->writeYAML($this->path."config.yml", $this->speaklist);
   	}

    public function __destruct(){}
}