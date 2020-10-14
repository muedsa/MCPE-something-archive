<?php
/*
__PocketMine Plugin__
name=Remove
description=Remove SimpleAuth Players to FCLogin
version=1
author=MUedsa
class=Remove
apiversion=12,13
*/
class Remove implements Plugin{

	public function __construct(ServerAPI $api, $server = false){
    	$this->api = $api;
    	$this->server = ServerAPI::request();
  	}

  public function init(){
    $this->api->console->register("remove","remove", array($this, "command"));
  }

  public function command($cmd, $args, $issuer){
    switch($cmd){
      case "remove":
        $i = 0;
        $hostdir = DATA_PATH."plugins/SimpleAuth/players";
        $filesnames = scandir($hostdir);
        foreach ($filesnames as $dir){
          if($dir!=="." AND $dir!== ".." AND !strpos($dir,".")){
            $dir2 = DATA_PATH."plugins/SimpleAuth/players/".$dir;
            $playerdata = scandir($dir2);
            foreach($playerdata as $playerdatai){
              if($playerdatai !=="." AND $playerdatai !== ".."){
                rename(DATA_PATH."plugins/SimpleAuth/players/".$dir."/".$playerdatai,DATA_PATH."plugins/FCLogin/players/".$playerdatai);
                $i++;
                console('移动'.$playerdatai."第 ".$i." 个文件");
              }
            }
          }
        }
        console("移动成功 , 总共".$i." 个文件");
      break;
    }
  }

      
  public function eventhandler($data, $event){
  }

  public function __destruct(){}

}