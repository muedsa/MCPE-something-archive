<?php
/*
__PocketMine Plugin__
name=JOINandQUIT
description=IP inquiry
version=0.1
author=MUedsa
class=JOINandQUIT
apiversion=12,13
*/

class JOINandQUIT implements Plugin {
	private $api;

	public function __construct(ServerAPI $api, $server = false){
    	$this->api = $api;
    	$this->server = ServerAPI::request();
  	}

	public function init(){
		$this->api->addHandler("player.spawn", array($this, 'eventhandler'));
		$this->api->addHandler("player.quit", array($this, "eventhandler"));
		console('[JOIN and QUIT] MUedsa全球定位开启');
	}

	public function __destruct(){}

	public function eventhandler($data, $event){
		switch ($event) {
			case 'player.spawn':
				$ip = $data->ip;
				$url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
				$playdata = file_get_contents($url);
				$playdata = json_decode($playdata,true);
				if(isset($playdata['data']['country'])){
					if ($playdata['data']['region'] === $playdata['data']['city']) {
						$this->server->api->chat->broadcast("[JOIN and QUIT] 来自".$playdata['data']['country'].$playdata['data']['city']."的 ".$data->username." 加入了游戏 ！");
					}else{
						$this->server->api->chat->broadcast("[JOIN and QUIT] 来自".$playdata['data']['country'].$playdata['data']['region'].$playdata['data']['city']."的 ".$data->username." 加入了游戏 ！");
					}
				}else{
					$this->server->api->chat->broadcast("[JOIN and QUIT] ".$data->username." 加入了游戏 ！");
				}
				if($this->api->ban->isOp($data->iusername)){
					$this->server->api->chat->broadcast("[JOIN and QUIT] ".$data->username." 还是OP , 欢迎大家不管有事没事都去麻烦TA !");
				}
				break;
			
			case 'player.quit':
				$msg = "[JOIN and QUIT] " . $data->username . "退出游戏 ！";
				$this->server->api->chat->broadcast($msg);
				break;
		}
		
	}

}

