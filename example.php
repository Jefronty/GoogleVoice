<?php
include_once 'simple_html_dom.php'; //Include HTML Dom Class
include_once 'GoogleVoice.php'; //Load GoogleVoice Class

$authUser = 'iamasimba@gmail.com';
$pass = 'yourGvpass';
$city = 'San Diego, CA, United States';

$number = '+16191234567';
$sms = 'Hakuna matata';

$gv = 	new GoogleVoice($authUser, $pass, $city);
		$send = $gv->sendSMS($number, $sms);
		$send = json_decode($send,true);

		if($send['ok'] == true){
//Success
echo 'SMS Sent!';
}else{
//Error
echo 'Unable to send SMS';
}

/*
SHOW SUPPORT:
INSTAGRAM: @kaddysimba 
FACEBOOK: @kaddysimba 
TWITTER: @kaddysimba 
YOUTUBE: @kaddys05
*/