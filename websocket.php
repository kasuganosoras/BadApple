<?php
error_reporting(0);
//设置端口号为 9090，监听在 0.0.0.0
$port = 1232;
$host = '0.0.0.0';
$null = NULL;
$badapple = file_get_contents("badapple.txt");
$count = 0;
$exp = explode("%line%", $badapple);
//创建 Socket，并设置端口号
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);  
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);  
socket_bind($socket, 0, $port);  
  
//监听端口 9090
socket_listen($socket);

//clients 用于储存所有连接的客户端
$clients = array($socket);

while (true) {
    $cd = $clients;
    socket_select($cd, $null, $null, 0, 10);
    
    //如果有客户端连接到服务器
    if(in_array($socket, $cd)) {
        //接受并加入新的socket连接
        $s_client = socket_accept($socket);
        $clients[] = $s_client;

        //与客户端执行 TCP 握手操作
        $header = socket_read($s_client, 1024);  
        ws_handshake($header, $s_client, $host, $port);  
          
        //获取客户端的 IP 地址
        socket_getpeername($s_client, $ip);
		
		$datas = explode("\n", $header);
		foreach($datas as $ds) {
			$hs = explode(": ", $ds);
			if($hs[0] == 'X-Real-IP') {
				$ip = $hs[1];
			}
		}
		
        //$response = ws_encrypt(json_encode(array('type'=>'system', 'time' => date("Y-m-d H:i:s"), 'message' => $ip.' 加入了聊天', 'action' => 'online')));  
        //ws_send($response);
        $fs = array_search($socket, $cd);  
        unset($cd[$fs]);
    }
	
	if(count($clients) > 1) {
		if($count == 0) {
			$count = 0;
			$response = ws_encrypt("restart");
			ws_send($response);
		}
		if($count > count($exp)) {
			$count = 0;
			$response = ws_encrypt("restart");
			ws_send($response);
		} else {
			$response = ws_encrypt($exp[$count]);
			ws_send($response);
			$count++;
			usleep(31916);
		}
	}
}  
//如果出现奇奇怪怪的情况跳出了循环，关闭 Socket
socket_close($sock);

function ws_send($msg) {
    //将 clients 作为全局变量
    global $clients;

    //为每个客户端都执行一次发送消息
    foreach($clients as $c_client) {
        @socket_write($c_client, $msg, strlen($msg));  
    }
    return true;  
}

//ws_encrypt 加密数据  
function ws_encrypt($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);
    if($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } elseif($length >= 65536) {
        $header = pack('CCNN', $b1, 127, $length);
    }
	unset($b1);
	unset($length);
    return $header . $text;
}

//ws_decrypt 解密数据
function ws_decrypt($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function ws_handshake($ws_header, $connect, $host, $port) {
    $headers = array();
    $lines = preg_split("/\r\n/", $ws_header);
    foreach($lines as $line) {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {  
            $headers[$matches[1]] = $matches[2];
        }
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
    "Upgrade: websocket\r\n" .
    "Connection: Upgrade\r\n" .
    "WebSocket-Origin: " . $host . "\r\n" .
    "WebSocket-Location: ws://" . $host . ":" . $port . "/websocket.php\r\n".
    "Sec-WebSocket-Accept: " . $secAccept . "\r\n\r\n";
    @socket_write($connect, $upgrade, strlen($upgrade));
}
