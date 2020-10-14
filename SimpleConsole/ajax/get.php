<?php
if($_GET['type'] == 1){
    if(isset($_GET['ip']) AND isset($_GET['port']) AND isset($_GET['password']) AND $_GET['ip'] !== "" AND $_GET['port'] !== "" AND $_GET['password'] !== ""){
    	$socket = startSocket($_GET['ip'],$_GET['port'])
    	if ($socket === false) {
    		@socket_close($socket);
    		$re = array(
				"status" => 0,
				"data" => "startSocketFalse",
			);
        	echo json_encode($re);
    	}else{
    		if (Login($socket,$_GET['password']) === 1) {
    			$re = array(
						"status" => 1,
						"data" => "LoginSuccess",
				);
        		echo json_encode($re);
    		}else{
    			@socket_close($socket);
    			$re = array(
						"status" => 0,
						"data" => "LoginError",
				);
        		echo json_encode($re);
    		}
    	}
    }else{
    	$re = array(
				"status" => 0,
				"data" => "ParameterError",
			);
        echo json_encode($re);
    }
}
if($_GET['type'] == 2){
	if (isset($_GET['ip']) AND isset($_GET['port']) AND isset($_GET['password']) AND isset($_GET['cmd']) AND $_GET['ip'] !== "" AND $_GET['port'] !== "" AND $_GET['password'] !== "" AND $_GET['cmd'] !== "") {
		$socket = startSocket($_GET['ip'],$_GET['port']);
		if ($socket === false) {
    		@socket_close($socket);
    		$re = array(
				"status" => 0,
				"data" => "startSocketFalse",
			);
        	echo json_encode($re);
    	}else{
    		if (Login($socket,$_GET['password']) === 1) {
    			$cmddata = RunCmd($socket,$_GET['cmd']);
    			@socket_close($socket);
    			$re = array(
					"status" => 1,
					"data" => $cmddata,
				);
    			echo json_encode($re);
    		}else{
    			@socket_close($socket);
    			$re = array(
						"status" => 0,
						"data" => "LoginError",
				);
        		echo json_encode($re);
    		}
    	}
	}else{
		$re = array(
			"status" => 0,
			"data" => "ParameterError",
		);
		echo json_encode($re);
	}
}

function startSocket($ip,$port){
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket !== false) {
		if (socket_connect($socket, $ip, $port) !== false) {
			return $socket;
		}
	}
	return false;
}
function writePacket($socket, $requestID, $packetType, $payload){
    $pk = pack("V", (int) $requestID)
        . pack("V", (int) $packetType)
        . $payload
        . "\x00\x00"; //Terminate payload and packet
    return @socket_write($socket, pack("V", strlen($pk)) . $pk);
}
function readPacket($socket){
    @socket_set_nonblock($socket);
    $d = @socket_read($socket, 4);
    if($d === false){
        return null;
    }elseif($d === "" or strlen($d) < 4){
        return false;
    }
    @socket_set_block($socket);
    $size = (PHP_INT_SIZE === 8 ? unpack("V", $d)[1] << 32 >> 32 : unpack("V", $d)[1]);
    if($size < 0 or $size > 65535){
        return false;
    }
    $requestID = (PHP_INT_SIZE === 8 ? unpack("V", @socket_read($socket, 4))[1] << 32 >> 32 : unpack("V", @socket_read($socket, 4))[1]);
    $packetType = (PHP_INT_SIZE === 8 ? unpack("V", @socket_read($socket, 4))[1] << 32 >> 32 : unpack("V", @socket_read($socket, 4))[1]);
    $payload = rtrim(@socket_read($socket, $size + 2)); //Strip two null bytes
    $redata = array(
                "size" => $size,
                "requestID" => $requestID,
                "packetType" => $packetType,
                "payload" => $payload,
            );
    return $redata;
}
function Login($socket,$password){
    @writePacket($socket,rand(1,255),3,$password);
    while (true) {
        $redata = @readPacket($socket);
        if ($redata !== null AND $redata !== false) {
            break;
        }
    }
    if(showRedata($redata) == -1){
    	return 0;
    }else{
        return 1;
    }
}
function RunCmd($socket,$cmd){
    writePacket($socket,rand(1,255),2,$cmd);
    while (true) {
        $redata = @readPacket($socket);
        if ($redata !== null AND $redata !== false) {
            break;
        }
    }
    return showRedata($redata);
}
function showRedata($redata){
    switch ($redata['packetType']) {
        case 2:
        	if($redata['requestID'] == -1){
				return -1;
        	}else{
        		return 2;
        	}
            break;
        case 0:
			return $redata['payload'];
            break;
    }
}