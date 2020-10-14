<?php
/*
__PocketMine Plugin__
name=ServerVIP
description=Give VIP/SVIP To Players For PM Server
version=0.2.3
author=MUedsa
class=ServerVIP
apiversion=12,13
*/

/*
[ServerVIP By MUedsa & MG]
本插件发布地址:http://www.cattery.cn
               http://www.mcbbs.net/thread-324492-1-1.html
你可以到这里了解本插件的详细信息及获取最新版本
本插件完全开源，你可以随意修改插件的主体内容，但不能篡改插件作者及注释，发表修改版本时请注明源地址
[版本更新记录]
@0.0.1--禁止普通创造玩家(非OP)放置违禁方块
@0.0.2--添加SVIP,不受以上限制
@0.0.3--SVIP也受限制以维持服务器经济平衡
@0.0.4--VIP,SVIP全部自由切换模式
@0.0.5--增加SVIP签到金钱奖励
@0.0.6--增加违禁品限制
@0.1.0--大更新,重新开发
        修复诸多bug
        增加VIP签到初始奖励和附加奖励金额设置
        把VIP违禁方块归入配置文件
        增加命令 /vipban <add/remove> <方块ID> 添加/删除违禁方块
        把数据分类，重新规划配置文件
@0.1.1--到期自动移除VIP
        修复签到bug
@0.1.2--修复bug
        添加开源声明
@0.1.3--修复签到bug并完善签到,成为真正的连续签到奖励
@0.1.4--修复bug,修复VIP到期机制
@0.1.5--添加/viplist 列出所有VIP
@0.2.0--添加称号功能,与Userperfix插件功能类似
        /pfx 更改自己的称号
        /opfx 强制更改称号
        /setpfx <on/off> 称号功能开关,默认关闭
@0.2.1--添加创造禁止PVP
@0.2.2--修复bug
@0.2.2--修复bug
*/
class ServerVIP implements Plugin{

private $api,$path, $config, $user;
	
  public function __construct(ServerAPI $api, $server = false){
    $this->api = $api;
    $this->server = ServerAPI::request();
  }

  public function init(){
    if(!isset($this->api->economy) or !$this->api->economy instanceof EconomyAPI){
      console("[ServerVIP][错误]EconomyAPI没有找到!本插件需要配合Economy插件(€¢onom￥$)一起使用!");
      $this->api->console->defaultCommands("stop", "", "plugin", false);
    }
    $this->path = $this->api->plugin->createConfig($this, array(
      "default-money" => 5000,
      "add-money" => 500,
      "pfx-common" => 10000,
      "pfx-vip" => 5000,
      "default-pfx" => "平民",
      "pfx-open" => false,
      ));
    $this->config = $this->api->plugin->readYAML($this->path."config.yml");
    $this->vip = new Config(DATA_PATH."plugins/ServerVIP/vip.yml", CONFIG_YAML, array());
    $this->viplist = $this->api->plugin->readYAML(DATA_PATH."plugins/ServerVIP/vip.yml");
    $this->ban = new Config(DATA_PATH."plugins/ServerVIP/ban.yml", CONFIG_YAML, array());
    $this->banlist = $this->api->plugin->readYAML(DATA_PATH."plugins/ServerVIP/ban.yml");
    $this->pfx = new Config(DATA_PATH."plugins/ServerVIP/pfx.yml", CONFIG_YAML, array());
    $this->pfxlist = $this->api->plugin->readYAML(DATA_PATH."plugins/ServerVIP/pfx.yml");
    $this->api->console->register('vip', '将某人设为VIP', array($this, 'command'));
    $this->api->console->register('svip', '将某人设为SVIP', array($this, 'command'));
    $this->api->console->register('devip', '取消某人的VIP/SVIP', array($this, 'command'));
    $this->api->console->register("modes","VIP切换至生存模式", array($this, "command"));
    $this->api->ban->cmdWhitelist("modes");
    $this->api->console->register("modec","VIP切换至创造模式", array($this, "command"));
    $this->api->ban->cmdWhitelist("modec");
    $this->api->console->register("myvip","SVIP每日签到", array($this, "command"));
    $this->api->ban->cmdWhitelist("myvip");
    $this->api->console->register("viplist","列出VIP列表", array($this, "command"));
    $this->api->ban->cmdWhitelist("viplist");
    $this->api->console->register("vipban","禁止VIP在创造模式中使用违禁品", array($this, "command"));
    $this->api->console->register("pfx","改称号", array($this, "command"));
    $this->api->ban->cmdWhitelist("pfx");
    $this->api->console->register("opfx","OP强制改称号", array($this, "command"));
    $this->api->console->register("setpfx","设置称号开关", array($this, "command"));
    //$this->api->console->register("setsvw","设置SVIP专属地图", array($this, "command"));
    $this->api->addHandler("player.block.place", array($this, "eventhandler"));
    $this->api->addHandler("player.spawn", array($this, "eventhandler"));
    $this->api->addHandler("player.join", array($this, "eventhandler"));
    $this->api->addHandler("player.chat", array($this, "eventhandler"));
    $this->api->addHandler("player.interact", array($this, "eventhandler"));
    console("[EDSA服务器插件组]ServerVIP加载成功！");
    date_default_timezone_set('ETC/GMT-8');
  }

  public function __destruct(){}

  public function command($cmd, $args, $issuer)
  {
    switch($cmd){
      case "vip":
        if(!isset($args[0])){
          console("[ServerVIP] 给VIP : /vip <玩家名> <有效天数(不填为一个月>");
          $this->api->chat->sendTo(false, "[ServerVIP] 给VIP : /vip <玩家名> <有效天数(不填为一个月>", $issuer->username);
        }else{
          if(!isset($args[1]) OR $args[1] < 1){
            $d = strtotime('1 months');
            $dueday = date('y-m-d',$d);
          }else{
            $d = strtotime($args[1].' days');
            $dueday = date('y-m-d',$d);
          }
          $player = strtolower($args[0]);
		      $this->viplist[$player]["type"] = "VIP";
          $this->viplist[$player]["dueday"] = $dueday;
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
          $this->server->api->chat->broadcast("[ServerVIP] $player 成为了VIP");
          console("[ServerVIP] $player 成为了VIP");
          $this->api->chat->sendTo(false, "[ServerVIP] 你成为了本服的VIP ，到期时间 :".$dueday, $player);
          $this->api->chat->sendTo(false, "[ServerVIP] 你将 $player 设为了VIP ，到期时间 :".$dueday, $issuer->username);
        }
      break;

      case "svip":
        if(!isset($args[0])){
          console("[ServerVIP] 给SVIP : /svip <玩家名字> <有效天数(不填为一个月>");
          $this->api->chat->sendTo(false, "[ServerVIP] 给SVIP : /svip <玩家名字> <有效天数(不填为一个月>", $issuer->username);
        }else{
          if(!isset($args[1]) OR $args[1] < 1){
            $d = strtotime('1 months');
            $dueday = date('y-m-d',$d);
          }else{
            $d = strtotime($args[1].' days');
            $dueday = date('y-m-d',$d);
          }
          $player = strtolower($args[0]);
          $this->viplist[$player]["type"] = "SVIP";
          $this->viplist[$player]["dueday"] = $dueday;
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
          $this->server->api->chat->broadcast("[ServerVIP] $player 成为了SVIP");
          console("[ServerVIP] $player 成为了SVIP");
          $this->api->chat->sendTo(false, "[ServerVIP] 你成为了本服的SVIP ，到期时间 :".$dueday, $player);
          $this->api->chat->sendTo(false, "[ServerVIP] 你将 $player 设为了SVIP ，到期时间 :".$dueday, $issuer->username);
        }
      break;

      case "devip":
        if(!isset($args[0])){
          console("[ServerVIP] 取消玩家VIP/SVIP : /devip <玩家名>");
          $this->api->chat->sendTo(false, "[ServerVIP] 取消玩家VIP/SVIP : /devip <玩家名>", $issuer->username);
        }else{
	        $player = $args[0];
          $de = $args[1];
          $nplayer = $this->api->player->get($player);
          if($player instanceof Player){
            unset($this->viplist[$player]);
            $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
            $this->server->api->chat->broadcast("[ServerVIP] $player 被取消了VIP");
            $this->api->chat->sendTo(false, "[ServerVIP] 你被取消了VIP $de", $player);
            $this->api->chat->sendTo(false, "[ServerVIP] 你取消了 $player 的VIP ，如果在线会同时撤销创造 $de", $issuer->username);
            if($nplayer->gamemode === CREATIVE) $nplayer->setGamemode(SURVIVAL);
          }else{
            $this->viplist[$player]["dueday"] = date('y-m-d',time());
            $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
            $this->server->api->chat->broadcast("[ServerVIP] $player 被取消了VIP");
          }
        }
      break;
      case "modec":
        $player = $this->server->api->player->get($issuer->username);
        if($this->viplist[$issuer->iusername]["type"] === "VIP" OR $this->viplist[$issuer->iusername]["type"] === "SVIP" ){
          $player->setGamemode(CREATIVE);
          $this->api->chat->sendTo(false, "[ServerVIP] 即将退出以切换至创造模式", $issuer->username);
          $this->server->api->chat->broadcast( "[ServerVIP] $issuer->username 切换到了创造模式");
          break;
        }else{
          $this->api->chat->sendTo(false, "[ServerVIP] 你不是VIP", $issuer->username);
        }
        break;

        case "modes":
          $player = $this->server->api->player->get($issuer->username);
          if($this->viplist[$issuer->iusername]["type"] === "VIP" OR $this->viplist[$issuer->iusername]["type"] ==="SVIP" ){
    	      $player->setGamemode(SURVIVAL);
            $this->api->chat->sendTo(false, "[ServerVIP] 即将退出以切换至生存模式", $issuer->username);
            $this->server->api->chat->broadcast( "[ServerVIP] $issuer->username 切换到了生存模式");
            break;
          }else{
            $this->api->chat->sendTo(false, "[ServerVIP] 你不是VIP", $issuer->username);
          }
        break;

        case "myvip":
          $player = $this->server->api->player->get($issuer->username);
          if($this->viplist[$issuer->iusername]["type"] === "SVIP"){
            $time = date('y-m-d',time());
            $yday = date('y-m-d', strtotime('-1 days'));
            if($this->viplist[$issuer->iusername]["time"] === $time){
              $this->api->chat->sendTo(false, "[ServerVIP] 你今天已经签过到了", $issuer->username);
            }else{
              if(!isset($this->viplist[$issuer->iusername]["count"])  OR $this->viplist[$issuer->iusername]["count"] == 7 OR $this->viplist[$issuer->iusername]["time"] !== $yday){
                $this->viplist[$issuer->iusername]["count"] = 1;
                $this->viplist[$issuer->iusername]["time"] = $time;
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
                $this->api->economy->takeMoney($issuer->username,$this->config["defaul-money"]);
                $this->api->chat->sendTo(false, $time, $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 签到成功 ，得到".$this->config["default-money"]."元", $issuer->username);
              }else{
                $getmoney = $this->config["default-money"] + $this->config["add-money"] * $this->viplist[$issuer->iusername]["count"];
                $this->viplist[$issuer->iusername]["count"] = $this->viplist[$issuer->iusername]["count"] + 1;
                $this->viplist[$issuer->iusername]["time"] = $time;
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
                $this->api->economy->takeMoney($issuer->username,$getmoney);
                $this->api->chat->sendTo(false, "[ServerVIP] 连续第 ".$this->viplist[$issuer->iusername]["count"]." 次签到成功 ，得到".$getmoney."元", $issuer->username);
              }
            }
          }else{
            $this->api->chat->sendTo(false, "[ServerVIP] 你不是SVIP", $issuer->username);
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
                    console("[ServerVIP] 成功禁用ID为 ".$args[1]." 的物品");
                    $this->api->chat->sendTo(false, "[ServerVIP] 成功禁用ID为 ".$args[1]." 的物品", $issuer->username);
                  }else{
                    $this->banlist[] = $args[1];
                    $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/ban.yml", $this->banlist);
                    console("[ServerVIP] 成功禁用ID为 ".$args[1]." 的物品");
                    $this->api->chat->sendTo(false, "[ServerVIP] 成功禁用ID为 ".$args[1]." 的物品", $issuer->username);
                  }
                }else{
                  console("[ServerVIP] ID为 ".$args[1]." 的物品已经被禁用 , 请勿重复禁用");
                  $this->api->chat->sendTo(false, "[ServerVIP] 物品 ID: ".$args[1]." 的物品已经被禁用 , 请勿重复禁用", $issuer->username);
                }
              }else{
                console("[ServerVIP] 方法 : /vipban <add/remove> <物品ID>");
                $this->api->chat->sendTo(false, "[ServerVIP] 禁止VIP在创造模式中使用违禁物品", $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> <物品ID>", $issuer->username);
              }
            break;
            case "remove":
              if($args[1] > 0){
                if(in_array($args[1], $this->banlist)){
                  $key = array_search($args[1], $this->banlist);
                  unset($this->banlist[$key]);
                  $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/ban.yml", $this->banlist);
                  console("[ServerVIP] 成功解除禁用ID为ID为 ".$args[1]." 的物品");
                  $this->api->chat->sendTo(false, "[ServerVIP] 成功解除禁用ID为 ".$args[1]." 的物品", $issuer->username);
                }else{
                  console("[ServerVIP] ID为 ".$args[1]." 的物品没有被禁用");
                  $this->api->chat->sendTo(false, "[ServerVIP] ID: ".$args[1]." 的物品没有被禁用", $issuer->username);
                }
              }else{
                console("[ServerVIP] 方法 : /vipban <add/remove> <物品ID>");
                $this->api->chat->sendTo(false, "[ServerVIP] 禁止VIP在创造模式中使用违禁物品", $issuer->username);
                $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> <物品ID>", $issuer->username);
              }
            break;
            default:
              console("[ServerVIP] 方法 : /vipban <add/remove> <物品ID>");
              $this->api->chat->sendTo(false, "[ServerVIP] 禁止VIP在创造模式中使用违禁物品", $issuer->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 方法 : /vipban <add/remove> <物品ID>", $issuer->username);
            break;
          }
        break;

        case "viplist":
          $viplist = array_keys($this->viplist);
          $viplistchar = implode(" , ",$viplist);
          return "[ServerVIP] VIP玩家列表 : ".$viplistchar;
        break;

        case "pfx":
          if(isset($args[0])){
            if(!isset($this->viplist[$issuer->iusername]["type"])){
              $money = $this->api->economy->mymoney($issuer->username);
              if($money >= $this->config["pfx-common"]){
                $this->api->economy->useMoney($issuer->username,$this->config["pfx-common"]);
                $this->pfxlist["player"][$issuer->iusername] = $args[0];
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
                return "[ServerVIP] 成功将称号更改为 ".$args[0]." , 花费了 ".$this->config["pfx-common"];
              }else{
                return "[ServerVIP] 你的金钱不足 , 需要 ".$this->config["pfx-common"]." 元";
              }
            }elseif($this->viplist[$issuer->iusername]["type" === "VIP"]){
              $money = $this->api->economy->mymoney($issuer->username);
              if($money >= $this->config["pfx-vip"]){
                $this->api->economy->useMoney($issuer->username,$this->config["pfx-vip"]);
                $this->pfxlist["player"][$issuer->iusername] = $args[0];
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
                return "[ServerVIP] 成功将称号更改为 ".$args[0]." , 花费了 ".$this->config["pfx-vip"]." 元";
              }else{
                return "[ServerVIP] 你的金钱不足 , 需要 ".$this->config["pfx-vip"]." 元";
              }
            }elseif($this->viplist[$issuer->iusername]["type" === "VIP"]){
              $this->pfxlist["player"][$issuer->iusername] = $args[0];
              $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
              return "[ServerVIP] 成功将称号更改为 ".$args[0]." , SVIP无需费用";
            }else{
              $money = $this->api->economy->mymoney($issuer->username);
              if($money >= $this->config["pfx-common"]){
                $this->api->economy->useMoney($issuer->username,$this->config["pfx-common"]);
                $this->pfxlist["player"][$issuer->iusername] = $args[0];
                $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
                return "[ServerVIP] 成功将称号更改为 ".$args[0]." , 花费了 ".$this->config["pfx-common"]." 元";
              }else{
                return "[ServerVIP] 你的金钱不足 , 需要 ".$this->config["pfx-common"];
              }
            }
          }else{
            return "[ServerVIP] 更改称号: /pfx <你的称号 >";
          }
        break;

        case 'opfx':
          if(isset($args[0]) AND isset($args[1])){
            $player = strtolower($args[0]);
            $this->pfxlist["player"][$player] = $args[1];
            $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
            return "成功将 ".$args[0]." 的称号改为 ".$args[1];
          }else{
            return "[ServerVIP] OP强制更改称号: /opfx <玩家名> <称号>";
          }
        break;

        case 'setpfx':
          switch($args[0]){
            case 'on':
              $this->config["pfx-open"] = true;
              $this->api->plugin->writeYAML($this->path."config.yml", $this->config);
              return "[ServerVIP] 成功开启称号功能";
            break;
            case 'off':
              $this->config["pfx-open"] = false;
              $this->api->plugin->writeYAML($this->path."config.yml", $this->config);
              return "[ServerVIP] 成功关闭称号功能";
            break;
            default:
              return "[ServerVIP] 开启/关闭称号功能 : /setpfx <on/off>";
            break;
          }
        break;

/*      Building......
        case "setsvw":
          if(!isset($args[0])){
            $this->api->chat->sendTo(false, "[ServerVIP] 请输入目标地图", $issuer->username);
          }else{
            $this->config["svipworld"] = $args[0];
            $this->api->plugin->writeYAML($this->path."config.yml", $this->config);
            $this->api->chat->sendTo(false, "[ServerVIP] 成功将 $args[0] 设为SVIP专属地图", $issuer->username);
          }
        break;
*/
    }
  }

  public function eventhandler($data, $event){
    switch ($event) {
      case 'player.block.place':
        if(isset($this->viplist[$data["player"]->iusername]["type"])){
          if($this->viplist[$data["player"]->iusername]["type"] ==="VIP" OR $this->viplist[$data["player"]->iusername]["type"] === "SVIP"){
            if($data["player"]->gamemode === CREATIVE AND !$this->api->ban->isOp($data["player"]->iusername)){
              $item = $data['item']->getID();
              $banitem = $this->banlist;
              if(in_array($item,$banitem)){
                $this->api->chat->sendTo(false, "[ServerVIP] 禁止在创造模式中使用违禁物品 ！", $data["player"]->username);
                return false;
              }
            }
          }
        }
      break;

      case 'player.spawn':
        if(isset($this->viplist[$data->iusername]["type"])){
          if($this->viplist[$data->iusername]["type"] ==="VIP" OR $this->viplist[$data->iusername]["type"] === "SVIP"){
            $today = date('y-m-d',time());
            if(strtotime($this->viplist[$data->iusername]["dueday"]) <= strtotime($today)){
              $this->api->chat->sendTo(false, "[ServerVIP] 你的VIP/SVIP已经到期 ！", $data->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 你的VIP/SVIP已经到期 ！", $data->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 你的VIP/SVIP已经到期 ！", $data->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 你的VIP/SVIP已经到期 ！", $data->username);
              $this->api->chat->sendTo(false, "[ServerVIP] 你的VIP/SVIP已经到期 ！", $data->username);
              unset($this->viplist[$data->iusername]);
              $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/vip.yml", $this->viplist);
              if($data->gamemode === CREATIVE AND !$this->api->ban->isOp($data->iusername)){
                $this->api->chat->sendTo(false, "[ServerVIP] 即将退出以更改模式 ！", $data->username);
                $data->setGamemode(SURVIVAL);
              }
            }
          }
        }
      break;

      case 'player.join':
        if(!isset($this->pfxlist["player"][$data->iusername])){
          $this->pfxlist["player"][$data->iusername] = $this->config["default-pfx"];
          $this->api->plugin->writeYAML(DATA_PATH."plugins/ServerVIP/pfx.yml", $this->pfxlist);
        }
        break;

        case 'player.chat':
          if($this->config["pfx-open"] === true){
            $playerLv = $data['player']->level->getName();
            $msg = $playerLv." ：[".$this->pfxlist["player"][$data['player']->iusername]."]<".$data['player']->username."> ".$data["message"];
            $this->api->chat->broadcast($msg);
            return false;
          }
        break;
    
        case 'player.interact':
          if($data["entity"]->player->gamemode === "CREATIVE" AND !$this->api->ban->isOp($data["entity"]->player->iusername)){
            $this->api->chat->sendTo(false, "[ServerVIP] 禁止在创造模式中 PVP ！", $data["entity"]->player->iusername);
            return false;
          }
          break;


    }
    
  }




}