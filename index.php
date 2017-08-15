<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="./incs/style.css" media="screen">
<script src="./incs/jquery-3.1.1.js"></script>
<?php 
$colours = array('DarkRed','DarkGreen','DarkCyan','DarkMagenta','BlueViolet','Chocolate','Crimson','Brown','CadetBlue');
$uColor = array_rand($colours);
?>
</head>
<body>	
<script>
$(document).ready(function(){
	var serverURL = "ws://localhost:8585/demo/server.php";
	var clientIdUNQ	= '<?php echo  rand(1000,100000000); ?>';
	websocket = new WebSocket(serverURL); 
	websocket.onopen = function(ev) {
		$('#message_box').append("<div class=\"system_msg\">You are Connected!</div>");
	}

	$('#send-btn').click(function(){
		
		var Container = document.getElementById("message_box");
		Container.scrollTop = Container.scrollHeight;
		var msg = {
				number: '<?php echo rand(20,10000);?>',
				message: '',
				name: '<?php echo $colours[$uColor]; ?>',
				color : '<?php echo $colours[$uColor]; ?>',
				client : clientIdUNQ
		};
		websocket.send(JSON.stringify(msg));
	});

	websocket.onmessage = function(ev) {
		var msg			= JSON.parse(ev.data);
		var type			= msg.type;
		var unum		= msg.number;
		var umsg		= msg.message;
		var uname		= msg.name;
		var ucolor		= msg.color;
		var clientId		= msg.client;
		
		if(clientId != clientIdUNQ  &&  parseInt(unum) > 0)
		{
			alert(umsg+" \n "+unum);
			document.getElementById("send-btn").style.visibility = "hidden";
			if(parseInt(unum)  ==1 )
			{
				unum	= 0;
				umsg	= "I WON!!!!";
				document.getElementById("send-btn").style.visibility = "visible";
			}
			
			msg = {
					number: unum,
					message: umsg,
					name: '<?php echo $colours[$uColor]; ?>',
					color : '<?php echo $colours[$uColor]; ?>',
					client : clientIdUNQ
				};
		websocket.send(JSON.stringify(msg));
		}
		
		if(type == 'usermsg') 
		{
			$('#message_box').append("<div><span class=\"user_name\" style=\"color:"+ucolor+"\">"+uname+"</span> : <span class=\"user_message\">"+umsg+"</span></div>");
		}
		if(type == 'system')
		{
			$('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
		}
		
		$('#message').val('');
		
		var Container = document.getElementById("message_box");
		Container.scrollTop = Container.scrollHeight;
	};
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 
});

</script>
<div class="chat_wrapper">
<div class="message_box" id="message_box"></div>
<div class="panel">


</div>
<button style="background-color: <?php echo $colours[$uColor]; ?>;" id="send-btn" class=button>Start</button>

</div>

</body>
</html>