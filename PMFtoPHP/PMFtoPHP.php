<?php
/*
__PocketMine Plugin__
name=PMFtoPHP
description=PMFtoPHP
version=0.0.1
author=MUedsa
class=PMFtoPHP
apiversion=12,13
*/

class PMFtoPHP implements Plugin{
   	private $api;

   	public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   	}

   	public function init(){
   		$this->api->console->register("p2p", "PMFtoPHP", array($this, "PMFtoPHP"));
         @mkdir(FILE_PATH."plugins/PMFtoPHP");
      	console('[P2P] PMFtoPHP 在控制台输入 /p2p <文件名> 即可开始转换');
   	}

	public function PMFtoPHP($cmd, $args){
   		$file = FILE_PATH."plugins/".$args[0].".pmf";
   		if(file_exists($file)){
            console("[P2P] ".$args[0].".pmf 开始转换");
   			$pmf = new PMFPlugin($file);
			   $info = $pmf->getPluginInfo();
            chmod(dirname(__FILE__), 0777);
            $tofile = fopen(FILE_PATH."plugins/PMFtoPHP/".$args[0].".php", 'w');
            if(!$tofile){
               console("[P2P] 创建".$args[0].".php 失败 !");
            }else{
               console("[P2P] 创建".$args[0].".php 成功 , 开始写入 !");
               $c = "<?php \n/* \n __PocketMine Plugin__ \nname=".$info["name"]." \ndescription=USE PMFtoPHP \nversion=".$info["version"]." \nauthor=".$info["author"]." \nclass=".$info["class"]." \napiversion=".$info["apiversion"]." \n*/ \n".$info["code"];
               $a = fwrite($tofile, $c);
               if(!$a){
                  console("[P2P] ".$args[0].".php 写入失败 !");
               }
               fclose($tofile);
               console("[P2P] ".$args[0].".php 写入成功 !");
            }
            unset($tofile);
            unset($file);
            console("[P2P] ".$args[0].".pmf 成功转换为 ".$args[0].".php , 保存位置为 : ".FILE_PATH."plugins/PMFtoPHP/".$args[0].".php");
   		}else{
   			console("[P2P] ".$args[0].".pmf 不存在 , 请把需要的转换的PMF插件放入plugins文件夹 ! 在控制台输入 /p2p <文件名> 即可开始转换");
   		}
   	}

	public function __destruct(){}

}