<?php

/*
__PocketMine Plugin__
name=UserPrefix
description=UserPrefix
version=0.3
author=MUedsa
class=UserPrefix
apiversion=12,13
*/


class UserPrefix implements Plugin{
	private $api, $lang, $prefix0, $prefix, $path, $config, $user,$playerLv;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		if(!isset($this->api->economy) or !$this->api->economy instanceof EconomyAPI){
      		console("[ServerVIP][错误]EconomyAPI.php没有找到!本插件需要配合Economy插件(€¢onom￥$)一起使用!");
     	 	$this->api->console->defaultCommands("stop", "", "plugin", false);
    	} 
		$this->api->addHandler("player.join", array($this, "handler"), 5);
		$this->api->addHandler("player.chat", array($this, "handler"), 5);
		$this->readConfig();
		$this->api->console->register("setprefix", "更改称号", array($this, "Pref"));
		$this->api->console->register("setmrpfx", "更改默认称号", array($this, "Pref"));
		$this->api->console->register("setmrmoney", "更改称号费用", array($this, "Pref"));
		$this->api->console->register("spf", "购买称号", array($this, "Pref"));
		$this->api->ban->cmdWhitelist("spf");
	}
	
	public function __destruct(){
	}
	
	public function readConfig(){
		$this->path = $this->api->plugin->createConfig($this, array(
			"chat-format" => "{playerLv}:<{prefix}><{DISPLAYNAME}> {MESSAGE}",
			"moren-pfx" => "平民",
			"moren-money" => 10000,
		));
		$this->config = $this->api->plugin->readYAML($this->path."config.yml");
	}

	
	public function Pref($cmd, $args, $issuer){
		$output = "";
		switch($cmd)
		{	
	   	 	case "setprefix":
	      		$player = $args[0];
		 		$pref = $args[1];
				$this->config['player'][$player] =$pref;
        		$this->api->plugin->writeYAML($this->path."config.yml", $this->config);
         		$output .= "[Prefix] 把 ".$pref." 称号给了 ".$player." !";
         		$this->api->chat->sendTo(false, "[Prefix] 你的称号现在是 ".$pref." !", $player);
          	break;

          	case "setmrmoney":
          	if (is_numeric($args[0]) AND $args[0] >= 0) {
          		$this->config['moren-money'] = $args[0];
        		$this->api->plugin->writeYAML($this->path."config.yml", $this->config);
        		$output .= "[Prefix] 更改默认称号收费为 ".$args[0]." !";
          	}else{
          		$output .= "[Prefix] 请输入你要改为的金钱数 !";
          	}
          	break;

          	case "spf":
          		if(!$issuer instanceof Player){
					$output .=  "请在游戏中运行此命令";
					break;
				}
				if(isset($args[0])){
					$usermoney = $this->api->economy->mymoney($issuer->username);
	          		if($usermoney < $this->config['moren-money']){
	          			$output .= "你的金钱不足10000 ， 无法购买称号 ！";
	          		}else{
	          			$this->api->economy->useMoney( $issuer->username , $this->config['moren-money']);
		          		$this->config['player'][$issuer->username] = $args[0];
		        		$this->api->plugin->writeYAML($this->path."config.yml", $this->config);
		        		$output .= "购买称号成功 , 花费1W元";
          			}
				}else{
					$output .=  "请输入你想要更改的称号!";
				}
          	break;

          	case "setmrpfx":
		        foreach ($this->config["player"] as $key => $value) {
		        	if($value === $this->config['moren-pfx']){
		        		$this->config["player"][$key] = $args[0];
		        	}
		        }
		        $this->config['moren-pfx'] = $args[0];
		        $this->api->plugin->writeYAML($this->path."config.yml", $this->config);
		        $output .=  "更改默认称号为 : ".$args[0];
	  		break;
	  	}
	  	return $output."\n";
	}
	  
	public function handler(&$data, $event){
		switch($event){
				case "player.join":
				$user = $data->username;
				if (!isset($this->config['player'][$user])) {
					$this->config['player'][$user] = $this->config['moren-pfx'];
					$this->api->plugin->writeYAML($this->path."config.yml", $this->config);
				}
			break;
			case "player.chat":
			    $prefix = $data["player"]->username;
           		$playerLv = $data['player']->level->getName();
				$data = array("player" => $data["player"], "message" => str_replace(array("{DISPLAYNAME}", "{MESSAGE}", "{playerLv}",  "{prefix}"), array($data["player"]->username, $data["message"], $data["player"]->level->getName(), $this->config["player"][$prefix]), $this->config["chat-format"]));
				if($this->api->handle("UserPrefix.".$event, $data) !== false){
					$this->api->chat->broadcast($data["message"]);
      	
				}
				return false;
				break;
		}
	}	
}