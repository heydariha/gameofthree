<?php
function print_arr($a_arg,$dir = "ltr")
{
    echo "<pre style='text-align:left;direction:ltr'>";
    print_r ($a_arg);
    echo  "</pre>";
}

$host = 'localhost';
$port = '8585';
$null = NULL;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, $port);
socket_listen($socket);
$clients = array($socket);
while (true) {
	$changed = $clients;
	socket_select($changed, $null, $null, 0, 10);
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket);
		$clients[] = $socket_new;
		
		$header = socket_read($socket_new, 1024);
		perform_handshaking($header, $socket_new, $host, $port); 
		
		socket_getsockname($socket_new, $sName);
		$response = mask(json_encode(array('type'=>'system', 'message'=>$sName.' connected','number'=>'0','client'=>'0'))); 
		send_message($response); 
		
		
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	
	foreach ($changed as $changed_socket) {
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text		= unmask($buf); 
			$tst_msg				= json_decode($received_text); 
			if(!is_object($tst_msg))
				break;
			$user_name			= $tst_msg->name; 
			if(empty($user_name))
				break;
			$temp					= calculator($tst_msg->number);
			$user_message		= "Old values :".$tst_msg->number.$temp[0];
			$user_color			= $tst_msg->color; 

			
			$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message,'number'=>$temp[1], 'color'=>$user_color,'client'=>$tst_msg->client)));
			send_message($response_text); 
			break 2; 
		}

		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { 
			
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $sName);
			unset($clients[$found_socket]);
			
			
			$response = mask(json_encode(array('type'=>'system', 'message'=>$sName.' disconnected','client'=>'0')));
			send_message($response);
		}
	}
}

	function calculator($number)
	{	
		$result		= array();
		if($number % 3 == 0)
		{
			$result[0]	= " Added 0 , New Value : ".intval($number) / 3;
			$result[1]	= intval($number) / 3;
		}
		else
		if(intval($number+1) % 3 == 0)
		{
			$result[0]	= " Added +1 , New Value : ".intval($number + 1) / 3;
			$result[1]	= intval($number + 1) / 3;
		}
		else
		{
			$result[0]	= " Added -1, New Value : ".intval($number - 1) / 3;
			$result[1]	= intval($number - 1) / 3;
		}
		return $result;
	}	
socket_close($socket);

function send_message($msg)
{
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}

function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
