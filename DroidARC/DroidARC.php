<?php
 
/*
 __PocketMine Plugin__
name=DroidARC
description=DroidARC
version=0.1Beta
apiversion=10,11,12,13
author=MUedsa
class=DroidARC
*/
/*
	DroidARC-1.5免费版测试有效,收费版未测试
*/
class DroidARC implements Plugin{
    private $api, $server;
 
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}

	public function init(){
		$this->api->console->register("tttt","CheckDroidARC", array($this, "CheckDroidARC"));
		$this->api->schedule(3*20, array($this, "CheckDroidARC"), array(), true);
		console(FORMAT_RED."[DroidARC]".FORMAT_YELLOW."DroidARC防御插件-测试版");
		console(FORMAT_RED."[DroidARC]".FORMAT_GREEN."作者:".FORMAT_YELLOW."MUedsa");
		console(FORMAT_RED."[DroidARC]".FORMAT_GREEN."QQ:".FORMAT_YELLOW."471215557");
	}

	public function CheckDroidARC(){
		foreach ($this->server->clients as $p) {
			if($p->auth === false){
				$p->close("use DroidARC");
			}
		}
	}

	public function __destruct(){}
	
}