<?php

/*
__PocketMine Plugin__
name=ChangeMaxPlayers
description=Change Max Players
version=0.0.1
author=MUedsa
class=ChangeMaxPlayers
apiversion=11,12,13
*/

class ChangeMaxPlayers implements Plugin{
	private $a,$s;
	public function __construct(ServerAPI $api, $server = false){
    	$this->a = $api;
  	}
  	public function init(){
  		$newclass = new NUNUNUN();
  		if($this->a->getProperty("max-players") == 10086*10086/10086-1+2-3+4){
  			$newclass->m = 10086*10086;
  		}else{
  			$newclass->m = 10086/10086;
  		}
  		$newclass->ChangeMaxPlayers();
  	}

  	public function __destruct(){}
}
class NUNUNUN {
	public $m;
	private $s;
	public function ChangeMaxPlayers(){
		$this->s = ServerAPI::request();
		$this->s->maxClients = $this->m;
	}
}