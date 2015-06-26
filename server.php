<?php
/**
 * Created by PhpStorm.
 * User: imjunera
 * Date: 2015-06-19
 * Time: 오후 11:25
 */

Class WebSocket {
    private $host, $port;
    private $serverSocket, $tSocketArray;
    private $clients = array(), $clientsInfo = array();
    private $users = array();
    private $handShakeResponse = ["HTTP/1.1 101 Switching Protocols", "Upgrade: websocket", "Connection: Upgrade", "key", "\r\n"];

    function __construct($port) {
        $this->host = "127.0.0.1";
        $this->port = $port;
        $this->createSocket();
    }

    private function createSocket() {
        $this->serverSocket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_bind($this->serverSocket, 0, $this->port) or die("bind error !!");
        socket_listen($this->serverSocket);
        $this->waitSocket();
    }

    private function waitSocket() {
        while(true) {
            $this->tSocketArray = array_merge(array($this->serverSocket), $this->clients);
            if(socket_select($this->tSocketArray, $ttw = null, $tte = null, 10)) {
                foreach($this->tSocketArray as $eachSocket) {
                    if($eachSocket == $this->serverSocket) {
                        echo "new user !!\n";
                        $_socket = socket_accept($this->serverSocket);
                        socket_getpeername($_socket, $_ip, $_port);
                        $this->clients[$_socket] = $_socket;
                        $this->clientsInfo[$_socket] = array("ip" => $_ip, "port" => $_port);
                    } else {
                        $msg = socket_read($eachSocket, 10000);
                        $this->msgParser($eachSocket, $msg);
                    }
                }
            }
        }
    }

    private function msgParser(&$eachSocket, $msg) {
        if(substr($msg, 0, 3) == "GET") {
            $this->doAuthWebSocket($eachSocket, $msg);
        } else {
            //decode msg
            $dm = $this->decodeMsg($msg);
            if($dm != null && substr($dm,0,1) == 'm') {
                echo $dm;
                foreach($this->clients as $k => $v) {
                    $magic = chr(0x81);
                    $length = chr(strlen($dm));

                    socket_write($v, $magic.$length.$dm);
                }
            } else if($dm != null && substr($dm, 0,1) == 'n') {
                $this->users[$eachSocket] = substr($dm, 1, strlen($dm));
                foreach($this->clients as $k => $v) {
                    $magic = chr(0x81);
                    $length = chr(strlen($dm));

                    socket_write($v, $magic.$length.$dm);
                }
            } else {
                $u = $this->users[$eachSocket];
                unset($this->clients[$eachSocket]);
                unset($this->clientsInfo[$eachSocket]);
                unset($this->users[$eachSocket]);
                socket_close($eachSocket);
                foreach($this->clients as $k => $v) {
                    $magic = chr(0x81);
                    $dm = "b".$u;
                    $length = chr(strlen($dm));

                    socket_write($v, $magic.$length.$dm);
                }
            }
        }
    }

    private function doAuthWebSocket(&$eachSocket, $msg) {
        $acceptKey = $this->getAcceptKey($msg);
        $this->handShakeResponse[3] = "Sec-WebSocket-Accept: ".$acceptKey;
        $acceptData = join("\r\n", $this->handShakeResponse);
        print_r($acceptData);
        socket_write($eachSocket, $acceptData);
    }

    private function getAcceptKey($msg) {
        $msgArr = explode("\r\n", $msg);
        $locationKey = preg_grep("~Sec-WebSocket-Key:~", $msgArr);
        $key = explode(" ",array_merge($locationKey)[0])[1];
        $key .= "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $key =  sha1($key);
        $acceptKey = "";

        for($i=0; $i<strlen($key); $i+=2) {
            $t = $key[$i].$key[$i+1];
            $acceptKey .= chr("0x".$t);
        }
        return base64_encode($acceptKey);
    }

    private function decodeMsg($_msg) {
        $msg = bin2hex($_msg);
        $maskKey[0] = $msg[4].$msg[5];
        $maskKey[1] = $msg[6].$msg[7];
        $maskKey[2] = $msg[8].$msg[9];
        $maskKey[3] = $msg[10].$msg[11];

        $len = 0;
        $originalMsg = "";

        for($i=12; $i<strlen($msg); $i+=2) {
            $t = $msg[$i].$msg[$i+1];
            $originalMsg .= hex2bin($t) ^ hex2bin($maskKey[$len%4]);
            $len++;
        }
        return $originalMsg;
    }
}

new WebSocket(1237);