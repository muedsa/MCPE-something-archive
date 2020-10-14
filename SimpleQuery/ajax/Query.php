<?php

if(isset($_POST["ip"]) AND isset($_POST["port"]) AND $_POST["ip"] !== "" AND $_POST["port"] !== ""){
	$time = time();
	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	$PK_Handshake = "\xFE\xFD".chr(9).pack("N",rand(1,9999999));
	socket_sendto($socket, $PK_Handshake, strlen($PK_Handshake), 0, $_POST["ip"], $_POST["port"]);
	$Data_Handshakere = decode($socket,$time);
	$PK_Status = "\xFE\xFD".chr(0).pack("N",rand(1,9999999)).pack("N",$Data_Handshakere["payload"])."\x00\x00\x00\x00";
	socket_sendto($socket, $PK_Status, strlen($PK_Status), 0, $_POST["ip"], $_POST["port"]);
	$Data_Status = decode($socket,$time);
	$ServerData = explode("\x01",$Data_Status["payload"]);
	$ServerData1 = array_filter(explode("\x00",$ServerData[0]));
	$newServerData = [];
	for ($i=0; $i < count($ServerData1) ; $i+=2) { 
		$newServerData[$ServerData1[$i]] = $ServerData1[$i+1];
	}
	$ServerData2 = array_filter(explode("\x00",$ServerData[1]));
  if(strlen($newServerData["numplayers"]) == 0){
    $newServerData["numplayers"] = 0;
  }
	$html = "";
	$html .= '
		<table class="table table-bordered">
          <caption>服务器·水费账单</caption>
          <thead>
            <tr>
              <th>服务器信息</th>
              <th>数据值</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>服务器名字</td>
              <td>'.$newServerData["hostname"].'</td>
            </tr>
            <tr>
            <tr>
              <td>服务器端口</td>
              <td>'.$newServerData["hostport"].'</td>
            </tr>
              <td>游戏模式</td>
              <td>'.$newServerData["gametype"].'</td>
            </tr>
            <tr>
              <td>游戏客户端</td>
              <td>'.$newServerData["game_id"].'</td>
            </tr>
            <tr>
              <td>游戏客户端版本</td>
              <td>'.$newServerData["version"].'</td>
            </tr>
            <tr>
              <td>服务器端版本</td>
              <td>'.$newServerData["server_engine"].'</td>
            </tr>
            <tr>
              <td>服务器主地图</td>
              <td>'.$newServerData["map"].'</td>
            </tr>
            <tr>
              <td>在线人数/最大人数</td>
              <td>'.$newServerData["numplayers"].'/'.$newServerData["maxplayers"].'</td>
            </tr>
            <tr>
              <td>是否开启白名单</td>
              <td>'.$newServerData["whitelist"].'</td>
            </tr>
          </tbody>
        </table>
        <div class="well">
        	<span class="label label-primary">在线玩家:</span>
          ';
    foreach ($ServerData2 as $player) {
		if ($player !== "player_") {
			$html .= '<span class="label label-default">'.$player."</span>\n";
		}
	}
	$html .= '
        </div>
        <div class="well">
          <span class="label label-primary">加载插件:</span>
          ';
    $p = explode(":",$newServerData['plugins']);
    $plugins = explode(";",$p[1]);
    foreach ($plugins as $plugin) {
    	$html .= '<span class="label label-default">'.$plugin."</span>\n";
    }
    $html .= '</div>';
	$re = array(
		"status" => 1,
		"data" => $html,
		);
	echo json_encode($re);
	socket_close($socket);
}else{
	$re = array(
		"status" => 0,
		"data" => "ParameterError",
		);
	echo json_encode($re);
}

function decode($socket,$time){
	$buffer = "";
	while (true) {
		if(time() - $time > 3){
			die('{"status":0,"data":"Overtime"}');
		}
		socket_recv($socket, $buffer, 65535, 0);
		if($buffer !== false){
			break;
		}
	}
	$redata["packetType"] = ord($buffer{0});
	$redata["sessionID"] = unpack("N",substr($buffer, 1, 4))[1];
	$redata["payload"] = rtrim(substr($buffer, 5));
	return $redata;
}