<?php
 
/*
__PocketMine Plugin__
name=VoidTP
description=When you dead in void , tp you
version=1.0
apiversion=11,12,13
author=MUedsa
class=VoidTP
*/

class Voidtp implements Plugin
{
    private $api;
 
    public function __construct(ServerAPI $api, $server = false)
	{
        $this->api  = $api;
        $this->server = ServerAPI::request();
    }

    public function init()
	{
		$this->api->addHandler("player.spawn", array($this, 'Void'),5);
		$this->api->addHandler("player.respawn", array($this, 'Void'),5);
		console('[VoidTP] 上帝的救赎开启');
	}

	public function Void($data, $event)
	{	
		$x = $data->entity->x;
		$y = $data->entity->y;
		$z = $data->entity->z;
		if ($x > 254 OR $x < 2 OR $y <2 OR $z > 254 OR $z <2) {
			$this->server->api->chat->broadcast("<VoidTP> 发现".$data->username."卡在了虚空 ， 正在进行救赎 ！");
			$data->close("防虚空强制断线，请重新登录");
			$username = strtolower($data->username);
			if(file_exists(DATA_PATH."players/".$username.".yml")){
            	$userdata = new Config(DATA_PATH."players/".$username.".yml", CONFIG_YAML, array());
            	$spawn = $data->level->getSpawn();
				$userdata->set("position", array(
					"level" => $data->level->getName(),
					"x" => $spawn->x,
					"y" => $spawn->y,
					"z" => $spawn->z,
				));
				$userdata->save();
				unset($userdata);
			}
			else{
				console('[NoEmptiness]玩家['.$username.']数据文件不存在或异常。');
			}
		}
	}

	public function __destruct(){}
}