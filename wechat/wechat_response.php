<?php

header('Content-Type:text/html;charset=utf8');

require './WeChat.class.php';
define('APPID','wx02dxxxxxxc882');
define('APPSECRET','ffa809d1950bxxxxxxc54de791bac71');
define('TOKEN','weixin0101');

//第一次验证
$wechat = new WeChat(APPID,APPSECRET,TOKEN);
//$wechat->valid();
//

//处理微信公众平台的消息(事件)
$wechat->responseMSG();