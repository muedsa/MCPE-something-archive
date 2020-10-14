<?php
/*
__PocketMine Plugin__
name=ServerVIP
description=To the server using VIP
version=0.0.6
author=MUedsa
class=ServerVIP
apiversion=12,13
*/

/*
[Minegame电玩城汉化组][原创]ServerVIP插件,由MUedsa修改
@0.0.1--禁止普通创造玩家(非OP)放置违禁方块

@0.0.2--添加SVIP,不受以上限制

@0.0.3--SVIP也受限制,以维持服务器经济平衡

@0.0.4--VIP,SVIP全部自由切换模式

@0.0.5--增加SVIP签到金钱奖励

@0.0.6--增加违禁品限制

*/
class ServerVIP implements Plugin{

private $api,$path, $config, $user;
	
  public function __construct(ServerAPI $api, $server = false){
    $this->api = $api;
    $this->server = ServerAPI::request();
  }

  public function init(){
    if (!file_exists(DATA_PATH."plugins/EconomyAPI.php")) 
    {
      console(FORMAT_RED."[ServerVIP][错误] EconomyAPI.php没有找到！本插件需要配合Economy插件(€¢onom￥$)一起使用！");
      $this->api->console->defaultCommands("stop", array(), false, false);
    } 
    $this->path = $this->api->plugin->createConfig($this, array(
      "default-money" => 5000,
      "add-money" => 500,
      ));
    $this->config = $this->api->plugin->readYAML($this->path."config.yml");
    $this->vip = new Config(DATA_PATH."plugins/ServerVIP/vip.yml", CONFIG_YAML, array());
    $this->viplist = $this->api->plugin->readYAML(DATA_PATH."plugins/ServerVIP/vip.yml");
    $this->ban = new Config(DATA_PATH."plugins/ServerVIP/ban.yml", CONFIG_YAML, array());
    $this->banlist = $this->api->plugin->readYAML(DATA_PATH."plugins/ServerVIP/ban.yml");
    $this->api->console->register('vip', '[ServerVIP] 将某人设为VIP', array($this, 'command'));
    $this->api->console->register('devip', '[ServerVIP] 取消某人的VIP和SVIP', array($this, 'command'));
    $this->api->console->register('svip', '[ServerVIP] 将某人设为SVIP', array($this, 'command'));
    $this->api->console->register("modes","VIP切换至创造模式", array($this, "command"));
    $this->api->ban->cmdWhitelist("modes");
    $this->api->console->register("modec","VIP切换至生存模式", array($this, "command"));
    $this->api->ban->cmdWhitelist("modec");
    $this->api->console->register("myvip","SVIP每日签到", array($this, "command"));
    $this->api->ban->cmdWhitelist("myvip");
    $this->api->console->register("vipban","VIP创造模式禁止使用物品", array($this, "command"));
    //$this->api->console->register("setsvw","设置SVIP专属地图", array($this, "command"));
    $this->api->addHandler("player.block.place", array($this, "eventhandler"));
    $this->api->addHandler("player.spawn", array($this, "eventhandler"));
    console("ServerVIP 加载成功！MG & MUedsa原创插件！EDSA服务器");
    date_default_timezone_set('ETC/GMT-8');
  }

  public function __destruct(){}

  public function command($cmd, $args, $issuer)
  {
    switch($cmd){
      case "vip":
        if(!isset($args[0])){
          console("[ServerVIP] 给 VIP : /vip [ 玩家名字 ] <VIP 天数>.");
          $this->api->chat->sendTo(false, "[ServerVIP] 给 VIP : /vip [ 玩家名字 ] <VIP 天数>.", $issuer->username);
        }else{
          if(!isset($args[1]) OR $args[1] < 1){
            $d = strtotime('1 months');
            $dueday = date('Y-m-d',$d);
          }else{
            $d = strtotime($args[1].' days');
            $dueday = date('Y-m-d',$d);
          }
          $player = strtolower($args[0]);
		      $this->viplist[$player]["type"] = "vip";
          $this->viplist[$player]["dueday"] = $dueday;
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
          $this->server->api->chat->broadcast("[ServerVIP] $player 成为了VIP . 到期时间 :".$dueday);
          console("[ServerVIP] $player 成为了VIP");
          $this->api->chat->sendTo(false, "[ServerVIP] 你成为了本服的VIP. 到期时间 :".$dueday, $player);
          $this->api->chat->sendTo(false, "[ServerVIP] 你将 $player 设为了VIP. 到期时间 :".$dueday, $issuer->username);
        }
      break;

      case "svip":
        if(!isset($args[0])){
          console("[ServerVIP] 给 SVIP : /svip [ 玩家名字 ] <SVIP 天数>.");
          $this->api->chat->sendTo(false, "[ServerVIP] 给 SVIP : /Svip [ 玩家名字 ] <SVIP 天数>.", $issuer->username);
        }else{
          if(!isset($args[1]) OR $args[1] < 1){
            $d = strtotime('1 months');
            $dueday = date('Y-m-d',$d);
          }else{
            $d = strtotime($args[1].' days');
            $dueday = date('Y-m-d',$d);
          }
          $player = strtolower($args[0]);
          $this->viplist[$player]["type"] = "svip";
          $this->viplist[$player]["dueday"] = $dueday;
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
          $this->server->api->chat->broadcast("[ServerVIP] $player 成为了SVIP . 到期时间 :".$dueday);
          console("[ServerVIP] $player 成为了SVIP");
          $this->api->chat->sendTo(false, "[ServerVIP] 你成为了本服的SVIP. 到期时间 :".$dueday, $player);
          $this->api->chat->sendTo(false, "[ServerVIP] 你将 $player 设为了SVIP. 到期时间 :".$dueday, $issuer->username);
        }
      break;

      case "devip":
        if(!isset($args[0])){
          console("[ServerVIP] 移除玩家 VIP : /devip [ 玩家名字 ].");
          $this->api->chat->sendTo(false, "[ServerVIP] 移除玩家 VIP : /devip [ 玩家名字 ].", $issuer->username);
        }else{
	        $player = $args[0];
          $de = $args[1];
          $this->viplist[$player]["type"] =NULL;
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
          $this->server->api->chat->broadcast("[ServerVIP] $player 被取消了VIP");
          $this->api->chat->sendTo(false, "[ServerVIP] 你被取消了VIP. $de", $player);
          $this->api->chat->sendTo(false, "[ServerVIP] 你取消了 $player 的VIP , 如果玩家在线同时会撤销了他的创造. $de", $issuer->username);
        }
      break;
      case "modec":
        $player = $this->server->api->player->get($issuer->username);
        if($this->viplist[$issuer->iusername]["type"] === "svip" OR $this->viplist[$issuer->iusername]["type"] === "vip" ){
          $player->setGamemode(CREATIVE);
          $this->api->chat->sendTo(false, "[ServerVIP] 你即将退出 , 以切换至创造模式.", $issuer->username);
          $this->server->api->chat->broadcast( "[ServerVIP] $issuer->username 切换到了创造模式");
          break;
        }else{
          $this->api->chat->sendTo(false, "[ServerVIP] 你不是VIP.", $issuer->username);
        }
        break;

        case "modes":
          $player = $this->server->api->player->get($issuer->username);
          if($this->viplist[$issuer->iusername]["type"] === "svip" OR $this->viplist[$issuer->iusername]["type"] ==="vip" ){
    	      $player->setGamemode(SURVIVAL);
            $this->api->chat->sendTo(false, "[ServerVIP] 你即将退出 , 以切换至生存模式 ,.", $issuer->username);
            $this->server->api->chat->broadcast( "[ServerVIP] $issuer->username 切换到了生存模式");
            break;
          }else{
            $this->api->chat->sendTo(false, "[ServerVIP] 你不是VIP.", $issuer->username);
          }
        break;

        case "myvip":
          $player = $this->server->api->player->get($issuer->username);
          if($this->viplist[$issuer->iusername]["type"] === "svip"){
            $time = date('y-m-d',time());
            if($this->viplist[$issuer->iusername]["time"] === $time){
              $this->api->chat->sendTo(false, "[ServerVIP] 你今天已经签过到了.", $issuer->username);
            }else{
              if(!isset($this->viplist[$issuer->iusername]["count"])  OR $this->viplist[$issuer->iusername]["count"] == 7){
                $this->viplist[$issuer->iusername]["count"] = 1;
                $this->viplist[$issuer->iusername]["time"] = $time;
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
                $this->api->economy->takeMoney($issuer->username,$this->config["defaul-money"]);
                $this->api->chat->sendTo(false, $time, $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 签到成功 , 得到".$this->config["default-money"]."元.", $issuer->username);
              }else{
                $getmoney = $this->config["default-money"] + $this->config["add-tmoney"] * $this->config[$issuer->iusername]["count"];
                $this->viplist[$issuer->iusername]["count"] = $this->vip[$issuer->iusername]["count"] + 1;
                $this->viplist[$issuer->iusername]["time"] = $time;
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
                $this->api->economy->takeMoney($issuer->username,$getmoney);
                $this->api->chat->sendTo(false, "[ServerVIP] 签到成功 , 得到".$getmoney."元.", $issuer->username);
              }
            }
          }else{
            $this->api->chat->sendTo(false, "[ServerVIP] 你不是SVIP.", $issuer->username);
          }
        break;

        case "vipban":
          switch($args[0]){
            case "add":
              if($args[1] > 0){
                if(!in_array($args[1], $this->banlist)){
                  if(!is_array($this->banlist)){
                    $this->banlist = array($args[1]);
                    $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/ban.yml", $this->banlist);
                    console("[ServerVIP] 物品 ID: ".$args[1]." 成功被ban.");
                    $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 成功被ban.", $issuer->username);
                  }else{
                    $this->banlist[] = $args[1];
                    $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/ban.yml", $this->banlist);
                    console("[ServerVIP] 物品 ID: ".$args[1]." 成功被ban.");
                    $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 成功被ban.", $issuer->username);
                  }
                }else{
                  console("[ServerVIP] 物品 ID: ".$args[1]." 已经被ban, 请勿重复ban除.");
                  $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 已经被ban, 请勿重复ban除.", $issuer->username);
                }
              }else{
                console("[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].");
                $this->api->chat->sendTo(false, "[ServerVIP] ban除物品 , VIP创造模式禁止使用方块.", $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].", $issuer->username);
              }
            break;
            case "remove":
              if($args[1] > 0){
                if(in_array($args[1], $this->banlist)){
                  $key = array_search($args[1], $this->banlist);
                  unset($this->banlist[$key]);
                  $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/ban.yml", $this->banlist);
                  console("[ServerVIP] 物品 ID: ".$args[1]." 成功被解除ban.");
                  $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 成功被解除ban.", $issuer->username);
                }else{
                  console("[ServerVIP] 物品 ID: ".$args[1]." 没有被ban.");
                  $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 没有被ban.", $issuer->username);
                }
              }else{
                console("[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].");
                $this->api->chat->sendTo(false, "[ServerVIP] VIP创造ban除物品 , 禁止使用方块.", $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].", $issuer->username);
              }
            break;
            default:
              console("default:[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].");
              $this->api->chat->sendTo(false, "[ServerVIP] VIP创造ban除物品 , 禁止使用方块.", $issuer->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> [ 物品 ID].", $issuer->username);
            break;
          }
        break;



/*      Building......
        case "setsvw":
          if(!isset($args[0])){
            $this->api->chat->sendTo(false, "[ServerVIP] 请输入目标地图.", $issuer->username);
          }else{
            $this->config["svipworld"] = $args[0];
            $this->api->plugin->writeYAML($this->path."config.yml", $this->config);
            $this->api->chat->sendTo(false, "[ServerVIP] 成功将 $args[0] 设为SVIP专属地图.", $issuer->username);
          }
        break;
*/
    }
  }

  public function eventhandler($data, $event){
    switch ($data) {
      case 'player.block.place':
        if($this->viplist[$data["player"]->iusername]["type"] ==="svip" OR $this->viplist[$data["player"]->iusername]["type"] === "vip"){
          if($data["player"]->gamemode === CREATIVE AND !$this->api->ban->isOp($data["player"]->iusername)){
            $item = $data['item']->getID();
            $banitem = $this->banlist;
            if(in_array($item,$banitem)){
              $this->api->chat->sendTo(false, "[ServerVIP] 禁止放置违禁物品 ！", $data["player"]->username);
              return false;
            }
          }
        }
      break;

      case 'player.spawn':
        if($this->viplist[$data["player"]->iusername]["type"] ==="svip" OR $this->viplist[$data["player"]->iusername]["type"] === "vip"){
          $today = date('y-m-d',time());
          if($today === $this->viplist[$data["player"]->iusername]["dueday"]){
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->api->chat->sendTo(false, "[ServerVIP] 你的 VIP 或 SVIP 今天已经到期 ！", $data["player"]->username);
            $this->viplist[$data["player"]->iusername]["type"] =NULL;
            $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
            if($data["player"]->gamemode === CREATIVE AND !$this->api->ban->isOp($data["player"]->iusername)){
              $this->api->chat->sendTo(false, "[ServerVIP] 你即将被改为生存 ！", $data["player"]->username);
              $data["player"]->setGamemode(SURVIVAL);
            }
          }
        }
      break;
    }
    
  }




}