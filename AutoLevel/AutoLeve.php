<?php

/*
__PocketMine Plugin__
name=AutoLevel
description=自动更换资源图
version=0.0.1
author=MUedsa
class=AutoLevel
apiversion=11,12,13
*/
class AutoLevel implements Plugin{
	private $api,$server;

	public function __construct(ServerAPI $api, $server = false){
    	$this->api = $api;
    	$this->server = ServerAPI::request();
  	}

  	public function init(){
  		date_default_timezone_set('ETC/GMT-8');
  		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
			"default-generator" => "SuperflatGenerator",
			"worlds" => array(),
			"date" => date("Y-m-d"),
		));
		$this->api->console->register("changelevel","更换地图", array($this, "CMDevent"));
        $this->api->console->register("addchangelevel","添加要自动更换的地图", array($this, "CMDevent"));
		$this->api->schedule(3*60*60*20, array($this, "Checkadta"), array(), true);
  	}

  	public function __destruct(){}

    public function Checkadta(){
    	if(strtotime($this->config->get("date")) <= strtotime('-1 days')){
    		$worlds = "";
    		$levels = $this->config->get("worlds");
    		foreach ($levels as $level) {
    			$worlds .= $level."  ";
    		}
    		$this->api->chat->broadcast("[AutoLevel] 将在一分钟后重新生成地图:".$worlds);
    		$this->api->schedule(60*20, array($this, "ChangeLevel"), array(), false);
    	}
    }

    public function ChangeLevel(){
    	$levels = $this->config->get("worlds");
        if(!is_null($levels)){
            foreach ($levels as $level) {
                $Le = $this->api->level->get($level);
                if($this->api->level->getDefault() === $Le){
                    console("[AutoLevel] 不能 unload 默认地图 ".$level." !");
                    break;
                }
                if($this->api->level->get($level) !== false){
                    $players = $this->api->player->getAll($Le);
                    foreach ($players as $player) {
                        $player->teleport($this->api->level->getDefault()->getSpawn());
                    }
                    $this->api->level->unloadLevel($Le, true);
                    console("[AutoLevel] unload 地图 ".$level." !");
                }else{
                    console("[AutoLevel] 地图 ".$level." 未加载!");
                }
                $dir = DATA_PATH."worlds/".$level;
                if($this->deldir($dir)){
                    console("[AutoLevel] 删除地图文件 : ".$dir." !");
                }
                if($this->api->level->generateLevel($level) !== false){
                        $this->api->chat->broadcast("[AutoLevel] 地图 ".$level." 生成成功!");
                    }else{
                        $this->api->chat->broadcast("[AutoLevel] 地图 ".$level." 生成失败!");
                }
                console("[AutoLevel] 地图 ".$level." 生成结束!");
                if($this->api->level->loadLevel($level)){
                    console("[AutoLevel] load 地图 ".$level." 成功!");
                }else{
                    console("[AutoLevel] load 地图 ".$level." 失败!");
                }
            }
            $this->config->set("data",date("Y-m-d"));
            $this->api->chat->broadcast("[AutoLevel] 地图全部更换完毕!");
        }else{
            console("[AutoLevel] 重载地图为空!");
        }
    }

    public function CMDevent($cmd, $args, $issuer){
        switch ($cmd) {
            case 'changelevel':
                $levels = $this->config->get("worlds");
                if(!is_null($levels)){
                    foreach ($levels as $level) {
                        $Le = $this->api->level->get($level);
                        if($this->api->level->getDefault() === $Le){
                            console("[AutoLevel] 不能 unload 默认地图 ".$level." !");
                            continue;
                        }
                        if($this->api->level->get($level) !== false){
                            $players = $this->api->player->getAll($Le);
                            foreach ($players as $player) {
                                $player->teleport($this->api->level->getDefault()->getSpawn());
                            }
                            $this->api->level->unloadLevel($Le, true);
                            console("[AutoLevel] unload 地图 ".$level." !");
                        }else{
                            console("[AutoLevel] 地图 ".$level." 未加载!");
                        }
                        $dir = DATA_PATH."worlds/".$level;
                        if($this->deldir($dir)){
                            console("[AutoLevel] 删除地图文件 ".$level." !");
                        }
                        if($this->api->level->generateLevel($level) !== false){
                            $this->api->chat->broadcast("[AutoLevel] 地图 ".$level." 生成成功!");
                        }else{
                            $this->api->chat->broadcast("[AutoLevel] 地图 ".$level." 生成失败!");
                        }
                        if($this->api->level->loadLevel($level)){
                            console("[AutoLevel] load 地图 ".$level." 成功!");
                        }else{
                            console("[AutoLevel] load 地图 ".$level." 失败!");
                        }
                    }
                    $this->config->set("data",date("Y-m-d"));
                    $this->config->save();
                    $this->api->chat->broadcast("[AutoLevel] 地图全部更换完毕!");
                }else{
                    console("[AutoLevel] 重载地图为空!");
                }
                break;
            case 'addchangelevel':
                if(isset($args[0])){
                    $levels = $this->config->get("worlds");
                    if(!in_array($args[0], $levels)){
                        $levels[] = $args[0];
                        $this->config->set("worlds",$levels);
                        $this->config->save();
                        return "[AutoLevel] 成功添加地图:".$args[0];
                    }else{
                        return "[AutoLevel] 已经添加过地图:".$args[0];
                    }
                }else{
                    return "[AutoLevel] 添加自动更换的地图 , /addchangelevel <地图名字>";
                }
                break;
        }
    }

    public function deldir($dir) {
        //先删除目录下的文件:
        $dh = opendir($dir);
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath = $dir."/".$file;
                if(!is_dir($fullpath)){
                    unlink($fullpath);
                }else{
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

}