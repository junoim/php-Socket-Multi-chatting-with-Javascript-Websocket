var conn = "";
function createSo() {
	if(conn != "") {
		conn.close();
	}
	conn = new WebSocket("ws://180.229.71.116:1237");

	conn.onopen = function() {
		document.getElementById("nick").value = document.getElementById("nick").value.replace(/ /gi, "");
		conn.send("n" + document.getElementById("nick").value);
	}

	conn.onerror = function (e) {
		console.log(e);
	}
		
	conn.onmessage = function (event) {
		msgParser(decodeURIComponent(event.data));
	}

	conn.onclose = function () {
		document.getElementById("chat").innerHTML += "ㅃ2<br>";
	}
}

function msgParser(msg) {
	var d = "";
	var o = msg.substring(0,1);
	var m = msg.substring(1, msg.length);
	if(o == 'n') {
		d = m + "님이 입장하셨습니다.";
	} else if(o == 'm') {
		var x = m.split(" ");
		var n = x[0];
		delete(x[0]);
		m = x.join(" ");
		d = n + " : " + m.substring(1, m.length);
	} else if(o == 'b') {
		d = m + "님이 퇴장하셨습니다 룰루";
	}
	document.getElementById("chat").innerHTML += d+"<br>";
	var node = document.getElementById("chat"); 
	node.scrollTop = node.scrollHeight;
}

function sendMessage() {
	var v = document.getElementById("msg").value;
	v = v.substring(0, 40);
	conn.send("m" + document.getElementById("nick").value+" "+v);
	document.getElementById("msg").value = "";
}

function key(code) {
	if(code == 13)
		sendMessage();
}