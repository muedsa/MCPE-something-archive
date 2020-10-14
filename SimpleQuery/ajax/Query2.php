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
	foreach ($ServerData2 as $player) {
		if ($player !== "player_") {
			$newServerData["players"] .= $player.",";
		}
	}
	$re = array(
		"status" => 1,
		"data" => $newServerData,
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